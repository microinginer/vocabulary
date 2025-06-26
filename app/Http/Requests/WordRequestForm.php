<?php

namespace App\Http\Requests;

use App\Models\Words;
use Illuminate\Foundation\Http\FormRequest;

class WordRequestForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'word' => ['required'],
            'difficulty_level' => ['required', 'integer'],
            'is_active' => ['boolean'],
            'language' => 'required|in:en,fr,de,it',
            'translations' => 'array',
            'translations.ru' => 'nullable|string',
            'translations.uz' => 'nullable|string',
            'sentences' => 'array',
            'sentences.*.content' => 'required|string',
            'sentences.*.content_translate' => 'nullable|string',
            'sentences.*.translations' => 'array',
            'sentences.*.translations.*.language' => 'required|string',
            'sentences.*.translations.*.translation' => 'required|string',
        ];
    }

    public function save(Words $words): bool
    {
        return $words->fill($this->all())->save();
    }
}
