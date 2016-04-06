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
		
	//	print_r($this->_sensors);
	}
	public function destroy() {
	
	}
	public function event($data) {
		$state_watcher = $this->_controller->get_handler("state_watcher");
		$all_states = $state_watcher->get_states();//["piface_spi"];
		$states = 0;
		
		if (isset($all_states["piface_spi"])) {
			$states = $all_states["piface_spi"];
		}
		if ($states) {
			foreach ($states as $sensor_group_name => $pins) {
				foreach ($pins as $pin =>$labels) {
					foreach ($labels as $label => $state ) {
						$current_state = $state;
						if (!isset($this->_door_states[$data->pin]) && $current_state == 1) {
							$this->_door_states["/".$sensor_group_name."/".$pin] = time() + $this->_opendoor_delay;
							$this->_controller->log("opendoor: watching opendoor on /$sensor_group_name" . "/".$pin."/" .$label. ".  Alarm set at " . $this->_door_states[$pin]);
						} elseif (isset($this->_door_states["/".$sensor_group_name."/".$pin]) && $current_state == 0) {
							unset($this->_door_states[$pin]);
							$this->_controller->log("opendoor: no longer watching opendoor on /$sensor_group_name/" . $pin ."/" .$label. ".  Alarm cleared.");
						} else {
								
						}
					}
				}
			}
			
		}
	}
	public function tick() {
		foreach ($this->_door_states as $pin => $opentime) {
			if (time() > $opentime) {
				//send a message that the door is held open
				$message = $this->_controller->generate_handler_event(get_class($this),$pin,self::door_kept_open,"WARN",5,"Door Held Open",1);
				$this->_controller->log("opendoor:  device $pin held open > than ".$this->_opendoor_delay);
				// reset the door state delay again.
				$this->_door_states[$pin] = time() + $this->_opendoor_delay; 
				$this->_controller->log("opendoor: watching opendoor on device " . $pin . ".  Resetting alarm to " . $this->_door_states[$pin]);
				
				//function generate_handler_event($handler_name,$pin, $label, $event_code, $event_message,$state) {
				$this->_controller->event($message);
			}
		}
	}
	
	
}