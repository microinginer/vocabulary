<?php

namespace App\Console\Commands;

use App\Models\Words;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\WordTranslation;

class TranslateWords extends Command
{
    protected $signature = 'translate:words {language} {--limit=100}';
    protected $description = 'Translate words from English to specified language using Yandex.Translate API';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $IAM_TOKEN = env('YANDEX_TRANSLATE_API_KEY');
        $folder_id = env('YANDEX_FOLDER_ID');
        $target_language = $this->argument('language');
        $limit = $this->option('limit');
        $offset = 0;

        if (!in_array($target_language, ['ru', 'uz', 'az'])) {
            $this->error('Invalid language parameter. Valid options are: ru, uz, az.');
            return;
        }

        $client = new Client();
        $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';

        while (true) {
            $words = Words::offset($offset)->limit($limit)->get();

            if ($words->isEmpty()) {
                break;
            }

            $texts = $words->pluck('word')->toArray();

            $post_data = [
                'targetLanguageCode' => $target_language,
                'texts' => $texts,
                'folderId' => $folder_id,
            ];

            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer $IAM_TOKEN",
                    ],
                    'json' => $post_data,
                ]);

                $responseBody = json_decode($response->getBody(), true);

                if (isset($responseBody['translations'])) {
                    foreach ($words as $index => $word) {
                        $translation = $responseBody['translations'][$index]['text'] ?? null;

                        if ($translation) {
                            WordTranslation::updateOrCreate(
                                ['word_id' => $word->id, 'language' => $target_language],
                                ['translation' => $translation]
                            );
                        }
                    }

                    $this->info("Translations for offset $offset successfully saved.");
                } else {
                    $this->error("Translation failed for offset $offset.");
                }
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
            }

            $offset += $limit;
        }

        $this->info('All translations processed.');
    }
}
