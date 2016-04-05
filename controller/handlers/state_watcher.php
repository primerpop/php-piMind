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
					$this->_states[$data->sensor_group][$data->pin][$data->label] = $data->state;
					break;
				case EVENT_TYPE_HANDLER:
					break;
				case EVENT_TYPE_VARDATA:
					switch ($data->class) {
						case "mac_sensor":
							$this->_states[$data->sensor_group][$data->pin][$data->data[0]][$data->data[1]] = $data->state;
							break;
					}
					
					break;
			}
			
		}
		echo json_encode($this->_states);
	}
	public function tick() {
	
	}


}