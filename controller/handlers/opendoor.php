<?php
/**
 * opendoor handler - piMind Controller extension
 * Watch the doors and signal an event when doors have been left open too long
 *  
 * @author Paul
 *
 */

class opendoor extends controller_handler {
	protected $_controller = null;
	private $_door_states = array();
	
	private $_sensors = array();
	
	private $_opendoor_delay = 20;
	
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("opendoor handler initialized");
		$this->_sensors = $this->_controller->get_sensors();
	//	print_r($this->_sensors);
	}
	public function destroy() {
	
	}
	public function event($data) {
		$sensor= $_sensors[$data->pin];
		
		if ($sensor->type == SENSORTYPE_DOORSWITCH) {
			if ($sensor->state == 1) {
				$_door_states[$data->pin] = time() + $this->_opendoor_delay;
			} else {
				unset($_door_states[$data->pin]);
			}	
		}
	}
	public function tick() {
		foreach ($_door_state as $pin => $opentime) {
			if (time() > $opentime) {
				
			}
		}
	}
	
	
}