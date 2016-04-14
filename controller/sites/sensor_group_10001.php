<?php

class sensor_group_10001 extends controller_handler  {
	protected $_controller = null;
	private $_configuration = array();
	
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("sensor_group_10001 handler initialized");
		
		return 1;
	}
	public function destroy() {
	
	}
	public function event($data) {
		static $backdoor_notified = 0;
		if (isset($data->type)) {
			switch ($data->type) {
				case EVENT_TYPE_SENSOR:
					
					break;
				case EVENT_TYPE_HANDLER:
					switch ($data->source_handler) {
						case "opendoor":
							// Alarm
							if ($data->pin == 2 && $data->state == 1 && !$backdoor_notified) {
								$backdoor_notified= 1;
								include_once(PIMIND_NOTIFIERS.DIRECTORY_SEPARATOR."twiliosms.php");
								$t = new twiliosms;
								foreach ($this->_configuration as $number) {
									$t->send_message($number, "Backdoor is ajar!!");
								}
							} elseif ($data->state == 0) {
								$backdoor_notified= 0;
							}
						case "whoshome":
							foreach ($this->_configuration as $number) {
								$t->send_message($number, "Who's home event: ". $data->state);
							}
					
					}
					break;
				case EVENT_TYPE_VARDATA:
					break;
			}
				
		}
		
	}
	public function tick() {
	
	}
/**
	 * Read the configuration file defined in constants.php
	 */
	function read_configuration() {
	
		$this->_configuration = parse_ini_file(PIMIND_CONFIG .DIRECTORY_SEPARATOR . "sensor_group_10001.ini");
	
				
		
	}
}

?>