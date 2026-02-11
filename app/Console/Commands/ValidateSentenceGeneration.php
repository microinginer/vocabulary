<?php

namespace App\Console\Commands;

use App\Models\Words;
use App\Models\WordSentences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateSentenceGeneration extends Command
{
    protected $signature = 'sentences:validate-generation';
    protected $description = 'Validate sentence generation statistics';

    public function handle(): int
    {
        $this->info('=== Sentence Generation Validation ===');
        $this->newLine();

        // Total active English words
        $totalWords = Words::where('is_active', 1)
            ->where('language', 'en')
            ->count();
        $this->info("Total active English words: {$totalWords}");

        // Words by sentence count
        $sentenceCounts = DB::table('words')
            ->select(DB::raw('
                CASE
                    WHEN sentence_count = 0 THEN "0 sentences"
                    WHEN sentence_count = 1 THEN "1 sentence"
                    WHEN sentence_count >= 2 THEN "2+ sentences"
                END as category,
                COUNT(*) as word_count
            '))
            ->fromSub(function ($query) {
                $query->select('words.id')
                    ->selectRaw('COUNT(word_sentences.id) as sentence_count')
                    ->from('words')
                    ->leftJoin('word_sentences', 'words.id', '=', 'word_sentences.words_id')
                    ->where('words.is_active', 1)
                    ->where('words.language', 'en')
                    ->groupBy('words.id');
            }, 'word_stats')
            ->groupBy('category')
            ->get();

        $this->newLine();
        $this->info('Words by sentence count:');
        foreach ($sentenceCounts as $stat) {
            $this->line("  {$stat->category}: {$stat->word_count} words");
        }

        // Sample words with different sentence counts
        $this->newLine();
        $this->info('Sample words:');

        // Word with 0 sentences
        $wordWith0 = Words::where('is_active', 1)
            ->where('language', 'en')
            ->whereDoesntHave('sentences')
            ->first();

        if ($wordWith0) {
            $this->line("  0 sentences: {$wordWith0->word} (ID: {$wordWith0->id})");
        }

        // Word with 1 sentence
        $wordWith1 = Words::where('is_active', 1)
            ->where('language', 'en')
            ->has('sentences', '=', 1)
            ->with('sentences')
            ->first();

        if ($wordWith1) {
            $this->line("  1 sentence: {$wordWith1->word} (ID: {$wordWith1->id})");
            $this->line("    - \"{$wordWith1->sentences[0]->content}\"");
        }

        // Word with 2+ sentences
        $wordWith2Plus = Words::where('is_active', 1)
            ->where('language', 'en')
            ->has('sentences', '>=', 2)
            ->with('sentences')
            ->first();

        if ($wordWith2Plus) {
            $sentenceCount = $wordWith2Plus->sentences->count();
            $this->line("  {$sentenceCount} sentences: {$wordWith2Plus->word} (ID: {$wordWith2Plus->id})");
            foreach ($wordWith2Plus->sentences->take(2) as $sentence) {
                $this->line("    - \"{$sentence->content}\"");
            }
        }

        $this->newLine();
        $this->info('✓ Validation complete');

        return 0;
    }
}

