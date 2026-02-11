# Sentence Generation Feature - Implementation Guide

## Overview

This feature generates missing English example sentences for vocabulary words using GPT. It ensures each English word has at least N example sentences (default N=2).

## Components

### A) OpenAI Client Enhancement

**Modified:** `app/Services/OpenAi/OpenAiClient.php`

Added new method:
- `generateSentences(array $payload): array`

**Payload Format:**
```json
{
  "words": [
    {"id": 123, "word": "apple", "needed": 2},
    {"id": 124, "word": "chair", "needed": 1}
  ]
}
```

**Response Format:**
```json
{
  "items": [
    {"id": 123, "sentences_en": ["I ate an apple.", "Apples are healthy."]},
    {"id": 124, "sentences_en": ["Sit on the chair."]}
  ]
}
```

**System Prompt Rules:**
1. Output ONLY valid JSON, no markdown
2. Generate EXACTLY the number of sentences requested (see "needed" field)
3. Each sentence must be 6-12 words long
4. Each sentence MUST contain the exact word (case-insensitive)
5. Keep sentences simple, natural, and beginner-friendly
6. Avoid proper nouns and slang
7. Avoid duplicates and near-duplicates

### B) Job: GenerateSentencesBatchJob

**File:** `app/Jobs/GenerateSentencesBatchJob.php`

**Parameters:**
- `array $wordIds` - IDs of words to process
- `int $targetCount` - Target number of sentences per word (default: 2)
- `bool $force` - Force mode (default: false)
- `int $lockMinutes` - Lock duration (default: 10)
- `int $maxAttempts` - Max retry attempts (default: 3)

**Processing Logic:**

1. **Lock words** with status='processing', lock_until=now()+lockMinutes, attempts++
2. **Fetch current counts** using efficient single query:
   ```sql
   SELECT words.id, words.word, COUNT(word_sentences.id) as sentences_count
   FROM words
   LEFT JOIN word_sentences ON words.id = word_sentences.words_id
   WHERE words.id IN (...)
   GROUP BY words.id, words.word
   ```
3. **Determine needed count per word:**
   - `force=false`: needed = max(0, targetCount - current_count)
   - `force=true`: needed = targetCount (always generate)
4. **Call OpenAI** only for words where needed > 0
5. **Persist results:**
   - Insert into word_sentences with (words_id, content, content_translate=NULL)
   - Skip duplicates (case-insensitive comparison)
   - Skip near-duplicates within the same run
6. **Update GPT status:**
   - If word now has >= targetCount sentences: set gpt_status='done', gpt_enriched_at=now()
   - Clear lock: gpt_lock_until=null
7. **On failure:** set gpt_status='failed', store error, clear lock

**Key Features:**
- Idempotent (safe to retry)
- Duplicate detection (case-insensitive)
- Efficient batch processing
- Proper error handling and logging

### C) Artisan Command: sentences:gpt-generate

**File:** `app/Console/Commands/GptGenerateSentences.php`

**Usage:**
```bash
docker compose exec -T php php artisan sentences:gpt-generate [options]
```

**Options:**
- `--limit=200` - Maximum number of words to process
- `--batch=30` - Number of words per batch job
- `--target=2` - Minimum sentences per word
- `--force` - Generate target sentences for all words regardless of existing count
- `--max-attempts=3` - Maximum GPT attempts per word
- `--lock-minutes=10` - Lock duration in minutes

**Selection Logic:**
- `words.language='en'` AND `is_active=1`
- Lock expired: `gpt_lock_until IS NULL OR gpt_lock_until < NOW()`
- Attempts below max: `gpt_attempts < max-attempts`
- If `force=false`: only words where `sentence_count < target`
- **Ordering (IMPORTANT):** `ORDER BY (gpt_enriched_at IS NULL) DESC, gpt_enriched_at ASC, id ASC`
  - Prioritizes words never processed
  - Then oldest processed first
  - Ensures fair distribution, avoids always processing same items

**Behavior Examples:**
- Word with 0 sentences + target=2 → generates 2
- Word with 1 sentence + target=2 → generates 1
- Word with 2+ sentences + target=2 → generates 0 (skips)
- Word with 2 sentences + target=2 + force=true → generates 2 more (force mode)

### D) Validation Command

**File:** `app/Console/Commands/ValidateSentenceGeneration.php`

**Usage:**
```bash
docker compose exec -T php php artisan sentences:validate-generation
```

**Output:**
- Total active English words
- Distribution by sentence count (0, 1, 2+)
- Sample words with different sentence counts

## Current State (from validation)

- **3,506** total active English words
- **2,813** words with 0 sentences (need 2 each)
- **345** words with 1 sentence (need 1 each)
- **348** words with 2+ sentences (complete)

## Usage Examples

### Basic Usage

```bash
# Generate missing sentences for up to 200 words (default target: 2)
docker compose exec -T php php artisan sentences:gpt-generate

# Generate for specific number of words
docker compose exec -T php php artisan sentences:gpt-generate --limit=50

# Custom target (3 sentences per word)
docker compose exec -T php php artisan sentences:gpt-generate --target=3

# Force mode (generate target count for ALL words, even those with 2+)
docker compose exec -T php php artisan sentences:gpt-generate --limit=10 --force

# Small test batch
docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --batch=5
```

### Workflow Example

```bash
# 1. Check current state
docker compose exec -T php php artisan sentences:validate-generation

# 2. Generate missing sentences (first batch)
docker compose exec -T php php artisan sentences:gpt-generate --limit=100

# 3. Process queue (if using database queue)
docker compose exec -T php php artisan queue:work

# 4. Validate results
docker compose exec -T php php artisan sentences:validate-generation

# 5. Translate newly created sentences
docker compose exec -T php php artisan sentences:gpt-translate --limit=200 --languages=ru,uz
```

