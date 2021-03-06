# php-piMind
PHP code for home monitoring and event processing.  This is all in development in a varied state of disarray.  Good luck.

#Requires 
- PHP 5.4+
- SPI php extension installed.
- raspberrypi-face 
- PAM php extension (soon)

- twilio-php for SMS functions or write your own wrapper.

Quick and dirty (emphasis on dirty) install (once you have SPI installed):

- git clone https://github.com/primerpop/php-piMind
- cd php-piMind
- touch composer.json
- echo '{ "require": { "pkj/raspberry-piface-api": "dev-master" } }' >> composer.json
- curl -sS https://getcomposer.org/installer | php
- php composer.phar install

piMind's goal is to automate home monitoring and event handling using a combination of PHP, Apache, and the Linux IPC messaging queue system.

It is broken down to a number of components that facilitiate distribution and task offloading to any number of monitoring devices.

Basic data flow:

A script in /sensor does whatever it has to do to gather it's data and provide a first pass of desired logic.  This script can be started from /etc/rc.local or manually... It collects data and calls raise-event() which fires a quick url_encoded message to the redirector including the pre-shared secret. 

The redirector receives the message from the sensor script and "redirects" the message to the URLs indicated in the call back provided by the controller at the time of it's registration, again employing a pre-shared secret.

The controller in this package exposes a script named /event that receives the message from the redirector and proceeds to insert the message into the IPC message queue defined in controller.ini.


 


## Sensors

PHP CLI progams that look at whatever data needs examination.  Once a state change or desired data is seen, either blindly or with some detection logic, the sensors signal that an event occurred and raises an event to the redirector.  Events are lightly structured JSON messages that are freeform, no particular struct is imposed, but must provide some elements.

There are currently 2 sensor scripts;
- piface-spi.php which listens to piFace enhanced RaspberryPI boards and 
- mac.php which, using nmap parses the output to signal the arrival and departure of MAC addresses to the network.

## Messages 

A JSON array

{"pin":0,"type":2,"label":"An optional label","ts":1459696548,"state":0,"sensor_group":"A name for the sensor group"}

pin:  For sensor based devices, pin should map through the configs,  sensor pin 0 should map to the same sensor definition in sensors.ini.

label:  The element should be provided, but can be empty, it's really more for debugging the sensor.

ts: a unix timestamp at the time the event occured

state: the state of the pin.  Current logic uses 0 = off/closed 1= on/open.  

sensor_group:  the name of the sensor group, an analog to the piMind sensor that is raising the state change.

type: 0 - hardware sensor message (strict binary 1/0) (EVENT_TYPE_SENSOR), 
      1 - event fed into the controller from a handler (EVENT_TYPE_HANDLER) 
	  2 - event containing variable data (flexible data fields) (EVENT_TYPE_VARDATA)
	  
class: a name unique to sensor (examples use constructs like $data["class"] =get_class($this);)  


## Redirector

Software that provides a common end-point for sensors to relay their data to.  The redirector does 3 things:  

- it accepts registrations for new controllers  
- catches events raised by the sensors 
- redirects the events to the registered controllers  

(Controller registrations are a little clumsy right now, so don't judge too harshly)

http://host/pimind/redirector.php?action=register_controller&url=http://localhost/primind/event.php

The redirector is fairly blind,  raised events must include a "secret" field on the query string along with a url_encoded json "data" field of the Message described above.  Secret is shared in the config files, say like mac.ihi, piface-spi.ini and redirector.ini


The redirector blindly POSTS the received event to the URL the controller provided as a call back during it's registration.  This occurs for every controller for every event.

## Event

The event script is the feeder to the IPC message queue.  It receives events to the URL registered as call back to the redirector.  The event script takes the POSTED data from the redirector and sends into the IPC message queue in non-blocking mode.

## Controller

The controller processes the IPC message queue and manages handlers.  Handlers are signalled by events and by interval ticks.  

## Handlers

Do whatever logic and analysis of the event data to make something happen.  
- opendoor is a handler that checks and complains about doors left open.
- handler_watchdog just print_r's events for debugging.


