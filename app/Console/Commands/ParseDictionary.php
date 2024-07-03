<?php

namespace App\Console\Commands;

use App\Models\WordSentences;
use Illuminate\Console\Command;
use App\Models\Words;
use DOMDocument;
use DOMXPath;

class ParseDictionary extends Command
{
    protected $signature = 'parse:dictionary';
    protected $description = 'Parse dictionary from 7english.ru and store results';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Буквы от A до Z
        $letters = range('A', 'Z');
        $base_url = "http://www.7english.ru/dictionary.php?id=500&letter=";

        foreach ($letters as $letter) {
            $url = $base_url . $letter;
            $this->info("Parsing page: $url");
            $pageResults = $this->parsePage($url);
            $this->storeWords($pageResults);
            sleep(2); // Пауза 2 секунды между запросами
        }
    }

    private function getPageContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

    private function parsePage($url)
    {
        $pageContent = $this->getPageContent($url);
        $results = [];

        if ($pageContent !== false) {
            $dom = new DOMDocument();
            @$dom->loadHTML($pageContent);
            $xpath = new DOMXPath($dom);
            $rows = $xpath->query('//table[@width="793px" and @align="center"]/tr[@style]');

            if ($rows->length > 0) {
                foreach ($rows as $row) {
                    $cols = $row->getElementsByTagName('td');
                    if ($cols->length >= 5) {
                        $valueNode = $cols->item(1)->getElementsByTagName('a')->item(0);
                        $transcriptionNode = $cols->item(3)->getElementsByTagName('a')->item(0);
                        $translationNode = $cols->item(5);

                        $value = $valueNode ? trim($valueNode->nodeValue) : '';
                        $transcription = $transcriptionNode ? trim($transcriptionNode->nodeValue) : '';
                        $translation = $translationNode ? trim($translationNode->nodeValue) : '';

                        // Если есть несколько переводов, берем только первый
                        $translation = explode(',', $translation)[0];

                        // Удаление лишних символов из перевода, включая неразрывный пробел
                        $translation = str_replace("\u{A0}", '', $translation);
                        $translation = trim($translation);

                        // Извлечение примеров употребления
                        $detailUrl = $valueNode ? $valueNode->getAttribute('href') : '';
                        $sentences = $detailUrl ? $this->parseDetailPage($detailUrl) : [];

                        $results[] = [
                            'word' => $value,
                            'transcription' => $transcription,
                            'translation' => $translation,
                            'length' => strlen($value),
                            'sentences' => $sentences,
                        ];
                    }
                }
            } else {
                $this->warn("No data table found on page $url.");
            }
        } else {
            $this->error("Unable to access page $url.");
        }

        return $results;
    }

    private function parseDetailPage($url)
    {
        $base_url = "http://www.7english.ru";
        $full_url = $base_url . $url;
        $pageContent = $this->getPageContent($full_url);
        $sentences = [];

        if ($pageContent !== false) {
            $dom = new DOMDocument();
            @$dom->loadHTML($pageContent);
            $xpath = new DOMXPath($dom);
            $rows = $xpath->query('//table[@width="97%" and @align="center"]/tr');

            $currentPhrase = '';
            foreach ($rows as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length > 1) {
                    $label = trim($cols->item(1)->nodeValue);
                    if ($label == 'Фраза') {
                        $phrase = trim($cols->item(5)->nodeValue);
                        $currentPhrase = $phrase;
                    } elseif ($label == 'Перевод' && $currentPhrase != '') {
                        $translation = trim($cols->item(5)->nodeValue);
                        $sentences[] = ['phrase' => $currentPhrase, 'translation' => $translation];
                        $currentPhrase = '';
                    }
                }
            }
        } else {
            $this->warn("Unable to access detail page $full_url.");
        }

        return $sentences;
    }

    private function storeWords($words)
    {
        foreach ($words as $word) {
            // Проверка уникальности по полю 'word' и 'translate'
            $existingWord = Words::where('word', $word['word'])
                ->where('translate', $word['translation'])
                ->first();

            if (!$existingWord) {
                Words::create([
                    'word' => $word['word'],
                    'translate' => $word['translation'],
                    'length' => $word['length'],
                    'pronunciation' => $word['transcription'],
                    'is_active' => 1,
                    'difficulty_level' => 1,
                ]);


                if (!empty($word['sentences'])) {
                    $res = Words::where('word', $word['word'])
                        ->where('translate', $word['translation'])
                        ->first();

                    foreach ($word['sentences'] as $sentence) {
                        if (!$res) {
                            continue;
                        }
                        WordSentences::create([
                            'words_id' => $res->id,
                            'content' => $sentence['phrase'],
                            'content_translate' => $sentence['translation'],
                        ]);
                    }
                }
            }
        }
    }
}
