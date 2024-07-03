<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWordMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'word_id',
        'is_known',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function word()
    {
        return $this->belongsTo(Words::class);
    }
}
