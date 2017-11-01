<?php

/*
	_INTERACTION-PROJECTOR.PHP
    Panasonic DZ6710-specific interaction (v1.0)

    Daniel Skovli, 2012
    mail@danielskovli.com
    
    
	Sends various commands to the specified projector via its webserver. Has the following capabilities:
		Turn power on/off
		Turn shutters on/off
		Focus 3 different steps in each direction
		Zoom 3 different steps in each direction
		Vertical lens shift 3 different steps in each direction
		Horizontal lens shift 3 different steps in each direction
	
	
	Usage:
		interaction-projector.php?projector=projector_left&command=[command]&value=[value]
		(use the projector-variable names specified in config.php when calling this function)
		
		[command] refers to one of the following commands:
			on
			off
			normal
			blackout
			focus    (needs [value])
			zoom     (needs [value])
			hshift   (needs [value])
			vshift   (needs [value])
		
		[value] refers to one of the following values:
			inc1     (increases value by a 1-step >)
			inc2     (increases value by a 2-step >>)
			inc3     (increases value by a 3-step >>>)
			dec1     (decreases value by a 1-step <)
			dec2     (decreases value by a 2-step <<)
			dec3     (decreases value by a 3-step <<<)


	Returns:
		The following JSON structure:
		
		ON ERROR:
			Array (
				[error] => (bool) true
				[error_reason] => (str) reason for error
			)
		
		ON SUCCESS:
			Array (
				[error] => (bool) false
			)
*/


// Headers
header('Content-Type: application/json');

// Load the config, Simple HTML DOM engine and function base
require('functions.php');
require('config.php');

// Setup the acceptable commands and values
$commands_single = array('on', 'off', 'normal', 'blackout');
$commands_needValue = array('focus', 'zoom', 'hshift', 'vshift');
$values = array('inc1', 'inc2', 'inc3', 'dec1', 'dec2', 'dec3');

// Prepare the output
$json = array();
$json['error'] = false;

// Get input and output values
$projector = $_GET['projector'];
$command = $_GET['command'];
$value = $_GET['value'];

// Check that we got a projector and command value in
if (empty($projector) || empty($command)) {
	error('No projector/command supplied');
	exit;
}

// Check that the projector exists in the config
if (!array_key_exists($projector, $config['hosts'])) {
	error('Invalid projector name');
	exit;
}

// Check that we have an valid command
if (!in_array($command, $commands_single) && !in_array($command, $commands_needValue)) {
	error('Invalid command name');
	exit;
}


// Check that we have a value if the command calls for it
if (in_array($command, $commands_needValue) && !in_array($value, $values)) {
	error('Invalid value supplied for this command');
	exit;
}

// Create a base URL since all URLs start with the same sequence
$baseURL = 'http://'. $config['credentials'][$projector]['username'] .':'. $config['credentials'][$projector]['password'] .'@'. $config['hosts'][$projector] .'/cgi-bin/';

// Go through all the different possibilities and send the commands
if ($command == "on") {
	file_get_contents($baseURL . 'power_on.cgi');

} elseif ($command == "off") {
	file_get_contents($baseURL . 'power_off.cgi');

} elseif ($command == "normal") {
	file_get_contents($baseURL . 'proj_ctl.cgi?key=shutter_off&lang=e&osd=on');

} elseif ($command == "blackout") {
	file_get_contents($baseURL . 'proj_ctl.cgi?key=shutter_on&lang=e&osd=on');

} elseif ($command == "focus") {
	file_get_contents($baseURL . 'proj_ctl.cgi?key=lens_focus_'. $value .'&lang=e&osd=on');

} elseif ($command == "zoom") {
	file_get_contents($baseURL . 'proj_ctl.cgi?key=lens_zoom_'. $value .'&lang=e&osd=on');

} elseif ($command == "hshift") {
	file_get_contents($baseURL . 'proj_ctl.cgi?key=lens_hshift_'. $value .'&lang=e&osd=on');

} elseif ($command == "vshift") {
	file_get_contents($baseURL . 'proj_ctl.cgi?key=lens_vshift_'. $value .'&lang=e&osd=on');

} else {
	error('Unknown error - please report this! Occurred while parsing logical patterns for projector I/O');
	exit;
}


// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>