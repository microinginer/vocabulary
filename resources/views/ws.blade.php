<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Test</title>
</head>
<body>
<h1>WebSocket Test</h1>
<div id="status">Connecting...</div>
<button id="connect">Connect</button>
<button id="disconnect">Disconnect</button>
<button id="send">Send Message</button>
<script>
    let socket;
    const statusDiv = document.getElementById('status');
    const connectButton = document.getElementById('connect');
    const disconnectButton = document.getElementById('disconnect');
    const sendButton = document.getElementById('send');

    connectButton.addEventListener('click', () => {
        socket = new WebSocket('ws://localhost:5610');

        socket.onopen = function () {
            statusDiv.textContent = 'Connected';
            console.log('WebSocket connection established');
            // Отправляем токен аутентификации после подключения
            socket.send(JSON.stringify({ token: 'your-auth-token' }));
        };

        socket.onmessage = function (event) {
            const data = JSON.parse(event.data);
            console.log('Message from server', data);
            if (data.type === 'status-update') {
                console.log('User status updated:', data.user);
            }
        };

        socket.onclose = function () {
            statusDiv.textContent = 'Disconnected';
            console.log('WebSocket connection closed');
        };

        socket.onerror = function (error) {
            console.error('WebSocket error:', error);
        };
    });

    disconnectButton.addEventListener('click', () => {
        if (socket) {
            socket.close();
        }
    });

    sendButton.addEventListener('click', () => {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ message: 'Hello Server' }));
        }
    });
</script>
</body>
</html>
