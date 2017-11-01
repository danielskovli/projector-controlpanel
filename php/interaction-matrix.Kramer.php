<?php

/*
	_INTERACTION-MATRIX.PHP
    Kramer 16x16 Matrix-specific switching trigger (v1.0)

    Daniel Skovli, 2014
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

// Some socket and matrix settings
$timeout = array('sec'=>1, 'usec'=> 500000);
$command = "#VID %s>%s\r"; // #VID input>output
$retryAttempts = 4;

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

// Update our command with input and output
$command = sprintf($command, $input+1, $output+1);


// Putting this socket call in a loop because on some occasions the socket buffer screws up, and we need to run the commands one more time.
// Giving it a few iterations for good luck...
for ($x=1; $x<=$retryAttempts; $x++) {

	// Create/clear the switching array
	$switching = array();

	// Create the socket
	socket_clear_error();
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$timeout);
	
	if ($socket === false) {
		if ($x < $retryAttempts) {
			usleep(200000);
			continue;
		}
    	error('Unable to create socket connection to matrix switch ('. socket_strerror(socket_last_error()) .')');
    	socket_close($socket);
    	exit;
	}

	// Connect to socket
	if (!socket_connect($socket, $config['hosts']['matrix_kramer'], $config['hosts']['matrix_kramer_port'])) {
		if ($x < $retryAttempts) {
			usleep(200000);
			continue;
		}
		error('Unable to connect to matrix switch, socket failed to connect ('.socket_strerror(socket_last_error()).')');
		socket_close($socket);
		exit;
	}

	// Write data
	if (socket_write($socket, $command, strlen($command)) === false) {
		if ($x < $retryAttempts) {
			usleep(200000);
			continue;
		}
		error('Unable to send request through the open socket. Maybe it was forced closed? ('.socket_strerror(socket_last_error()).')');
		socket_close($socket);
		exit;
	}

	// Read the reply
	$reply = socket_read($socket, 2048, PHP_NORMAL_READ);
	if ($reply === false) {
		if ($x < $retryAttempts) {
			usleep(200000);
			continue;
		}
		error('Unable to read from socket. Was it forced close? ('.socket_strerror(socket_last_error()).')');
		socket_close($socket);
		exit;
	}

	// Parse the reply - look for errors
	if (stripos($reply, "err") !== false) {
		if ($x < $retryAttempts) {
			usleep(200000);
			continue;
		}
		error('There was a problem with the switching command (\''.$command.'\' => \''.$reply.'\')');
		socket_close($socket);
		exit;
	}

	// Close the socket and move on
	socket_close($socket);
	break;
}


// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>