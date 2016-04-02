<?



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

	public function set_state($value) {
		$this->state = $value;
		return $this->state;
	}
	public function get_state() {
		return $this->state;
	}
	public function event($data) {
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

	public function __construct($controller_config_file ="controller.ini",$sensor_config_file = "sensors.ini") {
		$this->log("piMind Event Controller started");
		$config = parse_ini_file($sensor_config_file,true);
		$this->log("Reading $sensor_config_file");

		foreach ($config as $name => $details) {
			$sensor = new sensor;
			foreach ($details as $prop => $value) {
				$sensor->$prop = $value;
			}
			$this->_sensors[$sensor->pin] = $sensor;
			$this->log("Adding Sensor for pin " . $sensor->pin);

		}

		$config = parse_ini_file($controller_config_file);
		foreach ($config as $prop => $value) {
			switch ($prop) {
				case "id":
					$this->_message_queue_id = $value;
			}
		}
		$this->_mq_segment = msg_get_queue($this->_message_queue_id) ;
		$this->log("setting up some IPC messaging using segment ".  $this->_message_queue_id);
 
	}
	function __destruct() {
	}
	function log($message, $severity = 1) {
		echo time(). "\t($severity)\t $message\n\r";
		syslog($severity, $message);
	}
	function dump_sensors() {
		print_r($this->_sensors);
	}
	function event_sink($jsondata) {

		$data = json_decode(trim($jsondata));
		if ($data) {
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
			usleep($this->_poll_delay);
		}
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
					$this->log("ALARM TRIPPED.  Zone $zone, pin $pin",10); 
				} else {
					$this->log("Zone Activity. Zone $zone, pin $pin,  ". $this->_sensors[$pin]->name);
				}
			}
		}
	}
}


$controller = new controller;
//$controller->event_sink("{\"pin\":4,\"label\":\"Garage Door\",\"ts\":1459462111,\"state\":\"0\"}");
$controller->set_zone_state(1,STANDBY);
$controller->set_zone_state(2,STANDBY);

$controller->realtime();
?>
