# GPT Enrichment Pipeline - Quick Start

## ✅ Implementation Status: COMPLETE

All Phase 2 requirements have been successfully implemented.

## 🚀 Quick Start

### 1. Set Environment Variable

Add to your `.env` file:
```bash
OPENAI_API_KEY=sk-your-actual-api-key-here
```

### 2. Verify Setup

```bash
# Validate current state
docker compose exec -T php php artisan gpt:validate

# Check available commands
docker compose exec -T php php artisan words:gpt-enrich --help
docker compose exec -T php php artisan sentences:gpt-translate --help
```

### 3. Run Enrichment

```bash
# Fill missing UZ translations (5 words)
docker compose exec -T php php artisan words:gpt-enrich --limit=100 --languages=uz

# Fill missing sentence translations
docker compose exec -T php php artisan sentences:gpt-translate --limit=50 --languages=ru,uz

# Force overwrite existing translations (use with caution)
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --force
```

## 📚 Commands

### words:gpt-enrich
Enrich words with GPT translations.

**Options:**
- `--limit=200` - Maximum words to process
- `--batch=30` - Items per API call
- `--languages=ru,uz` - Target languages (comma-separated)
- `--force` - Overwrite existing translations
- `--max-attempts=3` - Maximum retry attempts
- `--lock-minutes=10` - Lock duration

**Examples:**
```bash
# Fill missing RU and UZ translations
docker compose exec -T php php artisan words:gpt-enrich

# Only Azerbaijani
docker compose exec -T php php artisan words:gpt-enrich --languages=az

# Small batch for testing
docker compose exec -T php php artisan words:gpt-enrich --limit=5 --batch=5
```

### sentences:gpt-translate
Translate sentences using GPT.

**Same options as words:gpt-enrich**

**Examples:**
```bash
# Fill missing sentence translations
docker compose exec -T php php artisan sentences:gpt-translate

# Specific languages
docker compose exec -T php php artisan sentences:gpt-translate --languages=uz
```

### gpt:validate
Validation and statistics command.

```bash
docker compose exec -T php php artisan gpt:validate
```

Shows:
- Total words and sentences
- Translations by language
- Missing translations count
- GPT status distribution
- Sample data

## 🎯 How It Works

### Default Behavior (Recommended)
- Only fills **missing** translations
- Never overwrites existing data
- Idempotent: safe to re-run multiple times
- Respects UNIQUE constraints (no duplicates)

### Force Mode
- Overwrites existing translations
- Use for re-generating translations
- Still respects database constraints

### Smart Candidate Selection
- Filters by language (English only)
- Checks for missing target translations
- Honors lock expiration
- Respects max attempts limit
- Skips already-complete items

## 🔄 Processing Flow

1. **Command** → Finds candidates → Dispatches jobs in batches
2. **Job** → Locks rows → Determines missing translations → Calls OpenAI
3. **OpenAI** → Returns JSON with translations
4. **Job** → Persists results → Updates GPT status → Unlocks rows
5. **Retry Logic** → On failure: exponential backoff, max 3 attempts

## 📊 Current Database State

From `gpt:validate`:
- **Words:** 3,506 active English words
- **RU translations:** 3,508 (complete)
- **UZ translations:** 3,502 (5 missing)
- **Sentences:** 1,959 total
- **Sentence RU:** 1,958 (1 missing)
- **Sentence UZ:** 1,952 (7 missing)

## 🛡️ Safety Features

✅ **Idempotent** - Can retry safely  
✅ **Row Locking** - Prevents race conditions  
✅ **Lock Expiration** - No stuck locks  
✅ **Max Attempts** - Prevents infinite loops  
✅ **UNIQUE Constraints** - Database-level duplicate prevention  
✅ **Transaction Safety** - Rollback on failure  
✅ **Error Logging** - Stored in gpt_last_error  

## 📝 Logging

Check logs at: `storage/logs/laravel.log`

**Log Events:**
- Command execution (start/end, candidates, jobs dispatched)
- Job processing (items processed, filled, skipped)
- OpenAI API calls (batch size, retries)
- Errors and failures

