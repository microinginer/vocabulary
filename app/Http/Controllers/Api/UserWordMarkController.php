<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserWordMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserWordMarkController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = UserWordMark::where('user_id', $user->id)
            ->with(['word' => function($query) {
                $query->select('id', 'word','translate', 'pronunciation');
            }])
            ->join('words', 'user_word_marks.word_id', '=', 'words.id')
            ->select('user_word_marks.*')
            ->orderBy('words.word');

        if ($request->has('is_known')) {
            $query->where('is_known', $request->input('is_known'));
        }

        $wordMarks = $query->paginate(30);

        return response()->json($wordMarks);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $isKnown = $request->input('is_known', false);

        $request->validate([
            'word_id' => 'required|exists:words,id',
            'is_known' => 'boolean',
        ]);

        $currentMark = UserWordMark::where('user_id', $user->id)
            ->where('word_id', $request->word_id)
            ->first();

        $repeatCount = $currentMark ? $currentMark->repeat_count + 1 : 1;
        $isKnown = $currentMark ? (bool)$currentMark->is_known : $isKnown;

        $mark = UserWordMark::updateOrCreate(
            ['user_id' => $user->id, 'word_id' => $request->word_id],
            ['is_known' => $isKnown, 'repeat_count' => $repeatCount]
        );

        return response()->json($mark, 201);
    }
}
