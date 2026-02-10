<?php

namespace App\Console\Commands;

use App\Jobs\EnrichWordsBatchJob;
use App\Models\Words;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GptEnrichWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'words:gpt-enrich
                            {--limit=200 : Maximum number of words to process}
                            {--batch=30 : Number of words per batch job}
                            {--languages=ru,uz : Comma-separated target languages}
                            {--force : Overwrite existing translations}
                            {--max-attempts=3 : Maximum number of GPT attempts per word}
                            {--lock-minutes=10 : Lock duration in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich words with GPT translations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch');
        $languagesStr = $this->option('languages');
        $force = $this->option('force');
        $maxAttempts = (int) $this->option('max-attempts');
        $lockMinutes = (int) $this->option('lock-minutes');

        // Parse target languages
        $targetLanguages = array_map('trim', explode(',', $languagesStr));
        $targetLanguages = array_filter($targetLanguages);

        if (empty($targetLanguages)) {
            $this->error('No target languages specified');
            return 1;
        }

        // Default behavior: only process missing translations unless force is set
        $onlyMissing = !$force;

        $this->info('Starting GPT word enrichment');
        $this->info("Limit: {$limit}, Batch: {$batchSize}, Languages: " . implode(',', $targetLanguages));
        $this->info("Force: " . ($force ? 'yes' : 'no') . ", Only Missing: " . ($onlyMissing ? 'yes' : 'no'));

        Log::info('words:gpt-enrich started', [
            'limit' => $limit,
            'batch' => $batchSize,
            'languages' => $targetLanguages,
            'force' => $force,
            'only_missing' => $onlyMissing,
        ]);

        // Build query for candidate words
        $query = Words::where('is_active', 1)
            ->where('language', 'en')
            ->where(function ($q) use ($maxAttempts) {
                $q->whereNull('gpt_lock_until')
                    ->orWhere('gpt_lock_until', '<', now());
            })
            ->where('gpt_attempts', '<', $maxAttempts);

        if ($force) {
            // Force mode: process any word (new, failed, or even done)
            $query->whereIn('gpt_status', ['new', 'failed', 'done', null]);
        } else {
            // Non-force mode: only process words that need work
            if ($onlyMissing) {
                // Find words missing at least one target language
                $query->where(function ($q) use ($targetLanguages) {
                    foreach ($targetLanguages as $lang) {
                        $q->orWhereDoesntHave('translations', function ($translationQuery) use ($lang) {
                            $translationQuery->where('language', $lang);
                        });
                    }
                });
            } else {
                // Process words with new/failed status
                $query->whereIn('gpt_status', ['new', 'failed', null]);
            }
        }

        $candidateIds = $query->limit($limit)->pluck('id')->toArray();

        $candidateCount = count($candidateIds);
        $this->info("Found {$candidateCount} candidate words");
        Log::info('words:gpt-enrich candidates found', ['count' => $candidateCount]);

        if ($candidateCount === 0) {
            $this->info('No words to process');
            return 0;
        }

        // Dispatch jobs in batches
        $batches = array_chunk($candidateIds, $batchSize);
        $jobsDispatched = 0;

        foreach ($batches as $batch) {
            EnrichWordsBatchJob::dispatch(
                $batch,
                $targetLanguages,
                $force,
                $lockMinutes,
                $maxAttempts
            );
            $jobsDispatched++;
        }

        $this->info("Dispatched {$jobsDispatched} batch jobs");
        Log::info('words:gpt-enrich jobs dispatched', ['jobs_count' => $jobsDispatched]);

        $this->info('Done! Jobs queued for processing.');

        return 0;
    }
}

