<?php

namespace App\Jobs;

use App\Models\WordSentences;
use App\Models\WordSentenceTranslation;
use App\Services\OpenAi\OpenAiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranslateSentencesBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $sentenceIds;
    private array $targetLanguages;
    private bool $force;
    private int $lockMinutes;
    private int $maxAttempts;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $sentenceIds,
        array $targetLanguages,
        bool $force = false,
        int $lockMinutes = 10,
        int $maxAttempts = 3
    ) {
        $this->sentenceIds = $sentenceIds;
        $this->targetLanguages = $targetLanguages;
        $this->force = $force;
        $this->lockMinutes = $lockMinutes;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiClient $client): void
    {
        Log::info('TranslateSentencesBatchJob started', [
            'sentence_ids' => $this->sentenceIds,
            'target_languages' => $this->targetLanguages,
            'force' => $this->force,
        ]);

        DB::beginTransaction();

        try {
            // Lock rows for update where lock is expired
            $sentences = WordSentences::whereIn('id', $this->sentenceIds)
                ->where(function ($query) {
                    $query->whereNull('gpt_lock_until')
                        ->orWhere('gpt_lock_until', '<', now());
                })
                ->where('gpt_attempts', '<', $this->maxAttempts)
                ->lockForUpdate()
                ->get();

            if ($sentences->isEmpty()) {
                Log::info('TranslateSentencesBatchJob: No sentences to process (locked or max attempts reached)');
                DB::commit();
                return;
            }

            // Update lock status
            $lockUntil = now()->addMinutes($this->lockMinutes);
            foreach ($sentences as $sentence) {
                $sentence->update([
                    'gpt_status' => 'processing',
                    'gpt_lock_until' => $lockUntil,
                    'gpt_attempts' => DB::raw('gpt_attempts + 1'),
                    'gpt_last_error' => null,
                ]);
            }

            DB::commit();

            // Build payload for OpenAI
            $payload = [];
            $sentenceIdsNeedingWork = [];

            foreach ($sentences as $sentence) {
                $existingTranslations = $sentence->translations()
                    ->pluck('language')
                    ->toArray();

                $missingTargets = $this->force
                    ? $this->targetLanguages
                    : array_diff($this->targetLanguages, $existingTranslations);

                if (!empty($missingTargets)) {
                    $payload[] = [
                        'id' => $sentence->id,
                        'en' => $sentence->content,
                        'targets' => $missingTargets,
                    ];
                    $sentenceIdsNeedingWork[] = $sentence->id;
                }
            }

            if (empty($payload)) {
                // All sentences already have all translations
                Log::info('TranslateSentencesBatchJob: All sentences already complete', [
                    'sentence_ids' => $sentences->pluck('id')->toArray(),
                ]);

                foreach ($sentences as $sentence) {
                    $sentence->update([
                        'gpt_status' => 'done',
                        'gpt_enriched_at' => $sentence->gpt_enriched_at ?? now(),
                        'gpt_lock_until' => null,
                    ]);
                }

                return;
            }

            // Call OpenAI
            $result = $client->translateSentences($payload);

            // Persist results
            $filledCount = 0;
            $skippedCount = 0;

            foreach ($result['items'] as $item) {
                $sentenceId = $item['id'];
                $translations = $item['translations'] ?? [];

                $sentence = $sentences->firstWhere('id', $sentenceId);
                if (!$sentence) {
                    continue;
                }

                foreach ($translations as $lang => $translation) {
                    if (!in_array($lang, $this->targetLanguages)) {
                        continue;
                    }

                    if ($this->force) {
                        // Force mode: always update
                        WordSentenceTranslation::updateOrCreate(
                            [
                                'word_sentence_id' => $sentenceId,
                                'language' => $lang,
                            ],
                            [
                                'translation' => $translation,
                            ]
                        );
                        $filledCount++;
                    } else {
                        // Non-force mode: only insert if missing
                        $existing = WordSentenceTranslation::where('word_sentence_id', $sentenceId)
                            ->where('language', $lang)
                            ->exists();

                        if (!$existing) {
                            WordSentenceTranslation::create([
                                'word_sentence_id' => $sentenceId,
                                'language' => $lang,
                                'translation' => $translation,
                            ]);
                            $filledCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                }

                // Mark sentence as done
                $sentence->update([
                    'gpt_status' => 'done',
                    'gpt_enriched_at' => now(),
                    'gpt_lock_until' => null,
                    'gpt_last_error' => null,
                    'gpt_model' => $client->getModel(),
                ]);
            }

            // Mark sentences that were complete (not in payload) as done
            $completeSentenceIds = array_diff($sentences->pluck('id')->toArray(), $sentenceIdsNeedingWork);
            if (!empty($completeSentenceIds)) {
                WordSentences::whereIn('id', $completeSentenceIds)->update([
                    'gpt_status' => 'done',
                    'gpt_enriched_at' => DB::raw('COALESCE(gpt_enriched_at, NOW())'),
                    'gpt_lock_until' => null,
                ]);
            }

            Log::info('TranslateSentencesBatchJob completed', [
                'items_processed' => count($payload),
                'translations_filled' => $filledCount,
                'translations_skipped' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TranslateSentencesBatchJob failed', [
                'error' => $e->getMessage(),
                'sentence_ids' => $this->sentenceIds,
            ]);

            // Mark sentences as failed
            WordSentences::whereIn('id', $this->sentenceIds)->update([
                'gpt_status' => 'failed',
                'gpt_last_error' => substr($e->getMessage(), 0, 255),
                'gpt_lock_until' => null,
            ]);

            throw $e;
        }
    }
}

