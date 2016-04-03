<?

abstract class controller_handler {
	protected $_controller = null;
	public function create($controller_pointer) {} 
	public function destroy() {}
	public function event($data) {}
	public function tick() {}
}


class sensor {
	public $name = "";
	public $closed_state = 0;
	public $pin;
	public $detail_string = "";
	public $enabled = 1;
	public $state = 0;
	public $type = SENSORTYPE_BINARY;
	public $zone = 0;
	public $last_state_change_ts = 0;
	public $sticky_state_change = 0;
	public $sensor_group ="";
	public function set_state($value) {
		$this->state = $value;
		return $this->state;
	}
	public function get_state() {
		return $this->state;
	}
	public function event($data) {
		if ($this->sensor_group == "") {
			$this->sensor_group = $data->sensor_group;
		}
		
		if ($data->state != $this->state) {
			// new state is different from the last.
			if ($this->state == 1) {
				if (!$this->sticky_state_change) {
					$this->state = $data->state;
					$this->last_state_change_ts = $data->ts;
					return 1;

				} else {
					// if we get set we don't get unset in sticky mode.
					if (!$this->state){
					$this->state = 1;//$data->state;
					$this->last_state_change_ts = $data->ts;
					return 1;	
					}
				}
			}else {
				$this->state = $data->state;
				$this->last_state_change_ts = $data->ts;
				return 1;

			}
		}
		
		return 0;
	} 
}

class controller {
	private $_zone_state = array();
	private $_sensors = array();
	private $_shutdown = 0;
	private $_message_queue_id;
	private $_poll_delay = 5000;
	private $_mq_segment;
	private $_handlers = array();

