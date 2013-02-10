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
	protected $client_data;
	protected $elements;


	public function __construct() {
		$this->connections = new \SplObjectStorage;
		$this->client_data = array();
		$this->elements = array();
	}

	private function _print_elements() {
		if (!empty($this->elements)) {
			echo "elements:\n";
			foreach ($this->elements as $id => $element) {
				echo " - " . $id . ": " . $element['name'] . "\n";
			}
			echo "\n";
		}
	}

	public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible) {
		$topic->broadcast($event);

		switch ($topic) {

		// add new element
		case 'add':
			// create hash value of element name for unique id
			$key = md5($event['name']);

			if (!array_key_exists($key, $this->elements)) {
				$this->elements[$key] = array(
					'session' => $event['session'],
					'name' => $event['name'],
					'type' => $event['type'],
					'left' => $event['left'],
					'top' => $event['top']
				);
				$this->_print_elements();
			}
			break;

		// remove element
		case 'remove':
			// create hash value of element name for unique id
			$key = md5($event['name']);

			if (isset($this->elements[$key])) {
				unset($this->elements[$key]);
			} else {
				echo "can't remove element {$key}: not found!\n";
			}
			$this->_print_elements();
			break;

		// reposition element
		case 'drag-end':
			// create hash value of element name for unique id
			$key = md5($event['name']);

			if (isset($this->elements[$key])) {
				$this->elements[$key]['left'] = $event['left'];
				$this->elements[$key]['top'] = $event['top'];
			} else {
				echo "can't reposition element {$key}: not found!\n";
			}
			break;

		// change client name
		case 'change-name':
			if (!isset($this->client_data[$event['session']])) {
				$this->client_data[$event['session']] = array();
			}
			$this->client_data[$event['session']]['name'] = $event['name'];
			break;

		// change client color
		case 'change-color':
			if (!isset($this->client_data[$event['session']])) {
				$this->client_data[$event['session']] = array();
			}
			$this->client_data[$event['session']]['color'] = $event['color'];
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
			$session = $client->WAMP->sessionId;
			$name = (isset($this->client_data[$session]['name'])) ? $this->client_data[$session]['name'] : '';
			$color = (isset($this->client_data[$session]['color'])) ? $this->client_data[$session]['color'] : '';
			$data = array(
				'session' => $session,
				'name' => $name,
				'color' => $color
			);
			$clients[] = array($client->resourceId => $data);
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