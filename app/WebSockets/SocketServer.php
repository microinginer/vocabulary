<?php

namespace App\WebSockets;

use App\Jobs\WaitingGame;
use App\Models\GameAnswer;
use App\Models\User;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\GameSession;
use Illuminate\Support\Facades\Log;

class SocketServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);

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
            Log::info("Call action: " . $data->action);

            $this->handleAction($from, $data);
        }
    }

    protected function handleAction(ConnectionInterface $from, $data)
    {
        switch ($data->action) {
            case 'create_game':
                Log::info("Handle game" . $data->action);
                $this->createGame($from, $data);
                $this->broadcastStatusUpdate();
                break;
            case 'accept_game':
                $this->acceptGame($from, $data);
                $this->broadcastStatusUpdate();
                break;
            case 'decline_game':
                $this->declineGame($from, $data);
                $this->broadcastStatusUpdate();
                break;
            case 'auto_decline_game':
                $this->autoDeclineGame($from, $data);
                $this->broadcastStatusUpdate();
                break;
            case 'correct_answer':
            case 'in_correct_answer':
                $this->handleAnswer($from, $data);
                break;
            case 'cancel_pending_games':
                $this->cancelGame($from, $data);
                $this->broadcastStatusUpdate();
                break;
            case 'complete_game':
                $this->completeGame($from, $data);
                $this->broadcastStatusUpdate();
                break;
            default:
                Log::warning("Unknown action: {$data->action}");
        }
    }

    protected function handleAnswer(ConnectionInterface $from, $data)
    {
        $user = $from->user;
        $session = GameSession::findOrFail($data->session_id);

        GameAnswer::create([
            'game_session_id' => $session->id,
            'user_id' => $user->id,
            'word_id' => $data->word_id,
            'word_sentence_id' => $data->sentence_id,
            'is_correct' => $data->action === 'correct_answer',
        ]);

        if ($data->isLast) {
            if ($user->id === $session->player1_id) {
                $session->update(['is_player1_finished' => true]);
                Log::info("User1 {$user->id} has finished the game session {$session->id}");
            }

            if ($user->id === $session->player2_id) {
                $session->update(['is_player2_finished' => true]);
                Log::info("User2 {$user->id} has finished the game session {$session->id}");
            }
        }

        $player1CorrectAnswer = GameAnswer::where('game_session_id', $session->id)
            ->where('user_id', $session->player1_id)
            ->where('is_correct', true)
            ->count();
        $player2CorrectAnswer = GameAnswer::where('game_session_id', $session->id)
            ->where('user_id', $session->player2_id)
            ->where('is_correct', true)
            ->count();

        $response = [
            'type' => 'answer_result',
            'session_id' => $session->id,
            'user1Score' => $player1CorrectAnswer,
            'user2Score' => $player2CorrectAnswer,
            'isFinished' => $session->is_player1_finished && $session->is_player2_finished
        ];

        $this->notifyUser($session->player1_id, $response);
        $this->notifyUser($session->player2_id, $response);
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

        // Проверка, есть ли активная игра у противника
        $opponent = User::query()->where('id', $opponentId)->first();
        if ($opponent && $opponent->hasActiveGame()) {
            $from->send(json_encode(['error' => 'Opponent already has an active game']));
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

        $this->notifyUser($user->id, [
            'type' => 'game_waiting',
            'session_id' => $session->id,
            'waiting' => $opponent
        ]);

        dispatch(new WaitingGame($session))->delay(now()->addSeconds(30));
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

    protected function cancelGame(ConnectionInterface $from, $data)
    {
        $user = $from->user;

        if (empty($data->session_id)) {
            $session = GameSession::where(function ($query) use ($user) {
                $query->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id);
            })->where('status', 'pending')
                ->orWhere('status', 'active')
                ->first();
        } else {
            $session = GameSession::findOrFail($data->session_id);
        }

        $session->delete();

        Log::info("Game declined by user {$user->id}");

        $this->notifyUser($session->player1_id, [
            'type' => 'game_cancelled',
            'session_id' => $session->id
        ]);

        $this->notifyUser($session->player2_id, [
            'type' => 'game_cancelled',
            'session_id' => $session->id
        ]);
    }

    protected function declineGame(ConnectionInterface $from, $data)
    {
        $user = $from->user;

        $session = GameSession::query()->where('id',$data->session_id)->first();

        if(!$session) {
            return;
        }

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
    }

    protected function autoDeclineGame(ConnectionInterface $from, $data)
    {
        Log::info("Game auto declined by supervisor");

        $this->notifyUser($data->player1_id, [
            'type' => 'game_auto_declined',
            'session_id' => $data->session_id
        ]);
    }

    protected function notifyUser($userId, $message)
    {
        foreach ($this->clients as $client) {
            if (isset($client->user) && $client->user->id == $userId) {
                $client->send(json_encode($message));
            }
        }
    }

    protected function broadcastStatusUpdate($user = [])
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

            // Отмена вызова и удаление сессии игры при отключении пользователя
            $activeSession = GameSession::query()
                ->where(function ($query) use ($user) {
                    $query->where('player1_id', $user->id)
                        ->orWhere('player2_id', $user->id);
                })->whereIn('status', ['pending', 'active'])
                ->first();

            if ($activeSession) {
                $activeSession->delete();
                Log::info("Game session {$activeSession->id} has been deleted due to user {$user->id} disconnection.");

                $this->notifyUser($activeSession->player1_id, [
                    'type' => 'game_cancelled',
                    'session_id' => $activeSession->id,
                    'when' => 'onClose',
                    'player1' => $activeSession->player1_id,
                ]);
                $this->notifyUser($activeSession->player2_id, [
                    'type' => 'game_cancelled',
                    'session_id' => $activeSession->id,
                    'when' => 'onClose',
                ]);
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    private function completeGame(ConnectionInterface $from, $data)
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
            'game_status' => 'completed',
        ]);

        Log::info("Game completed by user {$user->id}");

        $this->notifyUser($session->player1_id, [
            'type' => 'game_completed',
            'session_id' => $session->id
        ]);

        $this->notifyUser($session->player2_id, [
            'type' => 'game_completed',
            'session_id' => $session->id
        ]);
    }
}
