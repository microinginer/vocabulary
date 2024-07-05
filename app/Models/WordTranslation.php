<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'word_id',
        'language',
        'translation',
    ];

    public function word(): BelongsTo
    {
        return $this->belongsTo(Words::class, 'word_id');
    }
}
