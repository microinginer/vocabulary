<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TranslateTextYandex extends Command
{
    protected $signature = 'translate:text-yandex {text}';
    protected $description = 'Translate text from English to Uzbek using Yandex.Translate API';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $IAM_TOKEN = env('YANDEX_TRANSLATE_API_KEY');
        $folder_id = env('YANDEX_FOLDER_ID');
        $target_language = 'uz';
        $text = $this->argument('text');

        $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $IAM_TOKEN",
        ];

        $post_data = [
            'targetLanguageCode' => $target_language,
            'texts' => [$text],
            'folderId' => $folder_id,
        ];

        $client = new Client();

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $post_data,
            ]);

            $responseBody = json_decode($response->getBody(), true);

            if (isset($responseBody['translations'][0]['text'])) {
                $this->info('Original Text: ' . $text);
                $this->info('Translated Text: ' . $responseBody['translations'][0]['text']);
            } else {
                $this->error('Translation failed.');
            }
        } catch (RequestException $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
