<?
chdir(__DIR__);
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
	public $type = SENSORTYPE_DOORSWITCH;
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
	 
}

class controller {
	
	private $_shutdown = 0;
	private $_message_queue_id;
	private $_poll_delay = 5000;
	private $_mq_segment;
	private $_handlers = array();
	private $_site_handlers = array();
	public function __construct($controller_config_file ="controller.ini",$sensor_config_file ="sensors.ini") {
		$this->log("piMind Event Controller started");
		$this->_setup_handlers();
		$this->_setup_site_handlers();
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
		foreach ($this->_handlers as $handler_name => $handler) {
			if (isset($data->source_handler)) {
				if ($data->source_handler != $handler_name) {
					$handler->event($data);
				}
			} else {
				$handler->event($data);
			}
		}
		if (isset($data->sensor_group)) {
			if (isset($this->_site_handlers[$data->sensor_group])) {
				$site_handler = $site_handlers[$data->sensor_group];
				$site_handler->event($data);
			}
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
		$sort=array();
		if ($glob_signature != md5($serialized_files)) {
			foreach ($files as $file) {
				$parts = pathinfo($file);
				$classname = $parts["filename"];
				$basename  = $parts["basename"]; 
				if (isset($this->_handlers[$classname])) {
					// already instanciated
				} else {
					include_once $file;
					$new_handler = new $classname;
					$this->_handlers[$classname] = $new_handler;
					$sort[$classname] = $handler_create = $new_handler->create($this);
					$this->log("$classname"."->create() = $handler_create");
				}
			}
			arsort($sort);
			$temp_handlers = array();
			$this->log("re-ordering handler firing order");
			foreach ($sort as $classname=>$order) {
				$temp_handlers[$classname] = $this->_handlers[$classname];
			}
			$this->_handlers = $temp_handlers;
			$this->log("Now as: " . implode(",",array_keys($this->_handlers)));
			
			$glob_signature = md5($serialized_files);
		} else {
			// glob signature matches, lets not rebuild the handlers.
			return 1;
		}
	}
	private function _setup_site_handlers() {
		static $glob_signature_1 = 0;
		$this->log("Setting up site scripts");
		$files = glob(PIMIND_CONTROLLER_SITES.DIRECTORY_SEPARATOR."*.php");
		$serialized_files = serialize($files);
		$sort=array();
		foreach ($files as $file) {
			$parts = pathinfo($file);
			$classname = $parts["filename"];
			$basename  = $parts["basename"];
			if (isset($this->_site_handlers[$classname])) {
				// already instanciated
			} else {
				include_once $file;
				$new_handler = new $classname;
				$this->_site_handlers[$classname] = $new_handler;
				$sort[$classname] = $handler_create = $new_handler->create($this);
				$this->log("$classname"."->create() = $handler_create");
			}
			
		}
		arsort($sort);
		$temp_handlers = array();
		$this->log("re-ordering site handler firing order");
		foreach ($sort as $classname=>$order) {
			$temp_handlers[$classname] = $this->_site_handlers[$classname];
		}
		$this->_site_handlers = $temp_handlers;
		$this->log("Now as: " . implode(",",array_keys($this->_site_handlers)));
			

	}
	function get_handler($handler_name) {
		if (isset($this->_handlers[$handler_name])) {
			return $this->_handlers[$handler_name];
		}
		return 0;
	}
	function log($message, $severity = 1) {
		echo time(). ": ($severity) : $message\n\r";
		syslog($severity, "controller.php:" . $message);
	}
	
	function generate_handler_event($handler_name,$pin, $label, $event_code, $event_message,$state) {
		$msg = new stdClass();
		$msg->source_handler = $handler_name;
		$msg->class = get_class($this);
		$msg->type = EVENT_TYPE_HANDLER;
		$msg->ts = time();
		$msg->pin = $pin;
		$msg->state = $state;
		$msg->label = $label;
		$msg->event_code = $event_code;
		$msg->event_message= $event_message; 
		return $msg;	
	}
	function event($data) {
		$this->_event_broadcast($data);
	}
	function event_sink($jsondata) {

		$data = json_decode(trim($jsondata));
		if ($data) {
			$this->event($data);
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
				//$this->log("Running a state check");
                //$this->run_state_check();
			}
			$this->_tick_broadcast();
			usleep($this->_poll_delay);
		}
		//$this->handle_cli();
		$this->log("Shutdown signalled.  Exiting realtime.");
	}
	function handle_cli(){
		static $keyboard_buffer = "";
		
		$char = $this->_non_block_read(non_block_read(STDIN, $x));
		echo $char;
	}
	function _non_block_read($fd, &$data) {
	    $read = array($fd);
	    $write = array();
	    $except = array();
	    $result = stream_select($read, $write, $except, 0);
	    if($result === false) throw new Exception('stream_select failed');
	    if($result === 0) return false;
	    $data = stream_get_line($fd, 1);
	    return true;
	}
	
}

// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");

chdir(PIMIND_CONFIG);

$controller = new controller;
$controller->realtime();
?>
