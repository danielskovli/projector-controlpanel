<?php

/*
	_INTERACTION-iBOOT.PHP
    iBoot 3.1c.103-specific on/off trigger (v1.0)

    Daniel Skovli, 2012
    mail@danielskovli.com
    
    
	Powers the iBoot device on or off, according to the command supplied by the user.
	
	
	Usage:
		interaction-iboot.php?command=on|off
	
	
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

// Load the config and function base
require('functions.php');
require('config.php');

// Prepare the output
$json = array();
$json['error'] = false;

// Get command
$command = $_GET['command'];

// Check for accepted commands
if ($command != 'on' && $command != 'off') {
	error('No/invalid command');
	exit;
}

// Run the commands
$fp = fsockopen($config['iboot']['host'], $config['iboot']['port'], $errno, $errstr, 10);
$reply = "";

if (!$fp) {
	$json['error'] = true;
	$json['error_reason'] = $errstr ."(". $errno .")";
} else {
	fwrite($fp, $config['iboot']['password'] . $config['iboot']['commands'][$command]);
	$reply = fread($fp, 5);
	fclose($fp);
}

// Check the returned value for errors
if (substr($reply, 0, 5) == "error") {
	error('Unspecified iBoot error');
	exit;
}

// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>