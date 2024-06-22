<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'goal',
    ];

    public function userChallenges()
    {
        return $this->hasMany(UserChallenge::class);
    }
}
