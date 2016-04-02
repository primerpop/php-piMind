<?php

// catches the state change from the gpio monitor and send the event to the registered
// event processors.

$processors = array();

class processor {
	public $callback_url;
	public function callback($data) {
		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $this->callback_url);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, ($data));

		//execute post
		$result = curl_exec($ch);

		//close connection
		curl_close($ch);

	}
}

if (file_exists("processors.json")) {
	$data = file_get_contents("processors.json");
	if ($data) {
		$processors = unserialize($data);
	}
}
print_r($processors);
if (isset($_REQUEST["action"])) {
	$action = $_REQUEST["action"];
	switch ($action) {
		case "register_processor":
			if (isset($_REQUEST["url"])) {
				$np = new processor;
				$np->callback_url = $_REQUEST["url"];
				$processors[] = $np;
			}
			break;
		case "event":
			if (isset($_REQUEST["data"])) {
				foreach ($processors as $processor) {
					$processor->callback($_REQUEST["data"]);
				}
			}
			break;
		default:
			break;

	}

}

if (count($processors)) {
	$data = serialize($processors);//,JSON_FORCE_OBJECT);
	file_put_contents("processors.json",$data);
}
?>
