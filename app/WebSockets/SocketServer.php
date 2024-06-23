<?php
namespace App\WebSockets;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;
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

        if (isset($data->token)) {
            $token = PersonalAccessToken::findToken($data->token);
            if ($token) {
                $user = $token->tokenable;
                if ($user) {
                    $user->updateOnlineStatus(true);
                    Log::info("User {$user->id} is online.");
                    foreach ($this->clients as $client) {
                        $client->send(json_encode([
                            'type' => 'status-update',
                            'user' => $user
                        ]));
                    }
                    $from->user = $user; // Store user in connection
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        Log::info("Connection {$conn->resourceId} has disconnected");

        $user = isset($conn->user) ? $conn->user : null;
        if ($user) {
            $user->updateOnlineStatus(false);
            Log::info("User {$user->id} is offline.");
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'status-update',
                    'user' => $user
                ]));
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        Log::error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }
}

