<?php
/**
 * opendoor handler - piMind Controller extension
* Watch the doors and signal an event when doors have been left open too long
*
* @author Paul
*
*/

class handler_watchdog extends controller_handler {
	protected $_controller = null;

	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("handler watchdog initialized");
		$this->_sensors = $this->_controller->get_sensors();
		//	print_r($this->_sensors);
	}
	public function destroy() {

	}
	public function event($data) {
		if (isset($data->type)) {
			if ($data->type == 1) {
				$this->_controller->log("watchdog sees a handler event" . print_r($data,true));
			}
		}
	}
	public function tick() {
	
	}


}