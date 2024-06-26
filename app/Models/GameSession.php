<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'player1_id',
        'player2_id',
        'status',
        'game_status',
        'is_player1_finished',
        'is_player2_finished',
    ];

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function histories()
    {
        return $this->hasMany(GameHistory::class, 'session_id');
    }

    public function answers()
    {
        return $this->hasMany(GameAnswer::class, 'game_session_id');
    }

    public function player1CorrectAnswersCount()
    {
        return $this->answers()->where('user_id', $this->player1_id)->where('is_correct', true)->count();
    }

    public function player2CorrectAnswersCount()
    {
        return $this->answers()->where('user_id', $this->player2_id)->where('is_correct', true)->count();
    }

    public function isWinner($playerId)
    {
        if ($playerId === $this->player1_id) {
            return $this->player1CorrectAnswersCount() > $this->player2CorrectAnswersCount();
        }

        return $this->player2CorrectAnswersCount() > $this->player1CorrectAnswersCount();
    }
}
