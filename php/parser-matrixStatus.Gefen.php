<?php

/*
	_PARSER-MATRIXSTATUS.PHP
    Gefen 16x16 Matrix-specific html parser (v1.1)

    Daniel Skovli, 2013
    mail@danielskovli.com
    
    
	Takes no input.
	
	Extracts the status page from the switch's built-in web server, and outputs the result as JSON.
	
	
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
	
	NB: Inputs and outputs are integers from 0-15, representing the 16 inputs/outputs on the matrix switch. NOT using their gefencode/hex representation.
*/


// Headers
header('Content-Type: application/json');

// Load the config, Simple HTML DOM engine and function base
require('plugins/simple_html_dom.php');
require('functions.php');
require('config.php');

// Prepare the output
$json = array();
$json['error'] = false;
$switching = array();

// Fetch the status page
//@$html = file_get_html('http://'. $config['hosts']['matrix'] .'/index.shtml');
@$html = file('http://'. $config['hosts']['matrix'] .'/index.shtml');

// Make sure we've got a valid reply
if (!$html) {
	error('Unable to load matrix status');
	exit;
}

// Extract the data we need from the HTML file
$gefenRaw = $html[241]; // This is probably prone to break - but for now, line 242 is the one to get!
$gefenRaw = str_replace(";", ";$", $gefenRaw); // translate to PHP syntax
$gefenRaw = substr($gefenRaw, strpos($gefenRaw, ";")+1, -3); // sanitize

// Evaluate code as PHP
eval($gefenRaw); // dirty dirty dirty, fingers crossed

// Loop through the list we've gotten and translate to what we need
for ($i=0; $i<count($StatS); $i++) {
	$switching[] = array(
						'output' => $i,
						'input' => $StatS[$i]['in'] - 1,
						'status' => $StatS[$i]['stat'],
						'in_name' => $config['inputs'][$StatS[$i]['in']-1]['name'],
						'out_name' => $config['outputs'][$i]['name']
					);
}


/* OLD METHOD....

	// Loop through the tables
	$switching = array();
	foreach ($html->find('#statusTable tr') as $row) {
		
		$td = $row->find('td');
		$switching[] = "test";
		if (count($td) > 0) {
			$switching[] = array(
								'output' => $td[0]->plaintext.split('_')[1] -1,
								'input' => $td[1]->plaintext.split('_')[1] -1,
								'status' => $td[2]->plaintext.split('_')[1]
							);
		}
		
	}
*/

$json['switching'] = $switching;

// Output the result as JSON
echo json_encode($json);
//print_r($json);

?>