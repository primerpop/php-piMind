<?php
chdir(__DIR__);



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
class mac_sensor {
	private $_pin_map;
	private $_redirector_url;
	private $_ignore_pins;
	private $_spi_dev;
	private $_last_state;
	private $_configuration;
	private $_debug;
	private $_shutdown = 0;
	private $_secret = "";
	private $_arp_cmd = "";
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
	function raise_event($mac,$ip,$state) {
		$data = array();
		$data["pin"] = 0;
		
		$data["type"] = 2; // raw input data messages are type 0.
		$data["label"] = "exec(nmap)";
		$data["ts"] = time();
		$data["data"] = array($mac,$ip);
		$data["state"] = $state;
		$data["class"] =get_class($this);
		$data["sensor_group"] = $this->_configuration["sensor_group"];
		
		
		
		$json =  json_encode($data);
		return file_get_contents($this->_redirector_url . "?action=event&data=" .urlencode($json) . "&secret=" .urlencode($this->_secret)); 
	}
	function ping ($host, $timeout = 1) {
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket = socket_create(AF_INET, SOCK_RAW, 1);
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
		socket_connect($socket, $host, null);
	
		$ts = microtime(true);
		socket_send($socket, $package, strLen($package), 0);
		if (socket_read($socket, 255)) {
			$result = microtime(true) - $ts;
		} else {
			$result = false;
		}
		socket_close($socket);
	
		return $result;
	}
	function arp_dump(){
		$output = array();
		//run an nmap to freshen the ARP table with available devices.
		$return = exec($this->_nmap_cmd);
		//sleep(1);
		$return = exec($this->_arp_cmd, $output);//$this->_arp_cmd);
		$ips = array();
		$macs = array();
		
		foreach ($output as $line) {
			$parts = explode(" ", $line);
			$ip = str_replace(")","",str_replace("(","", $parts[1]));
			$mac = $parts[3];
			$ips[] = $ip;
			$macs[] = $mac;
		}
		return array("ips"=>$ips, "macs"=>$macs);
		
	}
	function realtime() {
		$this->log($this->_configuration["sensor_group"] . " entered realtime poll with delay of " . $this->_configuration["usleep_poll_delay"]);
		while (!$this->_shutdown) {
			
			$active_macs = array();
			
			if(true) {
				$mac_ip_map = array();
				$payload = $this->arp_dump();
				$macs = $payload["macs"];
				$ips = $payload["ips"];
				
				
				
				// loop once to update the active list
				foreach ($macs as $key => $mac) {
					if ($mac) {
						if (!isset($active_macs[$mac])) {
							$mac_ip_map[$mac] = $ips[$key];
							$ping = $this->ping($ips[$key]);
							if ($ping) {
								$active_macs[$mac] = time();
								$this->raise_event($mac, $mac_ip_map[$mac],1);
								$this->log("MAC $mac (".$mac_ip_map[$mac].") seen. $ping ms");
							} else{
								$this->log("MAC $mac (".$mac_ip_map[$mac].") failed ping. $ping ms");
							}
						}
					}
				}
			
				foreach ($active_macs as $mac => $timestamp) {
					if (!in_array($mac, $macs)) {
						
						
						$this->raise_event($mac,null, 0);
						$this->log("MAC $mac went away. Was with us for ". (time() - $timestamp) . " seconds");
						unset($active_macs[$mac]);
					}
				}
			} else {
				$this->log("can't read mac.nmap or file is empty.",6);
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
		
		if (isset($this->_configuration["arp_cmd"])) {
			$this->_arp_cmd = $this->_configuration["arp_cmd"];
		}	

		if (isset($this->_configuration["nmap_cmd"])) {
			$this->_nmap_cmd = $this->_configuration["nmap_cmd"];
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


$mac_sensor = new mac_sensor();
$mac_sensor->realtime();


?>