## Integration with Existing Pipeline

The sentence generation feature integrates seamlessly with the existing Phase 2 pipeline:

1. **Generate English sentences:** `sentences:gpt-generate`
2. **Translate sentences:** `sentences:gpt-translate` (from Phase 2)
3. Both use the same GPT fields (gpt_status, gpt_enriched_at, etc.)
4. Both follow the same patterns (jobs, batching, locking, logging)

## Acceptance Tests

### Test 1: Word with 0 Sentences
```bash
# Before: word has 0 sentences
docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --target=2

# After: word should have 2 sentences
# Verify: Check word_sentences table
```

### Test 2: Word with 1 Sentence
```bash
# Before: word has 1 sentence
docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --target=2

# After: word should have 2 sentences (1 existing + 1 new)
```

### Test 3: Word with 2+ Sentences
```bash
# Before: word has 2+ sentences
docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --target=2

# After: word should still have same count (skipped)
```

### Test 4: Idempotency
```bash
# Run multiple times
docker compose exec -T php php artisan sentences:gpt-generate --limit=10
docker compose exec -T php php artisan sentences:gpt-generate --limit=10
docker compose exec -T php php artisan sentences:gpt-generate --limit=10

# Result: Once all words reach target, command finds 0 candidates
# No duplicates created
```

### Test 5: Force Mode
```bash
# Word already has 2 sentences
docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --target=2 --force

# After: word should have 4 sentences (2 existing + 2 new)
```

### Test 6: Integration with Translation
```bash
# 1. Generate sentences
docker compose exec -T php php artisan sentences:gpt-generate --limit=20

# 2. Translate the newly created sentences
docker compose exec -T php php artisan sentences:gpt-translate --limit=50 --languages=ru,uz

# Result: Newly created sentences should have RU and UZ translations
```

## Logging

**Command logs:**
- Start with parameters
- Candidates found
- Jobs dispatched

**Job logs:**
- Words processed
- Sentences inserted
- Duplicates skipped
- Failures

**Example log output:**
```
[2026-02-11] local.INFO: sentences:gpt-generate started {"limit":50,"batch":10,"target":2,"force":false}
[2026-02-11] local.INFO: sentences:gpt-generate candidates found {"count":50}
[2026-02-11] local.INFO: sentences:gpt-generate jobs dispatched {"jobs_count":5}
[2026-02-11] local.INFO: OpenAI generateSentences called {"batch_size":10}
[2026-02-11] local.INFO: GenerateSentencesBatchJob completed {"words_processed":10,"sentences_inserted":18,"duplicates_skipped":2}
```

## Database Impact

**Tables Modified:**
- `word_sentences` - New rows inserted (words_id, content, content_translate=NULL)
- `words` - GPT fields updated (gpt_status, gpt_enriched_at, gpt_attempts, gpt_lock_until, gpt_model)

**No Schema Changes:** Uses existing tables and columns.

## Performance Considerations

- **Batch Size:** 30 words per API call (configurable)
- **Duplicate Check:** Case-insensitive comparison against existing sentences
- **Efficient Query:** Single query to fetch word counts
- **Ordering:** Ensures fair distribution across all words

## Safety Features

✅ **Idempotent** - Safe to re-run  
✅ **Duplicate Detection** - Case-insensitive  
✅ **Row Locking** - Prevents race conditions  
✅ **Lock Expiration** - No stuck locks  
✅ **Max Attempts** - Prevents infinite retries  
✅ **Transaction Safety** - Rollback on failure  
✅ **Sentence Validation** - Must contain the word  

## Files Created

1. `app/Jobs/GenerateSentencesBatchJob.php` - Batch job for sentence generation
2. `app/Console/Commands/GptGenerateSentences.php` - Artisan command
3. `app/Console/Commands/ValidateSentenceGeneration.php` - Validation helper

## Files Modified

1. `app/Services/OpenAi/OpenAiClient.php` - Added generateSentences() method

## Next Steps

1. **Set OPENAI_API_KEY** in `.env` (if not already set)
2. **Validate current state:**
   ```bash
   docker compose exec -T php php artisan sentences:validate-generation
   ```
3. **Test with small batch:**
   ```bash
   docker compose exec -T php php artisan sentences:gpt-generate --limit=5
   ```
4. **Run full generation:**
   ```bash
   docker compose exec -T php php artisan sentences:gpt-generate --limit=500
   ```
5. **Translate generated sentences:**
   ```bash
   docker compose exec -T php php artisan sentences:gpt-translate --limit=500
   ```

## Expected Results

After running `sentences:gpt-generate` with default settings:
- All 2,813 words with 0 sentences will have 2 sentences each
- All 345 words with 1 sentence will have 2 sentences each
- 348 words with 2+ sentences remain unchanged
- Total new sentences created: ~5,971 (2,813 × 2 + 345 × 1)

## Status: ✅ IMPLEMENTATION COMPLETE

All requirements have been implemented:
- ✅ GenerateSentencesBatchJob with idempotent processing
- ✅ sentences:gpt-generate command with all options
- ✅ Smart candidate selection with proper ordering
- ✅ Duplicate detection and prevention
- ✅ Force mode support
- ✅ Integration with existing pipeline
- ✅ Comprehensive logging
- ✅ No tests added (as requested)
- ✅ No DB changes (uses existing tables)

