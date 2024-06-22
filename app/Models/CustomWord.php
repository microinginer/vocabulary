<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'word_id',
        'custom_word',
        'custom_translation',
        'custom_pronunciation',
        'custom_example_sentence',
    ];

    public function customWordList()
    {
        return $this->belongsTo(CustomWordList::class, 'list_id');
    }

    public function word()
    {
        return $this->belongsTo(Words::class, 'word_id');
    }
}
