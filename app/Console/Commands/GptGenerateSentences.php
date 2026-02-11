<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSentencesBatchJob;
use App\Models\Words;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GptGenerateSentences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentences:gpt-generate
                            {--limit=200 : Maximum number of words to process}
                            {--batch=30 : Number of words per batch job}
                            {--target=2 : Minimum sentences per word}
                            {--force : Generate target sentences for all words regardless of existing count}
                            {--max-attempts=3 : Maximum number of GPT attempts per word}
                            {--lock-minutes=10 : Lock duration in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing example sentences for words using GPT';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch');
        $targetCount = (int) $this->option('target');
        $force = $this->option('force');
        $maxAttempts = (int) $this->option('max-attempts');
        $lockMinutes = (int) $this->option('lock-minutes');

        if ($targetCount < 1) {
            $this->error('Target must be at least 1');
            return 1;
        }

        $this->info('Starting GPT sentence generation');
        $this->info("Limit: {$limit}, Batch: {$batchSize}, Target: {$targetCount}");
        $this->info("Force: " . ($force ? 'yes' : 'no'));

        Log::info('sentences:gpt-generate started', [
            'limit' => $limit,
            'batch' => $batchSize,
            'target' => $targetCount,
            'force' => $force,
        ]);

        // Build query for candidate words
        $query = Words::where('is_active', 1)
            ->where('language', 'en')
            ->where(function ($q) use ($maxAttempts) {
                $q->whereNull('gpt_lock_until')
                    ->orWhere('gpt_lock_until', '<', now());
            })
            ->where('gpt_attempts', '<', $maxAttempts);

        if (!$force) {
            // Only select words that have fewer sentences than target
            $query->whereRaw('(
                SELECT COUNT(*)
                FROM word_sentences
                WHERE word_sentences.words_id = words.id
            ) < ?', [$targetCount]);
        }

        // Order by enrichment status (unenriched first, then oldest first)
        $query->orderByRaw('(gpt_enriched_at IS NULL) DESC, gpt_enriched_at ASC, id ASC');

        $candidateIds = $query->limit($limit)->pluck('id')->toArray();

        $candidateCount = count($candidateIds);
        $this->info("Found {$candidateCount} candidate words");
        Log::info('sentences:gpt-generate candidates found', ['count' => $candidateCount]);

        if ($candidateCount === 0) {
            $this->info('No words to process');
            return 0;
        }

        // Dispatch jobs in batches
        $batches = array_chunk($candidateIds, $batchSize);
        $jobsDispatched = 0;

        foreach ($batches as $batch) {
            GenerateSentencesBatchJob::dispatch(
                $batch,
                $targetCount,
                $force,
                $lockMinutes,
                $maxAttempts
            );
            $jobsDispatched++;
        }

        $this->info("Dispatched {$jobsDispatched} batch jobs");
        Log::info('sentences:gpt-generate jobs dispatched', ['jobs_count' => $jobsDispatched]);

        $this->info('Done! Jobs queued for processing.');

        return 0;
    }
}

