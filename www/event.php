<?php
// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");

$message_queue_id = 0;
$mq_seqment = 0;
$config = parse_ini_file(PIMIND_STATE.DIRECTORY_SEPARATOR. "controller.ini");
foreach ($config as $prop => $value) {
	switch ($prop) {
		case "id":
                	$message_queue_id = $value;
	}
}
$msg_id = msg_get_queue ($message_queue_id, 0600);
$msg_err = 0;
$raw_post = file_get_contents("php://input");
msg_send ($msg_id, 1, $raw_post , true, false, $msg_err);

?>
