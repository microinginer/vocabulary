<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UserToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a user token for authentication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get id from input
        $userId = $this->ask('Enter user ID');
        // get user by id
        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error('User not found.');
            return;
        }
        // create token
        $token = $user->createToken('authToken')->plainTextToken;
        // output token
        $this->info("User token for user ID {$userId}: {$token}");
    }
}
