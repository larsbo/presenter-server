<?php
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Presenter\Server;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
	new WsServer(
		new Server()
	), 8080
);

$server->run();
?>