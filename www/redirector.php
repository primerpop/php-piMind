<?php

// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");


// catches the state change from the gpio monitor and send the event to the registered
// event controllers.

$controllers = array();

class controller {
	public $callback_url;
	public $secret = "";
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

if (file_exists("controllers.json")) {
	$data = file_get_contents(PIMIND_STATE .DIRECTORY_SEPARATOR ."controllers.serialize");
	if ($data) {
		$controllers = unserialize($data);
	}
}
$configuration = parse_ini_file(PIMIND_CONFIG.DIRECTORY_SEPARATOR."redirector.ini");
$config_secret = $configuration["secret"];

if (isset($_REQUEST["action"])) {
	$action = $_REQUEST["action"];
	switch ($action) {
		case "register_controller":
			if (isset($_REQUEST["url"])) {
				$np = new controller;
				$np->callback_url = $_REQUEST["url"];
				$controllers[] = $np;
			}
			break;
		case "event":
			$secret = "";
			if (isset($_REQUEST["secret"])) {
				$secret = $_REQUEST["secret"];
			}
			if ($secret == $config_secret) {
				if (isset($_REQUEST["data"])) {
					foreach ($controllers as $controller) {
						$controller->callback($_REQUEST["data"]);
					}
				}
			} else {
				header('HTTP/1.0 403 Forbidden');
			}
			break;
		default:
			break;

	}

}

if (count($controllers)) {
	$data = serialize($controllers);//,JSON_FORCE_OBJECT);
	file_put_contents(PIMIND_STATE .DIRECTORY_SEPARATOR ."controllers.serialize",$data);
}
?>
