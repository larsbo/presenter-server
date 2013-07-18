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
		
		echo "Server started. Waiting for clients...\n";
	}

	private function _print_elements() {
		if (!empty($this->elements)) {
			$i = 0;
			echo "elements:\n";
			foreach ($this->elements as $id => $element) {
				$i++;
				echo "[" . $i . "]\n";
				echo "\tid: " . $id . ":\n";
				echo "\tname: " . $element['name'] . "\n";
				echo "\ttype: " . $element['type'] . "\n";
				echo "\tleft: " . $element['left'] . "\n";
				echo "\ttop: " . $element['top'] . "\n";
				echo "\tz-index: " . $element['index'] . "\n";
				echo "\trotation: " . $element['rotation'] . "\n";
				echo "\tscale: " . $element['scale'] . "\n";
			}
			echo "\n";
		}
	}


	/*****  OPEN CLIENT CONNECTION  *****/
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

			$clients[] = array(
				$client->resourceId => array(
					'session' => $session,
					'name' => $name,
					'color' => $color
				)
			);
		}

		foreach ($this->connections as $connection) {
			// publish connected clients to all connections
			$connection->event('clients', array('client', $clients));

			// publish elements to all connections
			$connection->event('elements', array('elements', $this->elements));
		}

		echo "client {$conn->resourceId} connected! [clients total: {$connections}]\n";
	}


	/*****  CLOSE CLIENT CONNECTION  *****/
	public function onClose(Conn $conn) {
		$client = array($conn->resourceId => $conn->WAMP->sessionId);

		// remove client from list of connections
		$this->connections->detach($conn);
		$connections = sizeof($this->connections);

		// remove client data
			if (array_key_exists($conn->resourceId, $this->client_data)) {
				unset($this->client_data[$conn->resourceId]);
			}

		// clear elements array if no clients connected anymore
		if (!$connections) {
			$this->elements = array();
		}

		// publish disconnection of client to still connected clients
		foreach ($this->connections as $connection) {
			$connection->event('disconnect', array('client', $client));
		}

		echo "client {$conn->resourceId} disconnected! [clients total: {$connections}]\n";
	}


	public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible) {
		$topic->broadcast($event);

		switch ($topic) {

		// add new element
		case 'add':
			if (!array_key_exists($event['id'], $this->elements)) {
				$this->elements[$event['id']] = array(
					'session' => $event['session'],
					'name' => $event['name'],
					'type' => $event['type'],
					'left' => $event['left'],
					'top' => $event['top'],
					'index' => $event['index'],
					'rotation' => $event['rotation'],
					'scale' => $event['scale']
				);
				$this->_print_elements();
			}
			break;

		// remove element
		case 'remove':
			$id = $event['id'];

			if (!empty($this->elements) && isset($this->elements[$id])) {
				unset($this->elements[$id]);
			} else {
				echo "can't remove element {$id}: not found!\n";
			}
			$this->_print_elements();
			break;

		// on drag start
		case 'drag-start':
			$id = $event['id'];

			if (!empty($this->elements) && isset($this->elements[$id])) {
				$this->elements[$id]['index'] = $event['index'];
			}
			break;

		// after dragging
		case 'drag-end':
			$id = $event['id'];

			if (!empty($this->elements) && isset($this->elements[$id])) {
				$this->elements[$id]['left'] = $event['left'];
				$this->elements[$id]['top'] = $event['top'];
			}
			break;

		// after rotation & scaling
		case 'rotate-scale':
			$id = $event['id'];

			if (!empty($this->elements) && isset($this->elements[$id])) {
				$this->elements[$id]['rotation'] = $event['rotation'];
				$this->elements[$id]['scale'] = $event['scale'];

				$this->_print_elements();
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


	public function onError(Conn $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";

		$conn->close();
	}

	public function onCall(Conn $conn, $id, $fn, array $params) {
		$conn->callError($id, $topic, 'RPC not supported!');
	}

	public function onSubscribe(Conn $conn, $topic) {
	}

	public function onUnSubscribe(Conn $conn, $topic) {
	}
}
?>