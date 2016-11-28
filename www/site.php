<?php 

if (isset($_GET["controller_id"])) {
	$controller_id = filter_input(INPUT_GET,"controller_id");
	$state = file_get_contents(PIMIND_STATE . DIRECTORY_SEPARATOR . "controller-state-".$controller_id.".json");
	print_r($state);
}

?>