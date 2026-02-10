<?php

namespace App\Console\Commands;

use App\Models\Words;
use App\Models\WordSentences;
use App\Models\WordTranslation;
use App\Models\WordSentenceTranslation;
use Illuminate\Console\Command;

class ValidateGptEnrichment extends Command
{
    protected $signature = 'gpt:validate';
    protected $description = 'Validate GPT enrichment pipeline setup';

    public function handle(): int
    {
        $this->info('=== GPT Enrichment Pipeline Validation ===');
        $this->newLine();

        // Check words
        $totalWords = Words::where('is_active', 1)->where('language', 'en')->count();
        $this->info("Active EN words: {$totalWords}");

        $translationStats = WordTranslation::selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->pluck('count', 'language')
            ->toArray();

        $this->info('Word translations by language:');
        foreach ($translationStats as $lang => $count) {
            $this->line("  {$lang}: {$count}");
        }

        // Check for missing translations
        $missingRu = Words::where('is_active', 1)
            ->where('language', 'en')
            ->whereDoesntHave('translations', function ($q) {
                $q->where('language', 'ru');
            })
            ->count();

        $missingUz = Words::where('is_active', 1)
            ->where('language', 'en')
            ->whereDoesntHave('translations', function ($q) {
                $q->where('language', 'uz');
            })
            ->count();

        $this->info("Words missing RU: {$missingRu}");
        $this->info("Words missing UZ: {$missingUz}");

        // Check GPT status
        $statusStats = Words::where('language', 'en')
            ->selectRaw('gpt_status, COUNT(*) as count')
            ->groupBy('gpt_status')
            ->pluck('count', 'gpt_status')
            ->toArray();

        $this->newLine();
        $this->info('GPT status distribution:');
        foreach ($statusStats as $status => $count) {
            $this->line("  {$status}: {$count}");
        }

        // Check sentences
        $this->newLine();
        $totalSentences = WordSentences::count();
        $this->info("Total sentences: {$totalSentences}");

        $sentenceTranslationStats = WordSentenceTranslation::selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->pluck('count', 'language')
            ->toArray();

        $this->info('Sentence translations by language:');
        foreach ($sentenceTranslationStats as $lang => $count) {
            $this->line("  {$lang}: {$count}");
        }

        // Sample word
        $this->newLine();
        $sampleWord = Words::where('is_active', 1)
            ->where('language', 'en')
            ->with('translations')
            ->first();

        if ($sampleWord) {
            $this->info("Sample word: {$sampleWord->word} (ID: {$sampleWord->id})");
            $this->info("GPT status: {$sampleWord->gpt_status}");
            $this->info("GPT attempts: {$sampleWord->gpt_attempts}");
            $this->info('Translations:');
            foreach ($sampleWord->translations as $trans) {
                $this->line("  {$trans->language}: {$trans->translation}");
            }
        }

        $this->newLine();
        $this->info('✓ Validation complete');

        return 0;
    }
}

