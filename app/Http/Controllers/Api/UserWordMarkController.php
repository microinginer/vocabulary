<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserWordMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserWordMarkController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'word_id' => 'required|exists:words,id',
            'is_known' => 'required|boolean',
        ]);

        $mark = UserWordMark::updateOrCreate(
            ['user_id' => $user->id, 'word_id' => $request->word_id],
            ['is_known' => $request->is_known]
        );

        return response()->json($mark, 201);
    }
}
