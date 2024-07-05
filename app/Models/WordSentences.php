<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WordSentences extends Model
{
    use HasFactory;

    protected $table = 'word_sentences';

    protected $fillable = [
        'content_translate',
        'content',
        'words_id',
    ];

    public function word(): BelongsTo
    {
        return $this->belongsTo(Words::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WordSentenceTranslation::class, 'word_sentence_id');
    }
}
