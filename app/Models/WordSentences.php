<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WordSentences extends Model
{
    use HasFactory;

    protected $table = 'word_sentences';

    protected $fillable = [
        'content_translate',
        'content',
        'word_id',
    ];

    public function getWord()
    {
        return $this->belongsTo(Words::class);
    }
}
