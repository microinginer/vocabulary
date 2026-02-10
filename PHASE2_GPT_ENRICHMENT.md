# Phase 2: GPT Enrichment Pipeline - Implementation Guide

## Overview

This implementation provides a robust GPT-based translation enrichment pipeline for vocabulary words and sentences. It supports batch processing with retry logic, idempotent operations, and proper error handling.

## Components

### A) Configuration

**Files:**
- `config/openai.php` - OpenAI service configuration
- `.env.example` - Added `OPENAI_API_KEY`

**Environment Variables:**
```bash
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4o-mini  # Optional, defaults to gpt-4o-mini
OPENAI_TIMEOUT=60         # Optional, defaults to 60 seconds
OPENAI_MAX_RETRIES=3      # Optional, defaults to 3
OPENAI_BACKOFF_BASE_MS=1000  # Optional, defaults to 1000ms
```

### B) OpenAI Client

**File:** `app/Services/OpenAi/OpenAiClient.php`

**Features:**
- Uses Laravel HTTP client
- Enforces strict JSON output via `response_format`
- Implements exponential backoff for transient errors (429, 5xx, timeouts)
- Validates JSON response structure
- Logs batch sizes and parse failures
- Two main methods:
  - `enrichWords(array $payload): array`
  - `translateSentences(array $payload): array`

**Error Handling:**
- Retries on transient errors (429 rate limits, 5xx server errors, timeouts)
- Exponential backoff: base_ms × 2^(attempt-1)
- Throws exception after max retries exceeded

### C) Queue Jobs

**Files:**
- `app/Jobs/EnrichWordsBatchJob.php`
- `app/Jobs/TranslateSentencesBatchJob.php`

**Features:**
- Idempotent operations (safe to retry)
- Row-level locking with `lockForUpdate()`
- Respects lock expiration and max attempts
- Handles both force and non-force modes
- Updates GPT status fields:
  - `gpt_status`: 'new', 'processing', 'done', 'failed'
  - `gpt_enriched_at`: timestamp when completed
  - `gpt_attempts`: retry counter
  - `gpt_last_error`: error message (max 255 chars)
  - `gpt_lock_until`: lock expiration timestamp
  - `gpt_model`: model used for enrichment

**Force Mode Behavior:**
- `force=false`: Only creates new translations, never overwrites existing
- `force=true`: Uses `updateOrCreate()` to overwrite existing translations

**Job Flow:**
1. Lock eligible rows (lock expired, attempts < max)
2. Update lock status and increment attempts
3. Build payload with only items needing work
4. Call OpenAI API
5. Persist results (respecting force flag)
6. Mark as done/failed and clear locks
7. Log statistics

### D) Artisan Commands

#### `words:gpt-enrich`

Enrich words with GPT translations.

**Usage:**
```bash
# Basic usage - fills missing RU/UZ translations for 200 words
php artisan words:gpt-enrich

# Custom batch size and limit
php artisan words:gpt-enrich --limit=50 --batch=10

# Specify languages
php artisan words:gpt-enrich --languages=ru,uz,az

# Force overwrite existing translations
php artisan words:gpt-enrich --force

# Custom retry settings
php artisan words:gpt-enrich --max-attempts=5 --lock-minutes=15
```

**Options:**
- `--limit=200`: Maximum number of words to process
- `--batch=30`: Number of words per batch job
- `--languages=ru,uz`: Target languages (comma-separated)
- `--force`: Overwrite existing translations
- `--only-missing`: Only process words with missing translations (default: true)
- `--max-attempts=3`: Maximum GPT retry attempts
- `--lock-minutes=10`: Lock duration in minutes

**Selection Logic:**
- Filters: `is_active=1`, `language='en'`, lock expired, attempts < max
- Force mode: processes all eligible words
- Non-force mode: only words with missing translations or status=new/failed

#### `sentences:gpt-translate`

Translate sentences using GPT.

**Usage:**
```bash
# Basic usage
php artisan sentences:gpt-translate

# Custom settings
php artisan sentences:gpt-translate --limit=50 --batch=10 --languages=ru,uz
```

**Options:** Same as `words:gpt-enrich`

**Selection Logic:**
- Filters: lock expired, attempts < max, content not empty
- Force mode: processes all eligible sentences
- Non-force mode: only sentences with missing translations or status=new/failed

### E) JSON Contracts

**Words Request:**
```json
{
  "words": [
    {"id": 123, "word": "hello", "targets": ["ru", "uz"]},
    {"id": 124, "word": "world", "targets": ["ru", "uz", "az"]}
  ]
}
```

**Words Response:**
```json
{
  "items": [
    {"id": 123, "translations": {"ru": "привет", "uz": "salom"}},
    {"id": 124, "translations": {"ru": "мир", "uz": "dunyo", "az": "dünya"}}
  ]
}
```

**Sentences Request:**
```json
{
  "sentences": [
    {"id": 999, "en": "Hello world", "targets": ["ru", "uz"]}
  ]
}
```

**Sentences Response:**
```json
{
  "items": [
    {"id": 999, "translations": {"ru": "Привет мир", "uz": "Salom dunyo"}}
  ]
}
```

### F) System Prompts

**Key Rules:**
1. Output ONLY valid JSON (no markdown, no code blocks)
2. Uzbek MUST use Latin script (e.g., "salom", NOT "салом")
3. Natural, simple translations
4. Concise (words: 1-3 words, sentences: faithful but not verbose)
5. Only include requested target languages

## Usage Examples

### Docker Commands

All commands use Docker Compose:

