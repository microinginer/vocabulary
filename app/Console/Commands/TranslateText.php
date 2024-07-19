<?php

namespace App\Console\Commands;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Core\Exception\ServiceException;
use Illuminate\Console\Command;
use Google\Cloud\Translate\V2\TranslateClient;

class TranslateText extends Command
{
    protected $signature = 'translate:text {text}';
    protected $description = 'Translate text from English to Uzbek using Google Translate API';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws GoogleException
     * @throws ServiceException
     */
    public function handle(): void
    {
        $text = $this->argument('text');

        // Убедитесь, что вы настроили свои учетные данные Google Cloud
        $translate = new TranslateClient([
            'key' => env('GOOGLE_TRANSLATE_API_KEY'),
        ]);

        $result = $translate->translate($text, [
            'target' => 'uz',
            'source' => 'en',
        ]);

        $this->info('Original Text: ' . $text);
        $this->info('Translated Text: ' . $result['text']);
    }
}
