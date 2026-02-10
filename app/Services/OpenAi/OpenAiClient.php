<?php

namespace App\Services\OpenAi;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient
{
    private string $apiKey;
    private string $model;
    private int $timeout;
    private int $maxRetries;
    private int $backoffBaseMs;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->model = config('openai.model');
        $this->timeout = config('openai.timeout');
        $this->maxRetries = config('openai.max_retries');
        $this->backoffBaseMs = config('openai.backoff_base_ms');
    }

    /**
     * Enrich words with translations
     *
     * @param array $payload Format: [['id' => 1, 'word' => 'hello', 'targets' => ['ru', 'uz']], ...]
     * @return array Format: ['items' => [['id' => 1, 'translations' => ['ru' => '...', 'uz' => '...']], ...]]
     */
    public function enrichWords(array $payload): array
    {
        Log::info('OpenAI enrichWords called', ['batch_size' => count($payload)]);

        $systemPrompt = $this->getWordEnrichmentSystemPrompt();
        $userMessage = $this->buildWordEnrichmentPrompt($payload);

        return $this->callOpenAi($systemPrompt, $userMessage);
    }

    /**
     * Translate sentences
     *
     * @param array $payload Format: [['id' => 1, 'en' => 'Hello world', 'targets' => ['ru', 'uz']], ...]
     * @return array Format: ['items' => [['id' => 1, 'translations' => ['ru' => '...', 'uz' => '...']], ...]]
     */
    public function translateSentences(array $payload): array
    {
        Log::info('OpenAI translateSentences called', ['batch_size' => count($payload)]);

        $systemPrompt = $this->getSentenceTranslationSystemPrompt();
        $userMessage = $this->buildSentenceTranslationPrompt($payload);

        return $this->callOpenAi($systemPrompt, $userMessage);
    }

    /**
     * Call OpenAI API with retry logic
     */
    private function callOpenAi(string $systemPrompt, string $userMessage): array
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                    ->timeout($this->timeout)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $systemPrompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => $userMessage,
                            ],
                        ],
                        'response_format' => [
                            'type' => 'json_object',
                        ],
                        'temperature' => 0.3,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? '';

                    return $this->parseAndValidateJson($content);
                }

                // Handle transient errors
                $statusCode = $response->status();
                if ($this->isTransientError($statusCode)) {
                    $attempt++;
                    if ($attempt < $this->maxRetries) {
                        $this->exponentialBackoff($attempt);
                        continue;
                    }
                }

                // Non-transient error
                throw new \RuntimeException(
                    "OpenAI API error [{$statusCode}]: " . $response->body()
                );

            } catch (RequestException $e) {
                // Timeout or connection error
                $attempt++;
                if ($attempt < $this->maxRetries) {
                    Log::warning('OpenAI request exception, retrying', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    $this->exponentialBackoff($attempt);
                    continue;
                }
                throw new \RuntimeException('OpenAI request failed: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new \RuntimeException('OpenAI API max retries exceeded');
    }

    /**
     * Parse and validate JSON response
     */
    private function parseAndValidateJson(string $content): array
    {
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($decoded['items']) || !is_array($decoded['items'])) {
                Log::error('OpenAI response missing items array', ['content' => $content]);
                throw new \RuntimeException('Invalid response format: missing items array');
            }

            return $decoded;

        } catch (\JsonException $e) {
            Log::error('OpenAI JSON parse error', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to parse OpenAI JSON response: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if error is transient (should retry)
     */
    private function isTransientError(int $statusCode): bool
    {
        return $statusCode === 429 || $statusCode >= 500;
    }

    /**
     * Exponential backoff delay
     */
    private function exponentialBackoff(int $attempt): void
    {
        $delayMs = $this->backoffBaseMs * (2 ** ($attempt - 1));
        usleep($delayMs * 1000);
    }

    /**
     * System prompt for word enrichment
     */
    private function getWordEnrichmentSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a professional translator. Translate English words into the requested languages.

CRITICAL RULES:
1. Output ONLY valid JSON, no markdown, no code blocks, no extra text
2. Use this exact format: {"items": [{"id": number, "translations": {"ru": "...", "uz": "..."}}]}
3. For Uzbek (uz): Use LATIN script only (e.g., "salom", NOT "салом")
4. Provide natural, simple translations
5. Only include the target languages requested for each word
6. Keep word translations concise (typically 1-3 words)

PROMPT;
    }

    /**
     * System prompt for sentence translation
     */
    private function getSentenceTranslationSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a professional translator. Translate English sentences into the requested languages.

CRITICAL RULES:
1. Output ONLY valid JSON, no markdown, no code blocks, no extra text
2. Use this exact format: {"items": [{"id": number, "translations": {"ru": "...", "uz": "..."}}]}
3. For Uzbek (uz): Use LATIN script only (e.g., "Men o'qiyapman", NOT "Мен ўқияпман")
4. Provide natural, faithful translations
5. Keep translations concise and natural (not overly literal)
6. Only include the target languages requested for each sentence

PROMPT;
    }

    /**
     * Build user message for word enrichment
     */
    private function buildWordEnrichmentPrompt(array $payload): string
    {
        $items = [];
        foreach ($payload as $item) {
            $items[] = [
                'id' => $item['id'],
                'word' => $item['word'],
                'targets' => $item['targets'],
            ];
        }

        return json_encode(['words' => $items], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build user message for sentence translation
     */
    private function buildSentenceTranslationPrompt(array $payload): string
    {
        $items = [];
        foreach ($payload as $item) {
            $items[] = [
                'id' => $item['id'],
                'en' => $item['en'],
                'targets' => $item['targets'],
            ];
        }

        return json_encode(['sentences' => $items], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the model being used
     */
    public function getModel(): string
    {
        return $this->model;
    }
}

