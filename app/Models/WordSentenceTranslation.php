<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordSentenceTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'word_sentence_id',
        'language',
        'translation',
    ];

    public function wordSentence(): BelongsTo
    {
        return $this->belongsTo(WordSentences::class);
    }
}
