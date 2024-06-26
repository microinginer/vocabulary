<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameAnswer extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'game_session_id',
        'word_id',
        'word_sentence_id',
        'is_correct',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function word()
    {
        return $this->belongsTo(Words::class);
    }

    public function wordSentence()
    {
        return $this->belongsTo(WordSentences::class);
    }
}
