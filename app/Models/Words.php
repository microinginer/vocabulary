<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Words extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'translate',
        'language',
        'is_active',
        'length',
        'pronunciation',
        'difficulty_level',
        'gpt_status',
        'gpt_enriched_at',
        'gpt_attempts',
        'gpt_last_error',
        'gpt_lock_until',
        'gpt_model',
    ];

    public function sentences(): HasMany
    {
        return $this->hasMany(WordSentences::class);
    }
    public function customWords(): HasMany
    {
        return $this->hasMany(CustomWord::class, 'word_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WordTranslation::class, 'word_id');
    }
}
