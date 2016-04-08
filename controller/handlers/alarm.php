<?php
/**
* handler_template - piMind Controller extension
* Describe your handler
*
* @author Paul
*
*/

class alarm extends controller_handler {
	protected $_controller = null;
	
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("Alarm Handler initialized");
		return 1;
	}
	public function destroy() {

	}
	public function event($data) {
		if (isset($data->type)) {
			switch ($data->type) {
				case EVENT_TYPE_SENSOR:
					break;
				case EVENT_TYPE_HANDLER:
					print_r($data);
					if ($data->event_code == 10) {
						$this->_controller->log("Alarm Service sees an event coded with event_code 10");
					}
						
					break;
				case EVENT_TYPE_VARDATA:
					break;
			}
			
		}
	}
	public function tick() {
	
	}


}