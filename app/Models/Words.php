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
        'is_active',
        'length',
        'pronunciation',
        'difficulty_level',
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