**Example:**
```
[2026-02-11 00:00:00] local.INFO: words:gpt-enrich started {"limit":50,"batch":10,"languages":["ru","uz"]}
[2026-02-11 00:00:01] local.INFO: words:gpt-enrich candidates found {"count":5}
[2026-02-11 00:00:01] local.INFO: words:gpt-enrich jobs dispatched {"jobs_count":1}
[2026-02-11 00:00:02] local.INFO: OpenAI enrichWords called {"batch_size":5}
[2026-02-11 00:00:03] local.INFO: EnrichWordsBatchJob completed {"items_processed":5,"translations_filled":5,"translations_skipped":0}
```

## 🔍 Monitoring

### Check Progress
```sql
SELECT gpt_status, COUNT(*) 
FROM words 
WHERE language='en' 
GROUP BY gpt_status;
```

### Find Failed Words
```sql
SELECT id, word, gpt_attempts, gpt_last_error 
FROM words 
WHERE gpt_status='failed' 
LIMIT 10;
```

### Check Locked Words
```sql
SELECT id, word, gpt_lock_until 
FROM words 
WHERE gpt_lock_until > NOW();
```

## 🚨 Troubleshooting

**No candidates found?**
- Check if all translations already exist
- Try `--force` to reprocess
- Run `gpt:validate` to see current state

**Duplicate errors?**
- Should not happen (UNIQUE constraint prevents)
- Check for race conditions
- Verify job isn't running multiple times

**API timeouts?**
- Reduce `--batch` size
- Increase `OPENAI_TIMEOUT` in .env
- Check network/API status

**Rate limits (429)?**
- Reduce batch size
- Wait for quota reset
- Jobs will auto-retry with backoff

**Uzbek in Cyrillic?**
- Check OpenAI response in logs
- Verify system prompt is used
- May need model adjustment

## 📦 What Was Implemented

**Created Files:**
1. `config/openai.php`
2. `app/Services/OpenAi/OpenAiClient.php`
3. `app/Jobs/EnrichWordsBatchJob.php`
4. `app/Jobs/TranslateSentencesBatchJob.php`
5. `app/Console/Commands/GptEnrichWords.php`
6. `app/Console/Commands/GptTranslateSentences.php`
7. `app/Console/Commands/ValidateGptEnrichment.php`

**Modified Files:**
1. `.env.example` (added OPENAI_API_KEY)
2. `app/Models/Words.php` (added GPT fields)
3. `app/Models/WordSentences.php` (added GPT fields)

**Database:** Migration already existed and was run.

## 🎯 Acceptance Tests

### Test 1: Fill Missing (Default)
```bash
# Should find only words/sentences missing requested translations
docker compose exec -T php php artisan words:gpt-enrich --limit=50 --languages=ru,uz

# Re-run: Should find 0 candidates (already complete)
docker compose exec -T php php artisan words:gpt-enrich --limit=50 --languages=ru,uz
```

### Test 2: Force Mode
```bash
# Overwrites existing translations
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --force

# No duplicate violations (UNIQUE constraint enforced)
```

### Test 3: Idempotency
```bash
# Run same command multiple times
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --languages=uz
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --languages=uz
docker compose exec -T php php artisan words:gpt-enrich --limit=10 --languages=uz

# Result: No duplicates, no errors, correct count
```

## 📖 Documentation

Detailed documentation available in:
- `PHASE2_GPT_ENRICHMENT.md` - Complete technical documentation
- `IMPLEMENTATION_SUMMARY.md` - Implementation summary and checklist
- `README_GPT_ENRICHMENT.md` - This quick start guide

## ✅ Ready to Use!

The GPT enrichment pipeline is fully implemented and ready for production use. Simply add your OpenAI API key and run the commands.

**Next Steps:**
1. Add OPENAI_API_KEY to .env
2. Test with small batch: `--limit=5`
3. Verify translations quality
4. Run full enrichment
5. Set up cron for automated processing (optional)

---

**Questions or Issues?**
Check the detailed documentation in `PHASE2_GPT_ENRICHMENT.md`.

