<?php

namespace App\WebSockets;

use App\Models\GameHistory;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\GameSession;
use Illuminate\Support\Facades\Log;

class SocketServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        Log::info("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        Log::info("New Message ".$msg);

        if (isset($data->token)) {
            $token = PersonalAccessToken::findToken($data->token);
            if ($token) {
                $user = $token->tokenable;
                if ($user) {
                    $user->updateOnlineStatus(true);
                    Log::info("User {$user->id} is online.");
                    $this->broadcastStatusUpdate($user);
                    $from->user = $user;
                }
            }
        } elseif (isset($data->action)) {
            Log::info("New action ".$data->action);

            $this->handleAction($from, $data);
        }
    }

    protected function handleAction(ConnectionInterface $from, $data)
    {
        switch ($data->action) {
            case 'create_game':
                Log::info("Handle game".$data->action);
                $this->createGame($from, $data);
                break;
            case 'accept_game':
                $this->acceptGame($from, $data);
                break;
            case 'decline_game':
                $this->declineGame($from, $data);
                break;
            case 'end_game':
                $this->endGame($from, $data);
                break;
            default:
                Log::warning("Unknown action: {$data->action}");
        }
    }

    protected function createGame(ConnectionInterface $from, $data)
    {
        Log::info("Game created by user.");

        $user = $from->user;
        $opponentId = $data->opponent_id;

        if ($user->hasActiveGame()) {
            $from->send(json_encode(['error' => 'You already have an active game']));
            return;
        }

        $session = GameSession::create([
            'player1_id' => $user->id,
            'player2_id' => $opponentId,
            'status' => 'pending',
            'game_status' => 'pending',
        ]);

        Log::info("Game created by user {$user->id} with opponent {$opponentId}");

        $this->notifyUser($opponentId, [
            'type' => 'game_invite',
            'session_id' => $session->id,
            'from_user' => $user
        ]);
    }

    protected function acceptGame(ConnectionInterface $from, $data)
    {
        $session = GameSession::findOrFail($data->session_id);
        $user = $from->user;

        if ($session->player2_id !== $user->id) {
            $from->send(json_encode(['error' => 'You are not authorized to accept this game']));
            return;
        }

        $session->update([
            'status' => 'active',
            'game_status' => 'accepted',
        ]);

        Log::info("Game accepted by user {$user->id}");

        $this->notifyUser($session->player1_id, [
            'type' => 'game_accepted',
            'session_id' => $session->id
        ]);

        $from->send(json_encode([
            'type' => 'game_accepted',
            'session_id' => $session->id
        ]));
    }

    protected function declineGame(ConnectionInterface $from, $data)
    {
        $session = GameSession::findOrFail($data->session_id);
        $user = $from->user;

        if ($session->player2_id !== $user->id) {
            $from->send(json_encode(['error' => 'You are not authorized to decline this game']));
            return;
        }

        $session->delete();

        Log::info("Game declined by user {$user->id}");

        $this->notifyUser($session->player1_id, [
            'type' => 'game_declined',
            'session_id' => $session->id
        ]);

        $from->send(json_encode([
            'type' => 'game_declined',
            'session_id' => $session->id
        ]));
    }

    protected function notifyUser($userId, $message)
    {
        foreach ($this->clients as $client) {
            if (isset($client->user) && $client->user->id == $userId) {
                $client->send(json_encode($message));
            }
        }
    }

    protected function broadcastStatusUpdate($user)
    {
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'type' => 'status-update',
                'user' => $user
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        Log::info("Connection {$conn->resourceId} has disconnected");

        $user = isset($conn->user) ? $conn->user : null;
        if ($user) {
            $user->updateOnlineStatus(false);
            Log::info("User {$user->id} is offline.");
            $this->broadcastStatusUpdate($user);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    protected function endGame(ConnectionInterface $from, $data)
    {
        $session = GameSession::findOrFail($data->session_id);
        $user = $from->user;

        if ($session->player1_id !== $user->id && $session->player2_id !== $user->id) {
            $from->send(json_encode(['error' => 'You are not authorized to end this game']));
            return;
        }

        // Обновление статуса сессии
        $session->update([
            'status' => 'completed',
        ]);

        // Пример начисления очков (логика может быть иной)
        $score1 = $data->score1;
        $score2 = $data->score2;

        GameHistory::create([
            'user_id' => $session->player1_id,
            'session_id' => $session->id,
            'score' => $score1,
            'result' => $score1 > $score2 ? 'win' : ($score1 < $score2 ? 'lose' : 'draw'),
        ]);

        GameHistory::create([
            'user_id' => $session->player2_id,
            'session_id' => $session->id,
            'score' => $score2,
            'result' => $score2 > $score1 ? 'win' : ($score2 < $score1 ? 'lose' : 'draw'),
        ]);

        $this->notifyUser($session->player1_id, [
            'type' => 'game_completed',
            'session_id' => $session->id,
            'score1' => $score1,
            'score2' => $score2
        ]);

        $this->notifyUser($session->player2_id, [
            'type' => 'game_completed',
            'session_id' => $session->id,
            'score1' => $score1,
            'score2' => $score2
        ]);

        $from->send(json_encode([
            'type' => 'game_completed',
            'session_id' => $session->id,
            'score1' => $score1,
            'score2' => $score2
        ]));
    }
}
