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
		return 1;
	}
	public function destroy() {

	}
	public function event($data) {
		if (isset($data->type)) {
			$class = $data->class;
			
			switch ($data->type) {
				case 0:
					$this->_controller->log("watchdog sees a sensor event: " .implode(", ", get_object_vars($data)));
					break;
				case 1:
					$this->_controller->log("watchdog sees a handler event: " . implode(", ", get_object_vars($data)));
					break;
				case 2:
					$old_data =null;
					if (is_array($data->data)) {
						$old_data = $data->data;
						$data->data = implode(",",$data->data);
					}
					$this->_controller->log("watchdog sees an identity event: " . implode(", ", get_object_vars($data)));
					if ($old_data != NULL) {
						$data->data = $old_data;
					}
					break;
			}
			
		}
	}
	public function tick() {
	
	}


}