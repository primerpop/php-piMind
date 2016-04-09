<?php 
chdir(__DIR__);



// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");
/**
 * The SDA12 TCP connector
 * @author Paul
 *
 */
class sda12io_tcp {
	// socket pointer
	private $_fopen_sock = 0;
	// current buffer
	private $_buffer = "";
	//expected number of bytes in a valid message for the specified number of channels.
	private $_expect_bytes = 0;
	/**
	 * Construct and connect
	 * @param string $host
	 * @param integer $port
	 * @param number $timeout
	 * @return number
	 */
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
	/**
	 * Send an !0RA message to the SDA12 to get data.
	 * @param integer $channels
	 */
	function send_poll($channels) {
		if ($this->_fopen_sock) {
			$this->_expect_bytes = ($channels + 1) * 2;
			// toss anything in the buffer.
			$toss = fread($this->_fopen_sock,1024);
			fwrite($this->_fopen_sock,"!0RA" .chr($channels));
		}	
	}
	/**
	 * Read from the socket into the buffer.
	 */
	function read() {
		$t_buff = fread($this->_fopen_sock,64);
		$this->_buffer .= $t_buff;
		
	}
	/**
	 * get the whole buffer and flush.
	 * @return string
	 */
	function get_buffer() {
		$data = $this->_buffer;
		$this->_buffer = "";
		return $data;
		
	}
	/**
	 * Get the buffer in chunks as a message for the expected number of channels.
	 * @return string
	 */
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
/**
 * The yet to be written serial handler.
 * @author Paul
 *
 */
class sda12io_serial {
	private $_dio_handle;
	function __construct($serial_device = "/dev/TTYS0") {
		
	}
	function send_poll($channels) {
		
	}
	function read() {
		
	}
}
/**
 * SDA12 message processing
 * @author Paul
 *
 */
class sda12 {
	private $_shutdown = 0;
	private $_sdaio_class = null;
	/**
	 * Constructor
	 * @param object $sdaio_instance an instance of the tcp or serial handlers above.
	 */
	function __construct($sdaio_instance) {
		$this->_sdaio_class = $sdaio_instance;
	}	
	function __destruct() {
		
	}
	function process_samples($samples) {
		var_dump($samples);
	}
	/**
	 * Realtime loop
	 * @return void
	 */
	function realtime() {
		
		while (!$this->_shutdown) {
			$this->_sdaio_class->send_poll(11);
			$buffer = $this->_sdaio_class->get_message();
			if ($buffer) {
				$samples = $this->process_message($buffer,11);
				$this->process_samples($samples);
			}
			usleep(500000);
		}
		
	}
	/**
	 * Process the two-byte pairs based on the number of channels specified
	 * @param string $message  The right number of byte pairs forabove (remember 1=0, max 11 for a total of 12)
	 * @param integer $nbr_channels
	 * @return array the samples and data
	 */
	function process_message($message, $nbr_channels) {
		static $message_count = 0;
		$message_count++;
		
		$byte_pairs = str_split($message, 2);
		$samples = array();
		foreach ($byte_pairs as $key => $pair) {
			$msb = $pair[0];
			$lsb = $pair[1];
			$sample = (ord($msb) * 256) + ord($lsb);
			$samples[$key] = array($sample,$sample/4095);
		}
		// to align with SDA 12 docs, the samples are reversed.  Easier to do it now
		// than inverse the whole program logic and add confusion.
		$samples = array_reverse($samples); 
		
		return $samples;
	}	
}

/**
 * SDA-12 bootstrap and kick into the realtime loop.
 */

$sdaio_tcp = new sda12io_tcp("crrskinner.homeip.net", 8010);
if ($sdaio_tcp) {
	$sda12 = new sda12($sdaio_tcp);
	$sda12->realtime();
	
}






?>