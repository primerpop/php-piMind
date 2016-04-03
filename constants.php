<?php
define("STANDBY",0);
define("ARMED",1);

// sensor types
define("SENSORTYPE_DOORSWITCH",1);
define("SENSORTYPE_MOTION",2);
define("SENSORTYPE_DATA",3);

// Event types
// Event from a sensor, works for 1s and 0s pretty much.
define("EVENT_TYPE_SENSOR",0);
// Event that was invoked by a handler
define("EVENT_TYPE_HANDLER",1);
// Event that has a variable data type
define("EVENT_TYPE_VARDATA",2);