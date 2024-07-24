<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AppleAuthController extends Controller
{
    public function login(Request $request)
    {
        $identityToken = $request->input('identityToken');
        $authorizationCode = $request->input('authorizationCode');
        $name = $request->input('displayName', 'Anonymous');

        // Проверка и декодирование identityToken
        $appleSignInPayload = $this->decodeIdentityToken($identityToken);

        if (!$appleSignInPayload) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $email = $appleSignInPayload['email'] ?? null;

        if (!$email) {
            return response()->json(['error' => 'Email not provided'], 400);
        }

        // Получение или создание пользователя
        $user = User::firstOrCreate(
            ['email' => $email],
            ['password' => Hash::make(uniqid()), 'name' => $name, 'avatar' => 'https://words.todevelop.ru/img/anon.png']
        );

        // Создание токена для пользователя
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    private function decodeIdentityToken($identityToken)
    {
        $appleKeyUrl = "https://appleid.apple.com/auth/keys";
        $appleKeys = json_decode(file_get_contents($appleKeyUrl), true);

        // Получение заголовка токена для получения `kid`
        $jwtHeader = json_decode(base64_decode(explode('.', $identityToken)[0]), true);

        $publicKey = null;
        foreach ($appleKeys['keys'] as $key) {
            if ($key['kid'] == $jwtHeader['kid']) {
                $publicKey = $key;
                break;
            }
        }

        if (!$publicKey) {
            return null;
        }

        $jwtDecoded = JWT::decode($identityToken, JWK::parseKeySet(['keys' => [$publicKey]]));

        return (array)$jwtDecoded;
    }
}
