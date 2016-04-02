<?php
use Pkj\Raspberry\PiFace\PiFaceDigital;

require '../vendor/autoload.php';


// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");


/**
 * piface_spi
 * 
 * Class reads the input from the SPI subsystem and signals events to the configured redirector
 * @author Paul
 *
 */
class piface_spi {
	private $_pin_map;
	private $_redirector_url;
	private $_ignore_pins;
	private $_spi_dev;
	private $_last_state;
	private $_configuration;
	private $_debug;
	private $_shutdown = 0;
	/**
	 * Constructor.  Read configuration and set up the class
	 */
	
	public function __construct() {
		$this->log("piMind piface-spi input monitoring agent started.");
		if (class_exists("Pkj\Raspberry\PiFace\PiFaceDigital")) {
			$this->_spi_dev = PiFaceDigital::create();
			$this->_spi_dev->init();
		} else {
			$this->log("SPI:  PiFaceDigital class is not available",10);
			
		}
		$this->log("Reading configuration from " . PIMIND_CONFIG .DIRECTORY_SEPARATOR . "piface-spi.ini");
		$this->read_configuration();
		
	}
	public function __destruct() {
		
	}
	function raise_event($pin,$state) {
		$data = array();
		$data["pin"] = $pin;
		if (!isset($this->_pin_map[$pin])){
			$this->_pin_map[$pin]= "Undefined piface-spi input";
		}
		$data["label"] = $this->_pin_map[$pin];
		$data["ts"] = time();
		$data["state"] = $state;
		$data["sensor_group"] = $this->_configuration["sensor_group"];
		$json =  json_encode($data);
		return file_get_contents($redirector_url . "?action=event&data=" .urlencode($json)); 
	}
	function realtime() {
		$pin_map = $this->_pin_map;
		$ignore_pins = $this->_ignore_pins; 
		$this->log($this->_configuration["sensor_group"] . " entered realtime poll with delay of " . $this->_configuration["usleep_poll_delay"]);
		while (!$this->_shutdown) {
			
			if (isset($this->_spi_dev)) {
				$input_pins = $this->_spi_dev->getInputPins();
				foreach ($input_pins as $pin) {
					if (in_array($pin,$this->_ignore_pins)) {
						unset($ignore_pins[$pin]);
					}
				}
			} else {
				$this->log("piFace Interface isn't available",10);
				if ($this->_debug) {
					sleep(5);
				} else {
					$this->log("piFace not in debug mode and piFace Interface isn't available. ENDING");
					$this->_shutdown = 1;
				}
			}
			foreach ($input_pins as $pin => $inputPin) {
				$led = $this->_spi_dev->getLeds()[$pin];
				
				$label = "";
				if (isset($pin_map[$pin])) {
					$label = $pin_map[$pin];
				} else {
					$label = "unmapped pin $pin";
				}
				if (!isset($this->_last_state[$pin])) {
					$this->_last_state[$pin] = 0;
				}
		
			    	$value = $inputPin->getValue();
				if ($value == 1) {
					$value = 0;
					$led->turnOff();
				} else {
					$value = 1;
					$led->turnOn();
				}
				if ($value != $this->_last_state[$pin]) {
			        $this->log("Event on ".$label . "($pin) = $value. LS = " . $this->_last_state[$pin]);
					$this->_last_state[$pin] = $value;
					$this->raise_event($pin,$value);
				}
				if ($this->_debug) {
		                echo time() . ": " . $label . "($pin) = " .$value ."\n\r";
				}
				$led->turnOff();
		
		        }
			
		        usleep($this->_configuration["usleep_poll_delay"]);
		}
		$this->log("Realtime loop shutdown condition");
		return $this->_shutdown;
	}
	/**
	 * Class logging facility.  Writes to both STDOUT and SYSLOG
	 * @param string $message
	 * @param number $severity
	 */
	function log($message, $severity = 1) {
		echo time(). "\t($severity)\t $message\n\r";
		syslog($severity, $message);
	}
	/**
	 * Read the configuration file defined in constants.php
	 */
	function read_configuration() {
	
		$this->_configuration = parse_ini_file(PIMIND_CONFIG .DIRECTORY_SEPARATOR . "piface-spi.ini");
	
		if (isset($this->_configuration["ignore_pins"])) {
			
			$this->_ignore_pins = explode(",",$this->_configuration["ignore_pins"] );
			$this->log("ignoring pins " .$this->_configuration["ignore_pins"] );
			
		}
		if (isset($this->_configuration["debug"])) {
			$this->_debug = $this->_configuration["debug"];
		}	
		if (isset($this->_configuration["redirector_url"])) {
			$this->_redirector_url = $this->_configuration["redirector_url"];
		} else {
			$this->log("redirector_url is unset. Events will not be communicated.  It's your choice really, but this software is useless without something to catch events.");
		}
		
		foreach ($this->_configuration as $item => $value) {
			$found_maps = 0;
			if (substr($item, 0,8) == "pin_map_") {
				$this->log("Mapping Pin ".substr($item, 8) ." to " . $value);
				$this->_pin_map[substr($item, 8)] = $value;
			}
		}
	}
	
}


$piface = new piface_spi();
$piface->realtime();


//$_spi_dev->getLeds()[0]->turnOn();
//sleep(2);
//$_spi_dev->getLeds()[0]->turnOff();
