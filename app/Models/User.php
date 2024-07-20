<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_online',
        'role',
        'gender',
        'birth_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function customWordLists(): HasMany
    {
        return $this->hasMany(CustomWordList::class);
    }
    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserChallenge::class);
    }

    public function updateOnlineStatus($status): void
    {
        $this->is_online = $status;
        $this->save();
    }
    public function gameSessionsAsPlayer1()
    {
        return $this->hasMany(GameSession::class, 'player1_id');
    }

    public function gameSessionsAsPlayer2()
    {
        return $this->hasMany(GameSession::class, 'player2_id');
    }

    public function gameHistories()
    {
        return $this->hasMany(GameHistory::class);
    }

    public function hasActiveGame()
    {
        return GameSession::where(function ($query) {
            $query->where('player1_id', $this->id)
                ->orWhere('player2_id', $this->id);
        })->whereIn('status', ['pending', 'active'])->exists();
    }

    public function gameAnswers()
    {
        return $this->hasMany(GameAnswer::class);
    }

    public function correctAnswersCount()
    {
        return $this->gameAnswers()->where('is_correct', true)->count();
    }
}
