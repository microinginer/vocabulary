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
        // Define difficulty levels
        $difficultyLevels = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
        ];

        $query = Words::with('sentences')->inRandomOrder();

        // Filter by difficulty level if provided
        if ($request->has('difficulty_level')) {
            $difficultyLevel = $request->input('difficulty_level');
            $query->where('difficulty_level', $difficultyLevel);
        }

        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Get word IDs that the user marked as known
            $knownWordIds = UserWordMark::where('user_id', $user->id)
                ->where('is_known', true)
                ->pluck('word_id')
                ->toArray();

            // Exclude these words from the query
            $query->whereNotIn('id', $knownWordIds);
        }

        // Get 10 random words with their sentences from the database
        $words = $query->limit(5)->get();

        // Check if words were found
        if ($words->isEmpty()) {
            return response()->json(['message' => 'No words found'], 404);
        }

        // Return the words and difficulty levels as a JSON response
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

        $query = Words::with('sentences')->has('sentences', '>=', 2)->has('sentences', '<=', 2)->inRandomOrder();

        $words = $query->limit(10)->get();

        if ($words->isEmpty()) {
            return response()->json(['message' => 'No words found'], 404);
        }

        return response()->json([
            'words' => $words,
            'difficultyLevels' => $difficultyLevels,
        ]);
    }
}
