<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Words;
use Illuminate\Http\Request;
use App\Models\GameSession;
use App\Http\Controllers\Controller;

class GameController extends Controller
{
    public function getActiveSessions(Request $request)
    {
        $user = auth()->user();

        $session = GameSession::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                ->orWhere('player2_id', $user->id);
        })->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->with(['player1', 'player2']) // Подгружаем данные пользователей
            ->first();

        $query = Words::with('sentences')->inRandomOrder();

        $words = $query->limit(5)->get();

        if ($session) {
            return response()->json([
                'id' => $session->id,
                'status' => $session->status,
                'game_status' => $session->game_status,
                'player1' => $session->player1,
                'player2' => $session->player2,
                'created_at' => $session->created_at,
                'updated_at' => $session->updated_at,
                'words' => $words,
            ], 200);
        } else {
            return response()->json(['message' => 'No active game session found'], 404);
        }
    }

    public function getGameHistory(Request $request)
    {
        $user = auth()->user();

        $history = $user->gameHistories()->with('session')->get();

        return response()->json($history, 200);
    }

    public function createGame(Request $request)
    {
        $user = auth()->user();
        $opponentId = $request->input('opponent_id');

        if ($user->hasActiveGame()) {
            return response()->json(['error' => 'You already have an active game'], 400);
        }

        $session = GameSession::create([
            'player1_id' => $user->id,
            'player2_id' => $opponentId,
            'status' => 'pending',
            'game_status' => 'pending',
        ]);

        return response()->json(['message' => 'Game created successfully'], 201);
    }

    public function acceptGame($sessionId)
    {
        $session = GameSession::findOrFail($sessionId);
        $user = auth()->user();

        if ($session->player2_id !== $user->id) {
            return response()->json(['error' => 'You are not authorized to accept this game'], 403);
        }

        $session->update([
            'status' => 'active',
            'game_status' => 'accepted',
        ]);

        return response()->json(['message' => 'Game accepted and started'], 200);
    }

    public function declineGame($sessionId)
    {
        $session = GameSession::findOrFail($sessionId);
        $user = auth()->user();

        if ($session->player2_id !== $user->id) {
            return response()->json(['error' => 'You are not authorized to decline this game'], 403);
        }

        $session->delete();

        return response()->json(['message' => 'Game declined'], 200);
    }
}
