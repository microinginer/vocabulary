<?php

namespace App\Services;

use App\Models\User;
use App\Models\Challenge;
use App\Models\UserChallenge;

class ChallengeService
{
    public function assignChallengesToUser(User $user)
    {
        $challenges = Challenge::all();
        foreach ($challenges as $challenge) {
            UserChallenge::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
            ]);
        }
    }

    public function updateChallengeProgress(User $user, $challengeType)
    {
        $userChallenges = UserChallenge::where('user_id', $user->id)
            ->whereHas('challenge', function ($query) use ($challengeType) {
                $query->where('type', $challengeType);
            })
            ->get();

        foreach ($userChallenges as $userChallenge) {
            $userChallenge->progress++;
            if ($userChallenge->progress >= $userChallenge->challenge->goal) {
                $userChallenge->completed = 1;
            }
            $userChallenge->save();
        }
    }
}
