<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AssignUserRole extends Command
{
    protected $signature = 'user:assign-role {userId} {role}';
    protected $description = 'Assign a role to a user';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userId = $this->argument('userId');
        $role = $this->argument('role');

        // Валидация роли
        if (!in_array($role, ['admin', 'user'])) {
            $this->error('Invalid role. Valid roles are: admin, user.');
            return;
        }

        // Поиск пользователя по ID
        $user = User::find($userId);

        if (!$user) {
            $this->error('User not found.');
            return;
        }

        // Назначение роли пользователю
        $user->role = $role;
        $user->save();

        $this->info("Role '{$role}' assigned to user with ID {$userId}.");
    }
}
