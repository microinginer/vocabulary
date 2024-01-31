<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Words extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'translate',
        'is_active',
    ];

    public function getSentences()
    {
        return $this->hasMany(WordSentences::class);
    }
}
