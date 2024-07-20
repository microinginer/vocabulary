<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SetUserPassword extends Command
{
    protected $signature = 'user:set-password {userId} {password}';
    protected $description = 'Set a new password for a user';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userId = $this->argument('userId');
        $password = $this->argument('password');

        // Поиск пользователя по ID
        $user = User::find($userId);

        if (!$user) {
            $this->error('User not found.');
            return;
        }

        // Установка нового пароля пользователю
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password has been set for user with ID {$userId}.");
    }
}
