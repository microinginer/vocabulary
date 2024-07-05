<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|max:1|in:F,M',
            'birth_date' => 'nullable|date',
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }
}
