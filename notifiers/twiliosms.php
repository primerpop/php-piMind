<?php

// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");

class twiliosms {
	private $_account_sid = 0;
	private $_auth_token = 0;
	private $_from_phone = "";
	
	public function __construct() {
		// this line loads the library
		require(PIMIND_NOTIFIERS_SOURCE.'/twilio-php/Services/Twilio.php');
		$this->read_configuration();
				
	}
	
	public function send_message($to_phone,$message) {
		$client = new Services_Twilio($this->_account_sid, $this->_auth_token);
		
		$ret = $client->account->messages->create(array(
				
				'To' => $to_phone,
				'From' => $this->_from_phone,
				'Body' => $message,
		));
		
		return $ret;
		
	}
	public function list_messages() {
		$client = new Services_Twilio($this->_account_sid, $this->_auth_token);
		
		// Loop over the list of messages and echo a property for each one
		foreach ($client->account->messages as $message) {
			$messages[$message->sid] = array("date_sent"=>$message->DateSent, "from" => $message->from ,"body" => $message->body);

		}
		if (count($messages)){
			return $messages;
		} 
		return 0;
	}
	public function read_message($msg_id) {
		$client = new Services_Twilio($this->_account_sid, $this->_auth_token);
		$msg = array("date_sent"=>$client->account->messages->get($msg_id)->DateSent, "from" => $client->account->messages->get($msg_id)->from ,"body" => $client->account->messages->get($msg_id)->body);
		$client->account->messages->get($msg_id)->delete();
	}
	function read_configuration() {
	
		$this->_configuration = parse_ini_file(PIMIND_CONFIG .DIRECTORY_SEPARATOR . "twiliosms.ini");
	
	
		if (isset($this->_configuration["account_sid"])) {
			$this->_account_sid = $this->_configuration["account_sid"];
		}
		if (isset($this->_configuration["auth_token"])) {
			$this->_auth_token = $this->_configuration["auth_token"];
		}
		if (isset($this->_configuration["from_phone"])) {
			$this->_from_phone = $this->_configuration["from_phone"];
		}
		
	}
}

$t = new twiliosms;
$r = $t->list_messages();
//print_r($r);
foreach ($r as $sid => $message) {
	$t->read_message($sid);
}
$r = $t->list_messages();

$t1= ($t->send_message("+16138078143", "test 1234 MEssage from my keyboard"));