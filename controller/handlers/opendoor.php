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
	public function create($controller_pointer) {
		$this->_controller = $controller_pointer;
		$this->_controller->log("opendoor handler initialized");
	}
	public function destroy() {
	
	}
	public function event($data) {
	
	}
	public function tick() {
	
	}
	
	
}