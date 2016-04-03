<?php
/**
* handler_template - piMind Controller extension
* Describe your handler
*
* @author Paul
*
*/

class handler_template extends controller_handler {
	protected $_controller = null;
	
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