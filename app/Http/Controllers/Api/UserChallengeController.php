<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\UserChallenge;
use Illuminate\Http\Request;

class UserChallengeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Получаем челленджи пользователя вместе с данными о челлендже
        $userChallenges = $user->userChallenges()->with('challenge')->get();

        // Форматируем данные для ответа
        $formattedChallenges = $userChallenges->map(function ($userChallenge) {
            return [
                'id' => $userChallenge->challenge->id,
                'name' => $userChallenge->challenge->name,
                'type' => $userChallenge->challenge->type,
                'goal' => $userChallenge->challenge->goal,
                'progress' => $userChallenge->progress,
                'reward' => $userChallenge->challenge->reward,
            ];
        });

        return response()->json($formattedChallenges);
    }

    public function updateChallengeProgress(Request $request)
    {
        $userId = $request->user()->id;
        $challengeId = $request->challenge_id;
        $progress = $request->progress;

        $userChallenge = UserChallenge::where('user_id', $userId)
            ->where('challenge_id', $challengeId)
            ->first();

        if ($userChallenge) {
            $userChallenge->progress += $progress;
            $userChallenge->save();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Challenge not found'], 404);
    }
}
