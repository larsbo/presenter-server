<?php
namespace Presenter;

use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\WampServerInterface;

/**
* When a user publishes to a topic all clients who have subscribed
* to that topic will receive the message/event from the publisher
*/
class BasicPubSub implements WampServerInterface {
	public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible) {
		$topic->broadcast($event);
	}

	public function onCall(Conn $conn, $id, $topic, array $params) {
		$conn->callError($id, $topic, 'RPC not supported on this demo');
	}

	// No need to anything, since WampServer adds and removes subscribers to Topics automatically
	public function onSubscribe(Conn $conn, $topic) {}
	public function onUnSubscribe(Conn $conn, $topic) {}

	public function onOpen(Conn $conn) {}
	public function onClose(Conn $conn) {}
	public function onError(Conn $conn, \Exception $e) {}
}
?>