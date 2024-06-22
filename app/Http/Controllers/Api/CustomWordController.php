<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomWord;
use App\Models\CustomWordList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomWordController extends Controller
{
    public function index($listId)
    {
        $user = Auth::user();
        $customWordList = CustomWordList::where('user_id', $user->id)->where('id', $listId)->first();

        if (!$customWordList) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $customWords = CustomWord::where('list_id', $listId)->get();
        return response()->json($customWords);
    }

    public function store(Request $request, $listId)
    {
        $request->validate([
            'custom_word' => 'nullable|string|max:255',
            'custom_translation' => 'nullable|string|max:255',
            'custom_pronunciation' => 'nullable|string|max:255',
            'custom_example_sentence' => 'nullable|string',
            'word_id' => 'nullable|exists:words,id',
        ]);

        $user = Auth::user();
        $customWordList = CustomWordList::where('user_id', $user->id)->where('id', $listId)->first();

        if (!$customWordList) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $customWord = new CustomWord();
        $customWord->list_id = $listId;
        $customWord->word_id = $request->word_id;
        $customWord->custom_word = $request->custom_word;
        $customWord->custom_translation = $request->custom_translation;
        $customWord->custom_pronunciation = $request->custom_pronunciation;
        $customWord->custom_example_sentence = $request->custom_example_sentence;
        $customWord->save();

        return response()->json($customWord, 201);
    }

    public function show($listId, $id)
    {
        $user = Auth::user();
        $customWord = CustomWord::whereHas('customWordList', function ($query) use ($user, $listId) {
            $query->where('user_id', $user->id)->where('id', $listId);
        })->where('id', $id)->first();

        if (!$customWord) {
            return response()->json(['message' => 'Word not found'], 404);
        }

        return response()->json($customWord);
    }

    public function update(Request $request, $listId, $id)
    {
        $request->validate([
            'custom_word' => 'nullable|string|max:255',
            'custom_translation' => 'nullable|string|max:255',
            'custom_pronunciation' => 'nullable|string|max:255',
            'custom_example_sentence' => 'nullable|string',
            'word_id' => 'nullable|exists:words,id',
        ]);

        $user = Auth::user();
        $customWord = CustomWord::whereHas('customWordList', function ($query) use ($user, $listId) {
            $query->where('user_id', $user->id)->where('id', $listId);
        })->where('id', $id)->first();

        if (!$customWord) {
            return response()->json(['message' => 'Word not found'], 404);
        }

        $customWord->word_id = $request->word_id;
        $customWord->custom_word = $request->custom_word;
        $customWord->custom_translation = $request->custom_translation;
        $customWord->custom_pronunciation = $request->custom_pronunciation;
        $customWord->custom_example_sentence = $request->custom_example_sentence;
        $customWord->save();

        return response()->json($customWord);
    }

    public function destroy($listId, $id)
    {
        $user = Auth::user();
        $customWord = CustomWord::whereHas('customWordList', function ($query) use ($user, $listId) {
            $query->where('user_id', $user->id)->where('id', $listId);
        })->where('id', $id)->first();

        if (!$customWord) {
            return response()->json(['message' => 'Word not found'], 404);
        }

        $customWord->delete();

        return response()->json(['message' => 'Word deleted']);
    }
}
