<?php

/*
	_PARSER-CONFIGREADER.PHP

    Daniel Skovli, 2013
    mail@danielskovli.com
    
    
	Takes no input.
	
	Extracts the content from config.php and returns a JSON structure.
	
	
	Usage:
		parser-configReader.php
	
	
	Returns:
		The following JSON structure:
		
		ON ERROR:
			Array(
				[error] => (bool) true
				[error_reason] => (str) reason for error
		
		ON SUCCESS:
			Array(
				[error] => (bool) false
				[config] => (mixed) the whole $config array
			)
	
*/


// Headers
header('Content-Type: application/json');

// Load the config
include('config.php');

// Prepare the output
$json = array();
$json['error'] = false;

// Quick test
if (empty($config)) {
	$json['error'] = true;
	$json['error_reason'] = "Looks like we couldn't find the config file...";
} else {
	$json['config'] = $config;
}

// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>