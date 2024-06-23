<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\WebSockets\SocketServer;

class StartSocketServer extends Command
{
    protected $signature = 'websockets:start';
    protected $description = 'Start the WebSocket server';

    public function handle() {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new SocketServer()
                )
            ),
            5610
        );

        $this->info("WebSocket server started on port 5610");
        $server->run();
    }
}
