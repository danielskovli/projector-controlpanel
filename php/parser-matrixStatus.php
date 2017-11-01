<?php


/*
  _PARSER-MATRIXSTATUS.PHP
    Kramer 16x16 Matrix-specific Ethernet (ASCII)/Serial parser (v1.0)

    Daniel Skovli, 2014
    mail@danielskovli.com
    
    
  Takes no input.
  
  Extracts the status page from the switch's Ethernet socket connection, and outputs the result as JSON.
  
  
  Usage:
    parser-matrixStatus.php
  
  
  Returns:
    The following JSON structure:
    
    ON ERROR:
      Array(
        [error] => (bool) true
        [error_reason] => (str) reason for error
    
    ON SUCCESS:
      Array(
        [error] => (bool) false
        [switching] => Array(
                [0] => Array(
                    [output] => (int) 0
                    [input]  => (int) 0
                    [status] => (str) Active
                    [out_name] => (str) foo
                    [in_name]  => (str) bar
                  )

                [1] => Array(
                    [output] => (int) 1
                    [input]  => (int) 1
                    [status] => (str) Active
                    [out_name] => (str) baz
                    [in_name]  => (str) qux
                  )
                ...
                [n] => Array(
                    [output] => (int) n
                    [input]  => (int) n
                    [status] => (str) Active
                    [out_name] => (str) ...
                    [in_name]  => (str) ...
                  )
                )
      )
  
  NB: Inputs and outputs are integers from 0-15, representing the 16 inputs/outputs on the matrix switch. Internally the Kramer uses 1-17 though, but we're sending 0-15 back to the GUI.
*/


// Headers
header('Content-Type: application/json');

// Load the config and function base
require('functions.php');
require('config.php');

// Prepare the output
$json = array();
$json['error'] = false;
$switching = array();

// Some socket and matrix settings
$timeout = array('sec'=>1, 'usec'=> 500000);
$command = "#VID? *\r";
$retryAttempts = 4;


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

	// Parse the reply
	preg_match_all("/(\d{1,2}>\d{1,2}.+\d{1,2}>\d{1,2})/", $reply, $matches);
	if (count($matches) < 2) {
		if ($x < $retryAttempts) {
			usleep(200000);
			continue;
		}
		error('Received incomplete reply from switch (\''.$reply.'\')');
		socket_close($socket);
		exit;
	}

	$matrix = explode(",", $matches[1][0]);
	foreach ($matrix as $route) {
		$tmp = explode(">", $route);
		$input = trim($tmp[0]) -1;
		$output = trim($tmp[1]) -1;
		
		$switching[] = array(
	          'output' => $output,
	          'input' => $input,
	          'status' => "Active",
	          'in_name' => $config['inputs'][$input]['name'],
	          'out_name' => $config['outputs'][$output]['name']
	        );
	}

	// Validate again
	if (count($switching) != 16) {
		if ($x < $retryAttempts) {
			usleep(100000);
			continue;
		}
		error('Received incomplete reply from switch (\''.$reply.'\')');
		socket_close($socket);
		exit;

	// Close socket and move on
	} else {
		socket_close($socket);
		break;
	}
}

$json['switching'] = $switching;

// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>