```bash
# Run enrichment
docker compose exec -T php php artisan words:gpt-enrich --limit=50 --batch=10

# Translate sentences
docker compose exec -T php php artisan sentences:gpt-translate --limit=50 --batch=10

# Force mode (overwrite existing)
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --force

# Process queue jobs (if using database queue)
docker compose exec -T php php artisan queue:work --tries=3
```

### Acceptance Tests

**Test 1: Fill Missing Translations (No Duplicates)**
```bash
# Run command
docker compose exec -T php php artisan words:gpt-enrich --limit=50 --batch=10 --languages=ru,uz

# Verify: Check that translations were added
# Re-run: Should find 0 new candidates (all complete)
docker compose exec -T php php artisan words:gpt-enrich --limit=50 --batch=10 --languages=ru,uz
```

**Test 2: Force Mode (Overwrites Existing)**
```bash
# Run with force
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --batch=10 --languages=ru,uz --force

# Verify: Existing translations updated, no duplicate constraint violations
```

**Test 3: Sentence Translation**
```bash
# Translate sentences
docker compose exec -T php php artisan sentences:gpt-translate --limit=50 --batch=10 --languages=ru,uz

# Verify: Sentence translations created
```

**Test 4: Retry Logic**
```bash
# Artificially fail a word (set gpt_status='failed', gpt_attempts=1)
# Re-run command: Should retry the failed word
```

## Database Schema

**GPT Fields (added to both `words` and `word_sentences`):**
- `gpt_status` VARCHAR(20) DEFAULT 'new' - Status: new, processing, done, failed
- `gpt_enriched_at` DATETIME NULL - Completion timestamp
- `gpt_attempts` TINYINT UNSIGNED DEFAULT 0 - Retry counter
- `gpt_last_error` TEXT NULL - Last error message
- `gpt_lock_until` DATETIME NULL - Lock expiration
- `gpt_model` VARCHAR(50) NULL - Model used (e.g., 'gpt-4o-mini')

**Indexes:**
- `idx_words_gpt_status_lock` on (gpt_status, gpt_lock_until)
- `idx_words_gpt_enriched_at` on (gpt_enriched_at)
- `idx_word_sentences_gpt_status_lock` on (gpt_status, gpt_lock_until)

**Unique Constraints:**
- `word_translations`: UNIQUE(word_id, language)
- `word_sentence_translations`: UNIQUE(word_sentence_id, language)

## Monitoring & Logging

**Log Locations:**
- Command start/end: `storage/logs/laravel.log`
- Job execution: `storage/logs/laravel.log`
- OpenAI API calls: `storage/logs/laravel.log`

**Log Events:**
- Command: candidates count, jobs dispatched
- Job: items processed, translations filled/skipped, failures
- OpenAI: batch size, parse failures, retries

**Monitoring Queries:**
```sql
-- Check enrichment progress
SELECT gpt_status, COUNT(*) FROM words WHERE language='en' GROUP BY gpt_status;

-- Check failed words
SELECT id, word, gpt_attempts, gpt_last_error FROM words WHERE gpt_status='failed';

-- Check locked words
SELECT id, word, gpt_lock_until FROM words WHERE gpt_lock_until > NOW();
```

## Troubleshooting

**Issue: No candidates found**
- Check `gpt_status` values (may all be 'done')
- Check `gpt_lock_until` (may be locked)
- Check `gpt_attempts` (may have exceeded max)
- Use `--force` to reprocess

**Issue: Duplicates error**
- Should not happen (UNIQUE constraint)
- If occurs, check job logic for race conditions

**Issue: Uzbek in Cyrillic**
- Check OpenAI response
- Verify system prompt is being used
- May need to add explicit validation

**Issue: Timeouts**
- Reduce `--batch` size
- Increase `OPENAI_TIMEOUT`
- Check network/API status

**Issue: Rate limits (429)**
- Reduce batch size
- Increase backoff base
- Wait for quota reset

## Safety Features

1. **Idempotency**: Jobs can be retried safely
2. **Row Locking**: Prevents race conditions
3. **Lock Expiration**: Prevents stuck locks
4. **Max Attempts**: Prevents infinite retries
5. **Force Flag**: Explicit opt-in for overwrites
6. **UNIQUE Constraints**: Database-level duplicate prevention
7. **Transaction Rollback**: On failure, no partial updates
8. **Error Truncation**: Prevents oversized error messages

## Performance Considerations

- **Batch Size**: 30 items per API call (configurable)
- **Concurrency**: Queue workers can run in parallel
- **Lock Duration**: 10 minutes default (adjustable)
- **Rate Limits**: Exponential backoff handles 429s
- **Database Indexes**: Optimize candidate selection

## Next Steps

1. Set `QUEUE_CONNECTION=database` in `.env` for async processing
2. Run queue workers: `php artisan queue:work`
3. Monitor logs for errors
4. Adjust batch sizes based on performance
5. Set up cron for scheduled enrichment
6. Add monitoring/alerting for failed jobs

## Files Changed/Created

**Created:**
- `config/openai.php`
- `app/Services/OpenAi/OpenAiClient.php`
- `app/Jobs/EnrichWordsBatchJob.php`
- `app/Jobs/TranslateSentencesBatchJob.php`
- `app/Console/Commands/GptEnrichWords.php`
- `app/Console/Commands/GptTranslateSentences.php`

**Modified:**
- `.env.example` (added OPENAI_API_KEY)
- `app/Models/Words.php` (added GPT fields to fillable)
- `app/Models/WordSentences.php` (added GPT fields to fillable)

**Existing (used):**
- `database/migrations/2026_02_10_213738_alter_word_and_sentences_tables.php`
- `app/Models/WordTranslation.php`
- `app/Models/WordSentenceTranslation.php`

