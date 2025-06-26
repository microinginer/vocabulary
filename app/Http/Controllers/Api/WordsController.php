<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserWordMark;
use App\Models\Words;
use App\Models\WordSentences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WordsController extends Controller
{
    public function getRandomWords(Request $request): JsonResponse
    {
        if ($request->bearerToken()) {
            Auth::shouldUse('sanctum');
        }

        // Define difficulty levels
        $difficultyLevels = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
        ];

        $language = $request->input('language', 'ru');
        $targetLanguage = $request->input('targetLanguage', 'en'); // Новый параметр

        if (!in_array($language, ['ru', 'uz', 'az'])) {
            return response()->json(['message' => 'Invalid language parameter. Valid options are: ru, uz, az.'], 400);
        }

        if (!in_array($targetLanguage, ['en', 'fr', 'de', 'it'])) {
            return response()->json(['message' => 'Invalid targetLanguage parameter. Valid options are: en, fr, de, it.'], 400);
        }

        $query = Words::with(['sentences' => function ($query) use ($language) {
            $query->inRandomOrder()->with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }]);
        }])
            ->with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }])
            ->has('sentences', '>=', 1)
            ->where('language', $targetLanguage)
            ->orderBy('id','desc');


        // Filter by difficulty level if provided
        if ($request->has('difficulty_level')) {
            $difficultyLevel = $request->input('difficulty_level');
            $query->where('difficulty_level', $difficultyLevel);
        }

        if (Auth::check()) {
            $user = Auth::user();

            $knownWordIds = UserWordMark::where('user_id', $user->id)
                ->where('is_known', true)
                ->pluck('word_id')
                ->toArray();

            $query->whereNotIn('id', $knownWordIds);
        }

        $words = $query->limit(7)->get();
        if ($words->isEmpty()) {
            return response()->json(['message' => 'No words found'], 404);
        }

        $words = $words->toArray();

        foreach ($words as $key => $word) {
            $words[$key]['sentences'] = array_slice($word['sentences'], 0, 2);
            $translation = $word['translations'][0]['translation'] ?? null;

            if ($translation) {
                $words[$key]['translate'] = $translation;
            }

            foreach ($words[$key]['sentences'] as $sentenceKey => $sentence) {
                $sentenceTranslation = $sentence['translations'][0]['translation'] ?? null;
                if ($sentenceTranslation) {
                    $words[$key]['sentences'][$sentenceKey]['content_translate'] = $sentenceTranslation;
                    unset($words[$key]['sentences'][$sentenceKey]['translations']);
                }
            }
        }

        return response()->json([
            'words' => $words,
            'difficultyLevels' => $difficultyLevels,
        ]);
    }

    public function getRandomSentences(Request $request): JsonResponse
    {
        $difficultyLevels = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
        ];

        $language = $request->input('language', 'ru');

        if (!in_array($language, ['ru', 'uz', 'az'])) {
            return response()->json(['message' => 'Invalid language parameter. Valid options are: ru, uz, az.'], 400);
        }

        $query = Words::with(['sentences' => function ($query) use ($language) {
            $query->inRandomOrder()->with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }]);
        }])
            ->with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }])
            ->has('sentences', '>=', 2)
            ->inRandomOrder();

        if ($request->has('difficulty_level')) {
            $difficultyLevel = $request->input('difficulty_level');
            $query->where('difficulty_level', $difficultyLevel);
        }

        $words = $query->limit(10)->get();

        if ($words->isEmpty()) {
            return response()->json(['message' => 'No words found'], 404);
        }


        $words = $words->toArray();

        foreach ($words as $key => $word) {
            $words[$key]['sentences'] = array_slice($word['sentences'], 0, 2);
            $translation = $word['translations'][0]['translation'] ?? null;
            foreach ($words[$key]['sentences'] as $sentenceKey => $sentence) {
                $sentenceTranslation = $sentence['translations'][0]['translation'] ?? null;
                if ($sentenceTranslation) {
                    $words[$key]['sentences'][$sentenceKey]['content_translate'] = $sentenceTranslation;
                    unset($words[$key]['sentences'][$sentenceKey]['translations']);
                }
            }

            if ($translation) {
                $words[$key]['translate'] = $translation;
            }
        }

        return response()->json([
            'words' => $words,
            'difficultyLevels' => $difficultyLevels,
        ]);
    }
}
