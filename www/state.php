<?php
// bring in common paths
include("../paths.php");
// get constants
include(PIMIND_HOME."/constants.php");

$state = json_decode(PIMIND_STATE .DIRECTORY_SEPARATOR . "controller-state-0.json",1);
var_dump($state);
?>