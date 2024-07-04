<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\GameAnswer;
use App\Models\Words;
use Illuminate\Http\Request;
use App\Models\GameSession;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    public function getActiveSessions(Request $request)
    {
        $session = GameSession::query()->where('id',$request->get('session_id'))->first();
        $query = Words::with('sentences')->has('sentences', '>=', 2)->has('sentences', '<=', 2)->inRandomOrder();

        $words = $query->limit(5)->get();

        if ($session) {
            return response()->json([
                'id' => $session->id,
                'status' => $session->status,
                'game_status' => $session->game_status,
                'player1' => $session->player1,
                'player2' => $session->player2,
                'currentUser' => auth()->user(),
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

    public function resultGame(GameSession $session)
    {
        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'game_status' => $session->game_status,
            'player1' => array_merge($session->player1->toArray(), ['isWinner' => $session->isWinner($session->player1_id)]),
            'player2' => array_merge($session->player2->toArray(), ['isWinner' => $session->isWinner($session->player2_id)]),
            'currentUser' => auth()->user(),
            'player1_correct_answers' => $session->player1CorrectAnswersCount(),
            'player2_correct_answers' => $session->player2CorrectAnswersCount(),
        ]);
    }

    public function getUserGames(Request $request)
    {

        $user = Auth::user();
        $userId = $user->id;

        // Получаем все игровые сессии, в которых участвует пользователь, с пагинацией
        $games = GameSession::where('player1_id', $userId)
            ->orWhere('player2_id', $userId)
            ->with(['player1', 'player2'])
            ->orderBy('created_at', 'desc')
            ->paginate(20); // Вы можете изменить количество элементов на страницу

        // Формируем ответ с информацией о пользователях и очках
        $gameData = $games->map(function ($game) use ($userId) {
            $player1Score = GameAnswer::where('game_session_id', $game->id)
                ->where('user_id', $game->player1_id)
                ->where('is_correct', true)
                ->count();

            $player2Score = GameAnswer::where('game_session_id', $game->id)
                ->where('user_id', $game->player2_id)
                ->where('is_correct', true)
                ->count();

            return [
                'game_id' => $game->id,
                'created_at' => $game->created_at,
                'status' => $game->status,
                'game_status' => $game->game_status,
                'player1' => $game->player1,
                'player2' => $game->player2,
                'player1_score' => $player1Score,
                'player2_score' => $player2Score,
            ];
        });

        // Возвращаем данные с пагинацией
        return response()->json([
            'current_page' => $games->currentPage(),
            'data' => $gameData,
            'per_page' => $games->perPage(),
            'total' => $games->total(),
            'last_page' => $games->lastPage(),
        ]);
    }
}
