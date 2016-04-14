<?php

class sensor_group_10001 extends controller_handler  {
	protected $_controller = null;
	private $_configuration = array();
	
	private $_sms;
	
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("sensor_group_10001 handler initialized");
		include_once(PIMIND_NOTIFIERS.DIRECTORY_SEPARATOR."twiliosms.php");
		$this->_sms = new twiliosms;
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
					print_r($data);
					switch ($data->source_handler) {
						case "opendoor":
							// Alarm
							$this->_controller->log("Opendoor event... notifying");
							if (($data->pin == "/10001/5" || $data->pin =="/10001/3") && $data->state == 1 && !$backdoor_notified) {
								$backdoor_notified= 1;
								
								foreach ($this->_configuration as $number) {
									$this->_sms->send_message($number, "Backdoor is ajar!!");
								}
							} elseif ($data->state == 0) {
								$backdoor_notified= 0;
							}
						case "whoshome":
							$this->_controller->log("Opendoor event... notifying");
							foreach ($this->_configuration as $number) {
								$this->_sms->send_message($number, "Who's home event: ". $data->state);
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