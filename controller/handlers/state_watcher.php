<?php
/**
* state_watcher - piMind Controller extension
* This handler watches and records the current state of each sensor or vardata event
*
* @author Paul
*
*/

class state_watcher extends controller_handler {
	protected $_controller = null;
	
	private $_states = array();
	private $_state_file = "";
	public function create($controller_pointer) {
		$this->_state_file = PIMIND_STATE.DIRECTORY_SEPARATOR."state_watcher.serialized";
		$this->_controller = $controller_pointer;
		$this->_controller->log("handler template initialized");
		if (file_exists($this->_state_file)) {
			$this->_states = unserialize(file_get_contents($this->_state_file));
		}
	}
	public function __destruct() {
		$this->destroy();
	}
	public function destroy() {
		$this->save_state;
	}
	function save_state() {
		if ($this->_states) {
			file_put_contents($this->_state_file,serialize($this->_states));
		}
	}
	public function event($data) {
		
		if (isset($data->type)) {
			switch ($data->type) {
				case EVENT_TYPE_SENSOR:
					$this->_states[$data->sensor_group][$data->pin][$data->label] = $data->state;
					$this->_states[$data->sensor_group."_timestamps"][$data->pin][$data->label] = time();
					break;
				case EVENT_TYPE_HANDLER:
					break;
				case EVENT_TYPE_VARDATA:
					switch ($data->class) {
						case "mac_sensor":
							$this->_states[$data->sensor_group][$data->pin][$data->data[0]][$data->data[0]] = time();
							$this->_states[$data->sensor_group][$data->pin][$data->data[0]][$data->data[1]] = $data->state;
							break;
					}
					
					break;
			}
			$this->save_state;
		}
		//echo json_encode($this->_states);
	}
	public function tick() {
	
	}


}