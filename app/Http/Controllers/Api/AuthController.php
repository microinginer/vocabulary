<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Google\Client as GoogleClient;

class AuthController extends Controller
{
    public function handleGoogleCallback(Request $request)
    {
        $client = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);  // Укажите ваш Google Client ID
        $payload = $client->verifyIdToken($request->token);

        if ($payload) {
            $email = $payload['email'];
            $name = $payload['name'];

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make(uniqid())]
            );

            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
