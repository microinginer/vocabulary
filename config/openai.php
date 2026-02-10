<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API key for authentication.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default OpenAI model to use for enrichment tasks.
    |
    */

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for API response.
    |
    */

    'timeout' => env('OPENAI_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Max Retries
    |--------------------------------------------------------------------------
    |
    | Maximum number of retry attempts for transient errors.
    |
    */

    'max_retries' => env('OPENAI_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Backoff Base (milliseconds)
    |--------------------------------------------------------------------------
    |
    | Base delay for exponential backoff on retries.
    |
    */

    'backoff_base_ms' => env('OPENAI_BACKOFF_BASE_MS', 1000),

];

