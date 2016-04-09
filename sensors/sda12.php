<?php 
chdir(__DIR__);



// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");

class sda12io_tcp {
	private $_fopen_sock = 0;
	private $_buffer = "";
	private $_expect_bytes = 0;
	function __construct($host, $port, $timeout=5) {
		$errno =0;
		$errstr = "";
		$this->_fopen_sock =  fsockopen($host, $port, $errno, $errstr, $timeout);
		if ($this->_fopen_sock) {
			//big pull, get everything out.
			$toss = fread($this->_fopen_sock,4096);
			return 1;
		}
		return 0;
	}
	function send_poll($channels) {
		if ($this->_fopen_sock) {
			$this->_expect_bytes = ($channels + 1) * 2;
			//$toss = fread($this->_fopen_sock,4096);
			//fwrite($this->_fopen_sock,"!0RA" .chr($channels));
		}	
	}
	function read() {
		$t_buff = fread($this->_fopen_sock,128);
		$this->_buffer .= $t_buff;
		
	}
	function get_buffer() {
		$data = $this->_buffer;
		$this->_buffer = "";
		return $data;
		
	}
	function get_message() {
		$this->read();
		if (strlen($this->_buffer)>= $this->_expect_bytes){
			
			$message= substr($this->_buffer,0,$this->_expect_bytes);
			if (strlen($this->_buffer)) {
				$this->_buffer = substr($this->_buffer,$this->_expect_bytes);
			}
			return $message;
		}else {
			
			
		}
		return 0;
	}
}

class sda12io_serial {
	private $_dio_handle;
	function __construct($serial_device = "/dev/TTYS0") {
		
	}
	function send_poll($channels) {
		
	}
	function read() {
		
	}
}

class sda12 {
	private $_shutdown = 0;
	private $_sdaio_class = null;
	function __construct($sdaio_instance) {
		$this->_sdaio_class = $sdaio_instance;
	}	
	function __destruct() {
		
	}
	function realtime() {
		
		while (!$this->_shutdown) {
			$this->_sdaio_class->send_poll(11);
			$buffer = $this->_sdaio_class->get_message();
			//$buffer = $this->_sdaio_class->get_buffer();
			if ($buffer) {
				$this->process_message($buffer,11);
			}
			usleep(500000);
		}
		
	}
	function process_message($message, $nbr_channels) {
		static $message_count = 0;
		$message_count++;
		$byte_pairs = str_split($message, 2);
		
		echo "msgid: $message_count: ";
		foreach ($byte_pairs as $key => $pair) {
			$msb = $pair[0];
			$lsb = $pair[1];
			$sample = (ord($msb) * 256) + ord($lsb);
			echo "$key:$sample, ";
		}
		
		echo "\n\r";
	}	
}



$sdaio_tcp = new sda12io_tcp("crrskinner.homeip.net", 8010);
if ($sdaio_tcp) {
	$sda12 = new sda12($sdaio_tcp);
	$sda12->realtime();
	
}






?>