<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\UserChallenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function addChallenge(Request $request)
    {
        $user = Auth::user();
        $challengeId = $request->input('challenge_id');

        // Проверка, существует ли уже челлендж у пользователя
        if (UserChallenge::where('user_id', $user->id)->where('challenge_id', $challengeId)->exists()) {
            return response()->json(['message' => 'Challenge already added'], 400);
        }

        $challenge = UserChallenge::create([
            'user_id' => $user->id,
            'challenge_id' => $challengeId,
            'progress' => 0,
            'completed' => 0,
        ]);

        return response()->json([
            'id' => $challenge->challenge->id,
            'name' => $challenge->challenge->name,
            'type' => $challenge->challenge->type,
            'goal' => $challenge->challenge->goal,
            'progress' => $challenge->progress,
            'reward' => $challenge->challenge->reward,
        ]);
    }

    // Удаление челленджа у пользователя
    public function removeChallenge($id)
    {
        $user = Auth::user();

        $userChallenge = UserChallenge::where('user_id', $user->id)->where('challenge_id', $id)->first();
        if (!$userChallenge) {
            return response()->json(['message' => 'Challenge not found'], 404);
        }

        $userChallenge->delete();

        return response()->json($userChallenge);
    }
}
