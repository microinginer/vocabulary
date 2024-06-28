<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;

class WaitingGame implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly GameSession $session)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->session->status === 'pending' && $this->session->game_status === 'pending') {
            $this->session->delete();
            echo "Try to connect to WS \n";

            $loop = Factory::create();
            $connector = new Connector($loop);

            $connector('ws://127.0.0.1:5610')->then(function($conn) {
                echo "Connect success: send message \n";
                $conn->on('message', function($msg) use ($conn) {
                    echo "Received: {$msg}\n";
                    $conn->close();
                });

                $conn->send(json_encode([
                    'message' => 'Game session deleted',
                    'action' => 'auto_decline_game',
                    'session_id' => $this->session->id,
                    'player1_id' => $this->session->player1_id,
                    'player2_id' => $this->session->player2_id,
                ]));
            }, function($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });

            $loop->run();
        }
    }
}
