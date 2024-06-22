<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserChallenge;
use Carbon\Carbon;

class ResetChallenges extends Command
{
    protected $signature = 'challenges:reset';
    protected $description = 'Reset progress of daily and weekly challenges';

    public function handle(): void
    {
        $now = Carbon::now();

        UserChallenge::whereHas('challenge', function ($query) {
            $query->where('type', 'daily');
        })->chunk(100, function ($dailyChallenges) use ($now) {
            foreach ($dailyChallenges as $userChallenge) {
                if (!$userChallenge->updated_at->isSameDay($now)) {
                    $this->resetUserChallenge($userChallenge);
                }
            }
        });

        UserChallenge::whereHas('challenge', function ($query) {
            $query->where('type', 'weekly');
        })->chunk(100, function ($weeklyChallenges) use ($now) {
            foreach ($weeklyChallenges as $userChallenge) {
                if (!$userChallenge->updated_at->isSameWeek($now)) {
                    $this->resetUserChallenge($userChallenge);
                }
            }
        });

        $this->info('Challenges reset successfully.');
    }

    private function resetUserChallenge($userChallenge): void
    {
        // Сброс прогресса челленджа для пользователя
        $userChallenge->progress = 0;
        $userChallenge->completed = 0;
        $userChallenge->save();
    }
}
