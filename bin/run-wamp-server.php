<?php
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Presenter\BasicPubSub;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
	new WsServer(
		new WampServer(
			new BasicPubSub
		)
	), 8080
);

$server->run();
?>