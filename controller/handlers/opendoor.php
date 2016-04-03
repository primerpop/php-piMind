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
	const door_kept_open = 1;
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("opendoor handler initialized");
		$this->_sensors = $this->_controller->get_sensors();
	//	print_r($this->_sensors);
	}
	public function destroy() {
	
	}
	public function event($data) {
		$sensor= $this->_sensors[$data->pin];
		
		if ($sensor->type == SENSORTYPE_DOORSWITCH) {
			if ($sensor->state == 1) {
				$_door_states[$data->pin] = time() + $this->_opendoor_delay;
			} else {
				unset($_door_states[$data->pin]);
			}	
		}
	}
	public function tick() {
		foreach ($this->_door_states as $pin => $opentime) {
			if (time() > $opentime) {
				//$handler_name, $sensor,$event_code, $event_message
				$message = $this->_controller->generate_handler_event(get_class($this),$this->_sensors[$pin],self::door_kept_open,"Door Held Open");
				$this->_controller->event($message);
			}
		}
	}
	
	
}