<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomWordList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomWordListController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $customWordLists = CustomWordList::where('user_id', $user->id)->get();
        return response()->json($customWordLists);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $customWordList = new CustomWordList();
        $customWordList->user_id = $user->id;
        $customWordList->name = $request->name;
        $customWordList->save();

        return response()->json($customWordList, 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $customWordList = CustomWordList::where('user_id', $user->id)->where('id', $id)->first();

        if (!$customWordList) {
            return response()->json(['message' => 'List not found'], 404);
        }

        return response()->json($customWordList);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $customWordList = CustomWordList::where('user_id', $user->id)->where('id', $id)->first();

        if (!$customWordList) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $customWordList->name = $request->name;
        $customWordList->save();

        return response()->json($customWordList);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $customWordList = CustomWordList::where('user_id', $user->id)->where('id', $id)->first();

        if (!$customWordList) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $customWordList->delete();

        return response()->json(['message' => 'List deleted']);
    }
}
