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
		print_r($states);
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