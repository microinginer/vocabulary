<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getOnlineUsers(Request $request): JsonResponse
    {
        $currentUserId = Auth::id();

        $onlineUsers = User::query()->where('is_online', true)
            ->where('id', '<>', $currentUserId)
            ->get()
            ->reject(function ($user) {
                return $user->hasActiveGame();
            })
            ->shuffle()
            ->take(20);

        return response()->json($onlineUsers);
    }

    public function getAllUsers(Request $request): JsonResponse
    {
        $users = User::query()->withCount(['gameAnswers' => function ($query) {
            $query->where('is_correct', true);
        }])
            ->orderBy('game_answers_count', 'desc')
            ->paginate(10);

        return response()->json($users);
    }
}
