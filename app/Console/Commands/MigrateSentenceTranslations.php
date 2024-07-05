<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordSentences;
use App\Models\WordSentenceTranslation;
use Illuminate\Support\Facades\DB;

class MigrateSentenceTranslations extends Command
{
    protected $signature = 'migrate:sentence-translations {language}';
    protected $description = 'Migrate sentence translations from word_sentences table to word_sentence_translations table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $language = $this->argument('language');

        if (!in_array($language, ['ru', 'uz', 'az'])) {
            $this->error('Invalid language. Valid options are: ru, uz, az.');
            return;
        }

        DB::transaction(function () use ($language) {
            $sentences = WordSentences::all();

            foreach ($sentences as $sentence) {
                $translation = $this->getTranslationByLanguage($sentence, $language);

                if ($translation) {
                    WordSentenceTranslation::create([
                        'word_sentence_id' => $sentence->id,
                        'language' => $language,
                        'translation' => $translation,
                    ]);
                }
            }
        });

        $this->info("Sentence translations migrated successfully for language: $language.");
    }

    private function getTranslationByLanguage($sentence, $language)
    {
        switch ($language) {
            case 'ru':
                return $sentence->content_translate;
            case 'uz':
                // Assuming you have translations for uz in some other way
                return $this->getUzbekTranslation($sentence);
            case 'az':
                // Assuming you have translations for az in some other way
                return $this->getAzerbaijaniTranslation($sentence);
            default:
                return null;
        }
    }

    private function getUzbekTranslation($sentence)
    {
        return null;
    }

    private function getAzerbaijaniTranslation($sentence)
    {
        return null;
    }
}
