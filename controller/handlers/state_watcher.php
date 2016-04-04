<?php
/**
* state_watcher - piMind Controller extension
* This handler watches and records the current state of each sensor or vardata event
*
* @author Paul
*
*/

class state_watcher extends controller_handler {
	protected $_controller = null;
	
	private $_states = array();
	
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("handler template initialized");
		
	}
	public function destroy() {

	}
	public function event($data) {
		if (isset($data->type)) {
			switch ($data->type) {
				case EVENT_TYPE_SENSOR:
					$this->_states[$data->sensor_group][$data->pin] = $data->state;
					break;
				case EVENT_TYPE_HANDLER:
					break;
				case EVENT_TYPE_VARDATA:
					$this->_states[$data->sensor_group][$data->pin] = $data->data;
					break;
			}
			
		}
		$print_r($this->_states);
	}
	public function tick() {
	
	}


}