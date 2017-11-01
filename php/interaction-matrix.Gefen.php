<?php

/*
	_INTERACTION-MATRIX.PHP
    Gefen 16x16 Matrix-specific switching trigger (v1.1)

    Daniel Skovli, 2012
    mail@danielskovli.com
    
    
	Takes input and output values in the form of numbers between 0 and 15. 
	Sends the command directly to the switch and returns either positive or negative, depending on the result.
	
	
	Usage:
		interaction-matrix.php?input=0&output=6
	
	
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

// Prepare the output
$json = array();
$json['error'] = false;

// Get input and output values
$input = $_GET['input'];
$output = $_GET['output'];

// Check consistency/sanitize
if (!isset($input) || !isset($output) || !is_numeric($input) || !is_numeric($output)) {
	error('No/incorrect input/output supplied');
	exit;
}

if ($input > 15 || $input < 0 || $output > 15 || $output < 0) {
	error('Input/output out of bounds');
	exit;
}

// Convert the integers to HEX and send the request
//file_get_contents('http://'. $config['hosts']['matrix'] .'/cgibin.shtml?a=s&o='. $config['gefencodes'][$output] .'&i='. $config['gefencodes'][$input]);
file_get_contents('http://10.148.192.53/actionHandler.shtml?a=switch&r=index.shtml&dEdid='. ($output+1) .'&out'. $output .'='. ($output+1) .'&in='. ($input+1));


// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>