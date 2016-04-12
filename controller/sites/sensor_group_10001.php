<?php

class sensor_group_10001 extends controller_handler  {
	protected $_controller = null;
	
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("sensor_group_10001 handler initialized");
		return 1;
	}
	public function destroy() {
	
	}
	public function event($data) {
		if (isset($data->type)) {
			switch ($data->type) {
				case EVENT_TYPE_SENSOR:
					break;
				case EVENT_TYPE_HANDLER:
					break;
				case EVENT_TYPE_VARDATA:
					break;
			}
				
		}
	}
	public function tick() {
	
	}
	
}

?>