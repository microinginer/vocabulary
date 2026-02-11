# Sentence Generation Feature - Quick Reference

## 🎯 Purpose

Automatically generate English example sentences for vocabulary words using GPT, ensuring each word has at least N example sentences (default: 2).

## 🚀 Quick Start

### 1. Check Current State
```bash
docker compose exec -T php php artisan sentences:validate-generation
```

### 2. Generate Missing Sentences
```bash
# Generate for 100 words (default target: 2 sentences per word)
docker compose exec -T php php artisan sentences:gpt-generate --limit=100

# Generate with custom target (3 sentences per word)
docker compose exec -T php php artisan sentences:gpt-generate --limit=50 --target=3

# Small test batch
docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --batch=5
```

### 3. Translate Generated Sentences
```bash
# Translate to Russian and Uzbek
docker compose exec -T php php artisan sentences:gpt-translate --limit=200 --languages=ru,uz
```

## 📊 Current Statistics

From validation (Feb 11, 2026):
- **3,506** active English words total
- **2,813** words need 2 sentences (have 0)
- **345** words need 1 sentence (have 1)
- **348** words complete (have 2+)

## 📝 Commands

### sentences:gpt-generate
Generate missing example sentences.

**Options:**
```
--limit=200         Max words to process
--batch=30          Words per batch job
--target=2          Minimum sentences per word
--force             Generate target count for ALL words (even complete ones)
--max-attempts=3    Max GPT retry attempts
--lock-minutes=10   Lock duration
```

**Examples:**
```bash
# Basic usage (fills missing up to target=2)
docker compose exec -T php php artisan sentences:gpt-generate

# Custom target
docker compose exec -T php php artisan sentences:gpt-generate --target=3

# Force mode (adds target count to all words)
docker compose exec -T php php artisan sentences:gpt-generate --limit=10 --force
```

### sentences:validate-generation
Check statistics and current state.

```bash
docker compose exec -T php php artisan sentences:validate-generation
```

## 🔄 How It Works

**Default Behavior (without --force):**
- Word with 0 sentences + target=2 → generates 2 sentences
- Word with 1 sentence + target=2 → generates 1 sentence
- Word with 2+ sentences + target=2 → skips (already complete)

**Force Mode (with --force):**
- Always generates target count, regardless of existing sentences
- Word with 2 sentences + target=2 + force → generates 2 MORE (total becomes 4)

## 🎯 Sentence Quality Rules

GPT is instructed to:
- ✅ Generate 6-12 word sentences
- ✅ Include the word at least once (case-insensitive)
- ✅ Keep sentences simple and beginner-friendly
- ✅ Avoid proper nouns and slang
- ✅ Make sentences natural and practical
- ✅ Avoid duplicates

## 🔗 Integration with Translation Pipeline

**Complete Workflow:**
```bash
# 1. Generate English sentences
docker compose exec -T php php artisan sentences:gpt-generate --limit=100

# 2. Translate to multiple languages
docker compose exec -T php php artisan sentences:gpt-translate --limit=200 --languages=ru,uz

# 3. Validate results
docker compose exec -T php php artisan sentences:validate-generation
docker compose exec -T php php artisan gpt:validate
```

## 🛡️ Safety Features

- ✅ **Idempotent** - Safe to run multiple times
- ✅ **Duplicate Detection** - Case-insensitive check
- ✅ **Row Locking** - Prevents race conditions
- ✅ **Smart Ordering** - Unenriched words first
- ✅ **Transaction Safety** - Rollback on failure

## 📈 Expected Results

After running with default settings on all words:
- ~5,971 new English sentences created
- 2,813 words go from 0→2 sentences (5,626 new)
- 345 words go from 1→2 sentences (345 new)
- All words will have at least 2 example sentences

Then after translation:
- ~11,942 new translations (5,971 × 2 languages)

## 🔍 Troubleshooting

**No candidates found?**
```bash
# Check current state
docker compose exec -T php php artisan sentences:validate-generation

# If all words have 2+, they're complete!
# Use --force to generate more, or increase --target
```

**Want to add more sentences?**
```bash
# Increase target
docker compose exec -T php php artisan sentences:gpt-generate --target=3

# Or use force mode
docker compose exec -T php php artisan sentences:gpt-generate --force
```

**Sentences don't contain the word?**
- GPT is instructed to include the word
- System prompt enforces this rule
- If issue persists, check OpenAI response in logs

## 📦 Files

**Created:**
- `app/Jobs/GenerateSentencesBatchJob.php`
- `app/Console/Commands/GptGenerateSentences.php`
- `app/Console/Commands/ValidateSentenceGeneration.php`
- `SENTENCE_GENERATION.md` (detailed docs)
- `SENTENCE_GENERATION_SUMMARY.md` (implementation summary)

**Modified:**
- `app/Services/OpenAi/OpenAiClient.php` (added generateSentences method)

## 📚 Documentation

- **SENTENCE_GENERATION.md** - Complete technical documentation
- **SENTENCE_GENERATION_SUMMARY.md** - Implementation summary
- **README_SENTENCE_GENERATION.md** - This quick reference

## ✅ Status

**Implementation:** COMPLETE  
**Testing:** VERIFIED  
**Integration:** READY  

All requirements met, ready for production use! 🎉

---

**Need Help?**
Check the detailed documentation in `SENTENCE_GENERATION.md`

