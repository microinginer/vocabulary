<?php

namespace App\Jobs;

use App\Models\Words;
use App\Models\WordSentences;
use App\Services\OpenAi\OpenAiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateSentencesBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $wordIds;
    private int $targetCount;
    private bool $force;
    private int $lockMinutes;
    private int $maxAttempts;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $wordIds,
        int $targetCount = 2,
        bool $force = false,
        int $lockMinutes = 10,
        int $maxAttempts = 3
    ) {
        $this->wordIds = $wordIds;
        $this->targetCount = $targetCount;
        $this->force = $force;
        $this->lockMinutes = $lockMinutes;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiClient $client): void
    {
        Log::info('GenerateSentencesBatchJob started', [
            'word_ids' => $this->wordIds,
            'target_count' => $this->targetCount,
            'force' => $this->force,
        ]);

        DB::beginTransaction();

        try {
            // Lock rows for update where lock is expired
            $words = Words::whereIn('id', $this->wordIds)
                ->where(function ($query) {
                    $query->whereNull('gpt_lock_until')
                        ->orWhere('gpt_lock_until', '<', now());
                })
                ->where('gpt_attempts', '<', $this->maxAttempts)
                ->lockForUpdate()
                ->get();

            if ($words->isEmpty()) {
                Log::info('GenerateSentencesBatchJob: No words to process (locked or max attempts reached)');
                DB::commit();
                return;
            }

            // Update lock status
            $lockUntil = now()->addMinutes($this->lockMinutes);
            foreach ($words as $word) {
                $word->update([
                    'gpt_status' => 'processing',
                    'gpt_lock_until' => $lockUntil,
                    'gpt_attempts' => DB::raw('gpt_attempts + 1'),
                    'gpt_last_error' => null,
                ]);
            }

            DB::commit();

            // Fetch words with their current sentence counts efficiently
            $wordsWithCounts = Words::whereIn('words.id', $this->wordIds)
                ->select('words.id', 'words.word')
                ->selectRaw('COUNT(word_sentences.id) as sentences_count')
                ->leftJoin('word_sentences', 'words.id', '=', 'word_sentences.words_id')
                ->groupBy('words.id', 'words.word')
                ->get();

            // Build payload for OpenAI
            $payload = [];
            $wordNeeds = [];

            foreach ($wordsWithCounts as $wordData) {
                $currentCount = $wordData->sentences_count;

                if ($this->force) {
                    // Force mode: always generate targetCount new sentences
                    $needed = $this->targetCount;
                } else {
                    // Normal mode: only generate up to target
                    $needed = max(0, $this->targetCount - $currentCount);
                }

                if ($needed > 0) {
                    $payload[] = [
                        'id' => $wordData->id,
                        'word' => $wordData->word,
                        'needed' => $needed,
                    ];
                    $wordNeeds[$wordData->id] = [
                        'word' => $wordData->word,
                        'needed' => $needed,
                    ];
                }
            }

            if (empty($payload)) {
                // All words already have enough sentences
                Log::info('GenerateSentencesBatchJob: All words already have enough sentences', [
                    'word_ids' => $words->pluck('id')->toArray(),
                ]);

                foreach ($words as $word) {
                    $word->update([
                        'gpt_status' => 'done',
                        'gpt_enriched_at' => $word->gpt_enriched_at ?? now(),
                        'gpt_lock_until' => null,
                    ]);
                }

                return;
            }

            // Call OpenAI
            $result = $client->generateSentences($payload);

            // Persist results
            $sentencesInserted = 0;
            $duplicatesSkipped = 0;
            $insertedThisRun = [];

            foreach ($result['items'] as $item) {
                $wordId = $item['id'];
                $sentencesEn = $item['sentences_en'] ?? [];

                if (!isset($wordNeeds[$wordId])) {
                    continue;
                }

                $word = $words->firstWhere('id', $wordId);
                if (!$word) {
                    continue;
                }

                $needed = $wordNeeds[$wordId]['needed'];

                // Truncate to needed count if more were returned
                $sentencesEn = array_slice($sentencesEn, 0, $needed);

                // Get existing sentences for this word (case-insensitive comparison)
                $existingSentences = WordSentences::where('words_id', $wordId)
                    ->pluck('content')
                    ->map(function ($content) {
                        return strtolower(trim($content));
                    })
                    ->toArray();

                foreach ($sentencesEn as $sentence) {
                    $sentence = trim($sentence);
                    $sentenceLower = strtolower($sentence);

                    // Skip if duplicate of existing sentence
                    if (in_array($sentenceLower, $existingSentences)) {
                        $duplicatesSkipped++;
                        continue;
                    }

                    // Skip if already inserted in this run
                    if (in_array($sentenceLower, $insertedThisRun)) {
                        $duplicatesSkipped++;
                        continue;
                    }

                    // Insert new sentence
                    WordSentences::create([
                        'words_id' => $wordId,
                        'content' => $sentence,
                        'content_translate' => null,
                    ]);

                    $sentencesInserted++;
                    $insertedThisRun[] = $sentenceLower;
                    $existingSentences[] = $sentenceLower;
                }

                // Mark word as done
                $word->update([
                    'gpt_status' => 'done',
                    'gpt_enriched_at' => now(),
                    'gpt_lock_until' => null,
                    'gpt_last_error' => null,
                    'gpt_model' => $client->getModel(),
                ]);
            }

            // Mark words that didn't need processing as done
            $processedWordIds = array_keys($wordNeeds);
            $completeWordIds = array_diff($words->pluck('id')->toArray(), $processedWordIds);
            if (!empty($completeWordIds)) {
                Words::whereIn('id', $completeWordIds)->update([
                    'gpt_status' => 'done',
                    'gpt_enriched_at' => DB::raw('COALESCE(gpt_enriched_at, NOW())'),
                    'gpt_lock_until' => null,
                ]);
            }

            Log::info('GenerateSentencesBatchJob completed', [
                'words_processed' => count($payload),
                'sentences_inserted' => $sentencesInserted,
                'duplicates_skipped' => $duplicatesSkipped,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('GenerateSentencesBatchJob failed', [
                'error' => $e->getMessage(),
                'word_ids' => $this->wordIds,
            ]);

            // Mark words as failed
            Words::whereIn('id', $this->wordIds)->update([
                'gpt_status' => 'failed',
                'gpt_last_error' => substr($e->getMessage(), 0, 255),
                'gpt_lock_until' => null,
            ]);

            throw $e;
        }
    }
}

