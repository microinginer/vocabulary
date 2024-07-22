<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserChallenge;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Google\Client as GoogleClient;

class AuthController extends Controller
{
    public function handleGoogleCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        $client = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);  // Укажите ваш Google Client ID
        $payload = $client->verifyIdToken($request->token);

        if ($payload) {
            $email = $payload['email'];
            $name = $payload['name'];
            $avatar = $payload['picture'];

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make(uniqid()), 'avatar' => $avatar]
            );

            // Если пользователь уже существует, обновляем аватарку
            if (!$user->wasRecentlyCreated && $user->avatar !== $avatar) {
                $user->avatar = $avatar;
                $user->save();
            }

            $token = $user->createToken('authToken')->plainTextToken;

            if ($user->wasRecentlyCreated) {
                $this->createInitialChallenges($user);
            }

            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }

    private function createInitialChallenges(User $user): void
    {
        $dailyChallenges = Challenge::where('type', 'daily')->inRandomOrder()->take(1)->get();
        $weeklyChallenges = Challenge::where('type', 'weekly')->inRandomOrder()->take(1)->get();

        foreach ($dailyChallenges as $challenge) {
            UserChallenge::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'progress' => 0,
                'completed' => 0,
            ]);
        }

        foreach ($weeklyChallenges as $challenge) {
            UserChallenge::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'progress' => 0,
                'completed' => 0,
            ]);
        }
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
