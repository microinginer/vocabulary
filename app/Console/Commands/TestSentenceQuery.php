<?php

namespace App\Console\Commands;

use App\Models\Words;
use Illuminate\Console\Command;

class TestSentenceQuery extends Command
{
    protected $signature = 'test:sentence-query';
    protected $description = 'Test the sentence count query fix';

    public function handle(): int
    {
        $this->info('Testing sentence count query...');

        $wordIds = [1345, 1346, 1347];

        try {
            $wordsWithCounts = Words::whereIn('words.id', $wordIds)
                ->select('words.id', 'words.word')
                ->selectRaw('COUNT(word_sentences.id) as sentences_count')
                ->leftJoin('word_sentences', 'words.id', '=', 'word_sentences.words_id')
                ->groupBy('words.id', 'words.word')
                ->get();

            $this->info('✓ Query successful!');
            $this->info("Words retrieved: {$wordsWithCounts->count()}");

            foreach ($wordsWithCounts as $word) {
                $this->line("  {$word->word} (ID: {$word->id}) - {$word->sentences_count} sentences");
            }

            $this->newLine();
            $this->info('✓ Fix verified! The ambiguous column issue is resolved.');

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Query failed!');
            $this->error($e->getMessage());
            return 1;
        }
    }
}

