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
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("handler template initialized");
		
	}
	public function destroy() {

	}
	private function _build_whoshome() {
		$state_watcher = $this->_controller->get_handler("state_watcher");
		$states = $state_watcher->get_states()["mac_sensor"];
		foreach ($states as $sensor_group_name => $pins) {
			foreach ($pins as $pin =>$macs) {
				foreach ($macs as $mac => $mac_states ) {
					$this->_controller->log("$mac = " . $mac_states["state"]);
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
		}
	
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
	
	}


}