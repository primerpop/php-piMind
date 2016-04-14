<?php
/**
* whoshome - piMind Controller extension
* Determine via MAC presence whos phones are home.
*
* @author Paul
*
*/

class whoshome extends controller_handler {
	protected $_controller = null;
	
	private $_validmacs = array();
	private $_configuration = array();
	private $_is_home = array();
	private $_away_timeout = 60;
	
	const home_user_arrived = 1;
	const home_user_departed = 2;
	const home_user_nobodyhome = 3;
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("handler template initialized");
		return $this->read_configuration();
	}
	public function destroy() {

	}
	private function _build_whoshome() {
		$state_watcher = & $this->_controller->get_handler("state_watcher");
		$state_data = & $state_watcher->get_states();
		if (isset($state_data["event_id"])) {
			$event_id = $state_watcher->get_states()["event_id"];
			$states = & $state_data["mac_sensor"];
		}
		if ($event_id && isset($state_data["mac_sensor"])) {
			foreach ($states as $sensor_group_name => $pins) {
				foreach ($pins as $pin =>$macs) {
					foreach ($macs as $mac => $mac_states ) {
						if (isset($this->_validmacs["/".$sensor_group_name."/".$mac])) {
							//$this->_controller->log($event_id .": " .$mac . " " . $this->_validmacs[$mac] . " has state ". $mac_states["state"]);
							if ($mac_states["state"] == 1) {
								if (!isset($this->_is_home["/".$sensor_group_name."/".$mac])) {
									$this->_controller->log($event_id .": " .$mac . " " . $this->_validmacs["/".$sensor_group_name."/".$mac] . " has arrived");
									$message = $this->_controller->generate_handler_event(get_class($this),$pin,self::home_user_arrived,"INFO",5,$mac . " " . $this->_validmacs[$mac] . " has arrived",1);
									$this->_controller->event($message);
									
								}
								$this->_is_home["/".$sensor_group_name."/".$mac] = time();
								
							} else {
								
								
							}
						}
					}
				}
			}
			
			foreach ($this->_is_home as $mac => $time) {
				if (($time + $this->_away_timeout) < time()) {
					$this->_controller->log($mac . " " . $this->_validmacs[$mac] . " has not been seen for ". $this->_away_timeout. " seconds");
					unset($this->_is_home[$mac]);
					$message = $this->_controller->generate_handler_event(get_class($this),$mac,self::home_user_departed,"INFO",5,$mac . " " . $this->_validmacs[$mac] . " has departed",1);
					$this->_controller->event($message);
					if (count($this->_is_home) == 0) {
						// nobody home.
						$message = $this->_controller->generate_handler_event(get_class($this),$mac,self::home_user_nobodyhome,"WARN",10,"No valid macs seen on network.  Nobody home indicator.",1);
						$this->_controller->event($message);
					}
				} 
				
			}
		}
		
		
			/**
		 *  [90:B6:86:5C:0C:01] => Array
        (
            [90:B6:86:5C:0C:01] => 1459827476
            [state] => 1
            [ip] => 192.168.255.162
        )

	
		 *   [Basement pi MAC Scanner] => Array
        (
            [0] => Array
                (
                    [88:63:DF:DD:1E:C0] => Array
                        (
                            [88:63:DF:DD:1E:C0] => 1459826190
                            [192.168.255.160] => 1
                            [] => 0
                        )

                    [84:38:38:E7:16:91] => Array
                        (
                            [84:38:38:E7:16:91] => 1459826191
                            [] => 0
                            [192.168.255.164] => 1
                        )

                )

        )

		 */
	}
	function read_configuration() {
	
		$this->_configuration = parse_ini_file(PIMIND_CONFIG .DIRECTORY_SEPARATOR . "whoshome.ini");
	
	
		foreach($this->_configuration as $mac => $name) {
			$this->_validmacs[$mac]= $name; 
			//$this->_is_home[$mac] = time();// + $this->_away_timeout;
		}
		if ($this->_configuration) {
			return 1;
		}
		return 0;
	}
	public function event($data) {
		if (isset($data->type)) {
			switch ($data->type) {
				case EVENT_TYPE_SENSOR:
					break;
				case EVENT_TYPE_HANDLER:
					break;
				case EVENT_TYPE_VARDATA:
					if ($data->class =="mac_sensor"){
						$this->_build_whoshome();
					}
					break;
			}
			
		}
	}
	public function tick() {
		static $last_check = 0;
		
		if (!$last_check) {
			$last_check = time();
			
		}
		
		if (($last_check + $this->_away_timeout) < time() ) {
			$last_check = time();
			$this->_build_whoshome();
		}
	}


}