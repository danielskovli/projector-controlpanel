<?php

/*
	PARSER-iBOOT.PHP
    iBoot 3.1c.103-specific status fetcher (v1.0)

    Daniel Skovli, 2012
    mail@danielskovli.com
    
    
	Takes no input.
	Queries the iBoot device for current power status.
	
	
	Usage:
		parser-iboot.php
	
	
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
				[status] => (str) on|off
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

// Run the command
$fp = fsockopen($config['iboot']['host'], $config['iboot']['port'], $errno, $errstr, 5);
$reply = "";

if (!$fp) {
	$json['error'] = true;
	$json['error_reason'] = $errstr ."(". $errno .")";
} else {
	fwrite($fp, $config['iboot']['password'] . $config['iboot']['commands']['query']);
	$reply = strtolower(fread($fp, 5));
	fclose($fp);
}

// Check the returned value for errors
if (substr($reply, 0, 5) == "error") {
	error('Unspecified iBoot error');
	exit;
}

// Convert iBoot code to readable values
if (substr($reply, 1, 1) == 'n') {
	$json['status'] = 'on';
} elseif (substr($reply, 1, 1) == 'f') {
	$json['status'] = 'off';
}

// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>