	public function __construct($controller_config_file ="controller.ini",$sensor_config_file ="sensors.ini") {
		$this->log("piMind Event Controller started");
		$config = parse_ini_file($sensor_config_file,true);
		$this->log("Reading $sensor_config_file");
		if (!$config) {
			$this->log("Could not find a config file to read");
			$this->_shutdown = 1;
		}
		foreach ($config as $name => $details) {
			$sensor = new sensor;
			foreach ($details as $prop => $value) {
				$sensor->$prop = $value;
			}
			$this->_sensors[$sensor->pin] = $sensor;
			$this->log("Adding Sensor for pin " . $sensor->pin);

		}
		$this->_setup_handlers();
		$config = parse_ini_file($controller_config_file);
		foreach ($config as $prop => $value) {
			switch ($prop) {
				case "id":
					$this->_message_queue_id = $value;
			}
		}
		if (is_callable("msg_get_queue")) {
			$this->_mq_segment = msg_get_queue($this->_message_queue_id) ;
			$this->log("setting up some IPC messaging using segment ".  $this->_message_queue_id);
		} else {
			$this->log("There is no functional IPC message queue to leverage.  We can't work under these conditions");
			$this->_shutdown = 1;
			
		}
 
	}
	function __destruct() {
	}
	private function _event_broadcast($data) {
		foreach ($this->_handlers as $handler) {
			$handler->event($data);
		}
	}
	private function _tick_broadcast() {
		foreach ($this->_handlers as $handler) {
			$handler->tick();
		}
	}
	private function _setup_handlers() {
		static $glob_signature = 0;
		$this->log("Setting up event handlers");		
		$files = glob(PIMIND_CONTROLLER_HANDLERS.DIRECTORY_SEPARATOR."*.php");
		$serialized_files = serialize($files);
		if ($glob_signature != md5($serialized_files)) {
			foreach ($files as $file) {
				$parts = pathinfo($file);
				$classname = $parts["filename"];
				$basename  = $parts["basename"]; 
				if (isset($this->_handlers[$basename])) {
					// already instanciated
				} else {
					
					include_once $file;
					$new_handler = new $classname;
					if (get_parent_class($new_handler) instanceof controller_handler) {
						$this->log("Handled $basename does not extend the controller_handler class.  We don't know how to deal with it");
					} else {
						$this->_handlers[$basename] = $new_handler;
						$new_handler->create($this);
					}
				}
			}
			
			
			$glob_signature = md5($serialized_files);
		} else {
			// glob signature matches, lets not rebuild the handlers.
			return 1;
		}
	}
	function log($message, $severity = 1) {
		echo time(). "\t($severity)\t $message\n\r";
		syslog($severity, "controller.php:" . $message);
	}
	function get_sensors() {
		return $this->_sensors;
	}
	function generate_handler_event($handler_name, $sensor,$event_code, $event_message) {
		$msg = new stdClass();
		$msg->source_handler = $handler_name;
		$msg->type = 1;
		$msg->ts = time();
		$msg->pin = $pin;
		$msg->label = $sensor->name;
		$msg->zone =$sensor->zone;
		$msg->event_code = $event_code;
		$msg->event_message= $event_message; 
		
	}
	function event_sink($jsondata) {

		$data = json_decode(trim($jsondata));
		if ($data) {
			if (!$data->type) {
				if (isset($this->_sensors[$data->pin])) {
					$sensor =  $this->_sensors[$data->pin];
				} else {
					// undefined sensor sent data.  
					$sensor = new sensor;
					$sensor->pin = $data->pin;
				
					$this->log("A phantom sensor raised data on pin " .$data->pin,2);
					$this->_sensors[$data->pin] = $sensor;
				}
				$this->log("Got an event on pin " . $data->pin);
				if ($sensor->event($data)) {
					//sensors return true if there was a state change
					$this->log("Running a state check");
					$this->run_state_check();
	
				} else {
					// no state change.
				}
			}
			$this->_event_broadcast($data);
		} else {
			$this->log("A raised event didn't json_decode nicely \t $jsondata",2);
		}
	}
	function realtime() {
		$tick=0;
		while (!$this->_shutdown) {
			$tick++;
			$stat = msg_stat_queue ($this->_mq_segment);
			//print_r($stat);
			if ($stat["msg_qnum"]){
				$message = "";
				$message_type = 1;
				$desired_type = 0;
				$max_length = 8196;
				$ret = msg_receive($this->_mq_segment,$desired_type ,$message_type, $max_length,$message,1);
				if ($message) {
					//var_dump($message);
					//var_dump(base64_decode($message));
					$this->event_sink(($message));
				}
			}
			if ($tick % $this->_poll_delay == 1) {
				$this->log("Running a state check");
                $this->run_state_check();
			}
			$this->_tick_broadcast();
			usleep($this->_poll_delay);
		}
		$this->log("Shutdown signalled.  Exiting realtime.");
	}
	function set_zone_state($zone_id, $new_state) {
		$this->_zone_state[$zone_id] = $new_state;
		$this->log("Zone $zone_id set to state $new_state");
		if ($new_state == ARMED) {
			foreach ($this->_sensors as $sensor) {
				if ($sensor->zone == $zone_id){
					$sensor->sticky_state_change = 1;
				}
			}
		}
	}
	function run_state_check() {
		$zone_assertion = array();
		foreach ($this->_sensors as $sensor) {
			if (!isset($zone_assertion[$sensor->zone])) {
				$zone_assertion[$sensor->zone] = array();
			}
			if ($sensor->get_state() == 1) {
				$zone_assertion[$sensor->zone][]  = $sensor->pin;;//$zone_assertion[$sensor->zone] & $sensor->get_state();
			}
		}
		foreach ($zone_assertion as $zone => $pins){
			foreach ($pins as $pin) {	
				if ($this->_zone_state[$zone] == ARMED) {
					$this->log("ALARM TRIPPED.  Zone $zone, pin $pin, " .$this->_sensors[$pin]->name,10); 
				} else {
					$this->log("Zone Activity. Zone $zone, pin $pin,  ". $this->_sensors[$pin]->name);
				}
			}
		}
	}
}

// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");

chdir(PIMIND_CONFIG);

$controller = new controller;
//$controller->event_sink("{\"pin\":4,\"label\":\"Garage Door\",\"ts\":1459462111,\"state\":\"0\"}");
$controller->set_zone_state(1,STANDBY);
$controller->set_zone_state(2,STANDBY);

$controller->realtime();
?>
