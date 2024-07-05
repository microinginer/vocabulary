<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Words;
use App\Models\WordTranslation;
use Illuminate\Support\Facades\DB;

class MigrateTranslations extends Command
{
    protected $signature = 'migrate:translations {language}';
    protected $description = 'Migrate translations from words table to word_translations table';

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
            $words = Words::all();

            foreach ($words as $word) {
                $translation = $this->getTranslationByLanguage($word, $language);

                if ($translation) {
                    WordTranslation::create([
                        'word_id' => $word->id,
                        'language' => $language,
                        'translation' => $translation,
                    ]);
                }
            }
        });

        $this->info("Translations migrated successfully for language: $language.");
    }

    private function getTranslationByLanguage($word, $language)
    {
        switch ($language) {
            case 'ru':
                return $word->translate;
            case 'uz':
                // Assuming you have translations for uz in some other way
                return $this->getUzbekTranslation($word);
            case 'az':
                // Assuming you have translations for az in some other way
                return $this->getAzerbaijaniTranslation($word);
            default:
                return null;
        }
    }

    private function getUzbekTranslation($word)
    {
        // Implement your logic to get Uzbek translation
        return null; // Placeholder
    }

    private function getAzerbaijaniTranslation($word)
    {
        // Implement your logic to get Azerbaijani translation
        return null; // Placeholder
    }
}
