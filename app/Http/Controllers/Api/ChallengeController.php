<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\UserChallenge;
use Illuminate\Support\Facades\Auth;


class ChallengeController extends Controller
{
    public function index()
    {
        $challenges = Challenge::all();
        return response()->json($challenges);
    }
    public function updateProgressByMetrics(Request $request)
    {
        $user = Auth::user();
        $wordsFound = $request->input('words_found', 0);
        $successfulWordsPlaced = $request->input('successful_words_placed', 0);
        $successfulVictorina = $request->input('successful_victorina', 0);

        // Получаем все челленджи пользователя
        $userChallenges = UserChallenge::with('challenge')->where('user_id', $user->id)->get();

        foreach ($userChallenges as $userChallenge) {
            $challenge = $userChallenge->challenge;

            // Обновляем прогресс в зависимости от типа челленджа
            if ($challenge->sub_type == 'words_found') {
                $userChallenge->progress += $wordsFound;
            } elseif ($challenge->sub_type == 'successful_words_placed') {
                $userChallenge->progress += $successfulWordsPlaced;
            } elseif ($challenge->sub_type == 'successful_victorina') {
                $userChallenge->progress += $successfulVictorina;
            }

            // Проверка на завершение челленджа
            if ($userChallenge->progress >= $challenge->goal) {
                $userChallenge->completed = true;
                $userChallenge->progress = $challenge->goal; // Обеспечить, что прогресс не превышает цель
            }
            $userChallenge->save();
        }

        return response()->json(['status' => 'Progress updated successfully']);
    }
}
