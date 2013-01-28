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
		$conn->callError($id, $topic, 'RPC not supported!');
	}

	public function onSubscribe(Conn $conn, $topic) {
		echo "New Subscription on topic ".$topic.": {$conn->resourceId}\n";
	}

	public function onUnSubscribe(Conn $conn, $topic) {
	}

	public function onOpen(Conn $conn) {
		echo "New connection: {$conn->resourceId}\n";
	}

	public function onClose(Conn $conn) {
		echo "Connection closed: {$conn->resourceId}\n";
	}

	public function onError(Conn $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";

		$conn->close();
	}

}
?>