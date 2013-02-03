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
			// create hash value of element name to allocate each element
			$key = md5($event['name']);

			$this->elements[$key] = array(
				'session' => $event['session'],
				'name' => $event['name'],
				'path' => $event['path']
			);

			var_dump($this->elements);
			break;

		case 'remove':
			// create hash value of element name to allocate each element
			$key = md5($event['name']);

			if (isset($this->elements[$key])) {
				unset($this->elements[$key]);
			} else {
				echo "element {$key} not found!\n";
			}

			var_dump($this->elements);
			break;

		}
	}


	public function onCall(Conn $conn, $id, $fn, array $params) {
		$conn->callError($id, $topic, 'RPC not supported!');
	}


	public function onSubscribe(Conn $conn, $topic) {
	}


	public function onUnSubscribe(Conn $conn, $topic) {
	}


	public function onOpen(Conn $conn) {
		// add new client to list of connections
		$this->connections->attach($conn);
		$connections = sizeof($this->connections);

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

		echo "New connection: {$conn->resourceId} (connections: {$connections})\n";
	}


	public function onClose(Conn $conn) {
		$client = array($conn->resourceId => $conn->WAMP->sessionId);

		// remove client from list of connections
		$this->connections->detach($conn);
		$connections = sizeof($this->connections);

		// clear elements array if no clients connected anymore
		if (!$connections) {
			$this->elements = array();
		}

		// publish disconnection of client to still connected clients
		foreach ($this->connections as $connection) {
			$connection->event('disconnect', array('client', $client));
		}

		echo "Connection closed: {$conn->resourceId} (connections: {$connections})\n";
	}


	public function onError(Conn $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";

		$conn->close();
	}

}
?>