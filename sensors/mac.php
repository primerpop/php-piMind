<?php
chdir(__DIR__);

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
	private $_secret = "";
	/**
	 * Constructor.  Read configuration and set up the class
	 */
	
	public function __construct() {
		$this->log("piMind MAC Address presence detection agent started.");
		
		$this->log("Reading configuration from " . PIMIND_CONFIG .DIRECTORY_SEPARATOR . "mac.ini");
		$this->read_configuration();
		
	}
	public function __destruct() {
		
	}
	function raise_event($mac,$state) {
		$data = array();
		$data["pin"] = 0;
		
		$data["type"] = 2; // raw input data messages are type 0.
		$data["label"] = $mac;
		$data["ts"] = time();
		$data["state"] = $state;
		$data["sensor_group"] = $this->_configuration["sensor_group"];
		$json =  json_encode($data);
		return file_get_contents($this->_redirector_url . "?action=event&data=" .urlencode($json) . "&secret=" .urlencode($this->_secret)); 
	}
	function realtime() {
		$this->log($this->_configuration["sensor_group"] . " entered realtime poll with delay of " . $this->_configuration["usleep_poll_delay"]);
		while (!$this->_shutdown) {
			
			$active_macs = array();
			while (true) {
				exec("/usr/bin/nmap 192.168.255.1-255 -sP -oX ". PIMIND_STATE.DIRECTORY_SEPARATOR."mac.nmap");
				$file = file_get_contents(PIMIND_STATE.DIRECTORY_SEPARATOR."mac.nmap");
				
				$macs=array();
				if (preg_match_all('/([A-F0-9:]+)" addrtype="mac"/',$file ,$macs) ) {
					$macs = $macs[1];
				}
				
				// loop once to update the active list
				foreach ($macs as $mac) {
					if (!isset($active_macs[$mac])) {
						$active_macs[$mac] = time();
						$this->raise_event($mac, 1);
						$this->log("MAC $mac seen.");
					}
				}
			
				foreach ($active_macs as $mac => $timestamp) {
					if (!in_array($mac, $macs)) {
						
						unset($active_macs[$mac]);
						$this->raise_event($mac, 0);
						$this->log("MAC $mac went away. Was with us for ". time() - $timestamp . " seconds");
					}
				}
			
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
		syslog($severity, "mac.php:" . $message);
	}
	/**
	 * Read the configuration file defined in constants.php
	 */
	function read_configuration() {
	
		$this->_configuration = parse_ini_file(PIMIND_CONFIG .DIRECTORY_SEPARATOR . "mac.ini");
	
		
		if (isset($this->_configuration["debug"])) {
			$this->_debug = $this->_configuration["debug"];
		}	
		if (isset($this->_configuration["secret"])) {
			$this->_secret = $this->_configuration["secret"];
		} else {
			$this->log("Configuration value for secret is unset. Calls to the redirector will probably fail." );
		}
		if (isset($this->_configuration["redirector_url"])) {
			$this->_redirector_url = $this->_configuration["redirector_url"];
		} else {
			$this->log("redirector_url is unset. Events will not be communicated.  It's your choice really, but this software is useless without something to catch events.");
		}
		
	}
	
}


$piface = new piface_spi();
$piface->realtime();


?>