<?php

namespace App\Jobs;

use App\Models\Words;
use App\Models\WordTranslation;
use App\Services\OpenAi\OpenAiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrichWordsBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $wordIds;
    private array $targetLanguages;
    private bool $force;
    private int $lockMinutes;
    private int $maxAttempts;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $wordIds,
        array $targetLanguages,
        bool $force = false,
        int $lockMinutes = 10,
        int $maxAttempts = 3
    ) {
        $this->wordIds = $wordIds;
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
        Log::info('EnrichWordsBatchJob started', [
            'word_ids' => $this->wordIds,
            'target_languages' => $this->targetLanguages,
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
                Log::info('EnrichWordsBatchJob: No words to process (locked or max attempts reached)');
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

            // Build payload for OpenAI
            $payload = [];
            $wordIdsNeedingWork = [];

            foreach ($words as $word) {
                $existingTranslations = $word->translations()
                    ->pluck('language')
                    ->toArray();

                $missingTargets = $this->force
                    ? $this->targetLanguages
                    : array_diff($this->targetLanguages, $existingTranslations);

                if (!empty($missingTargets)) {
                    $payload[] = [
                        'id' => $word->id,
                        'word' => $word->word,
                        'targets' => $missingTargets,
                    ];
                    $wordIdsNeedingWork[] = $word->id;
                }
            }

            if (empty($payload)) {
                // All words already have all translations
                Log::info('EnrichWordsBatchJob: All words already complete', [
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
            $result = $client->enrichWords($payload);

            // Persist results
            $filledCount = 0;
            $skippedCount = 0;

            foreach ($result['items'] as $item) {
                Log::info('EnrichWordsBatchJob: Processing item', ['item' => $item]);
                $wordId = $item['id'];
                $translations = $item['translations'] ?? [];

                $word = $words->firstWhere('id', $wordId);
                if (!$word) {
                    continue;
                }

                foreach ($translations as $lang => $translation) {
                    if (!in_array($lang, $this->targetLanguages)) {
                        continue;
                    }

                    if ($this->force) {
                        // Force mode: always update
                        WordTranslation::updateOrCreate(
                            [
                                'word_id' => $wordId,
                                'language' => $lang,
                            ],
                            [
                                'translation' => $translation,
                            ]
                        );
                        $filledCount++;
                    } else {
                        // Non-force mode: only insert if missing
                        $existing = WordTranslation::where('word_id', $wordId)
                            ->where('language', $lang)
                            ->exists();

                        if (!$existing) {
                            WordTranslation::create([
                                'word_id' => $wordId,
                                'language' => $lang,
                                'translation' => $translation,
                            ]);
                            $filledCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
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

            // Mark words that were complete (not in payload) as done
            $completeWordIds = array_diff($words->pluck('id')->toArray(), $wordIdsNeedingWork);
            if (!empty($completeWordIds)) {
                Words::whereIn('id', $completeWordIds)->update([
                    'gpt_status' => 'done',
                    'gpt_enriched_at' => DB::raw('COALESCE(gpt_enriched_at, NOW())'),
                    'gpt_lock_until' => null,
                ]);
            }

            Log::info('EnrichWordsBatchJob completed', [
                'items_processed' => count($payload),
                'translations_filled' => $filledCount,
                'translations_skipped' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('EnrichWordsBatchJob failed', [
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

