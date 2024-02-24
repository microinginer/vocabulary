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
    ];

    public function sentences(): HasMany
    {
        return $this->hasMany(WordSentences::class);
    }
}
