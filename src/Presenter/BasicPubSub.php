<?php
namespace Presenter;

use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\WampServerInterface;

/**
* When a user publishes to a topic all clients who have subscribed
* to that topic will receive the message/event from the publisher
*/
class BasicPubSub implements WampServerInterface {
	protected $connections;
	protected $elements;


	public function __construct() {
		$this->connections = new \SplObjectStorage;
		$this->elements = array();
	}


	public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible) {
		$topic->broadcast($event);

		switch ($topic) {

		case 'add':
			// new element added
			$this->elements[$event['id']] = array(
				'session' => $event['session'],
				'name' => $event['name'],
				'path' => $event['path']
			);
			break;

		case 'remove':
			// element removed
			unset($this->elements[$event['id']]);
			break;
		}

		var_dump($this->elements);
	}


	public function onCall(Conn $conn, $id, $fn, array $params) {
		$conn->callError($id, $topic, 'RPC not supported!');
	}


	public function onSubscribe(Conn $conn, $topic) {
		//echo "New Subscription on topic ".$topic.": {$conn->resourceId}\n";
	}


	public function onUnSubscribe(Conn $conn, $topic) {
	}


	public function onOpen(Conn $conn) {
		// add new client to list of connections
		$this->connections->attach($conn);
		$connectionNumber = sizeof($this->connections);

		// get all connected clients
		$clients = array();
		foreach ($this->connections as $client) {
			$clients[] = array($client->resourceId => $client->WAMP->sessionId);
		}

		// publish all connections to all connections
		foreach ($this->connections as $connection) {
			$connection->event('connect', array('client', $clients));
		}

		// publish all elements to all connections
		foreach ($this->connections as $connection) {
			$connection->event('synchronize', array('elements', $this->elements));
		}

		echo "New connection: {$conn->resourceId} (connections: {$connectionNumber})\n";
	}


	public function onClose(Conn $conn) {
		$client = array($conn->resourceId => $conn->WAMP->sessionId);

		// remove client from list of connections
		$this->connections->detach($conn);
		$connectionNumber = sizeof($this->connections);

		// publish disconnection of client to still connected clients
		foreach ($this->connections as $connection) {
			$connection->event('disconnect', array('client', $client));
		}

		echo "Connection closed: {$conn->resourceId} (connections: {$connectionNumber})\n";
	}


	public function onError(Conn $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";

		$conn->close();
	}

}
?>