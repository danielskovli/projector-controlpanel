<?php

/*
	_PARSER-PROJECTORSTATUS.PHP
    Panasonic DZ6710-specific html parser (v1.0)

    Daniel Skovli, 2012
    mail@danielskovli.com
    
	
	Extracts the status page from the projector's built-in web server, and outputs the result as JSON.
	
	
	Usage:
		parser-projectorStatus.php?projector=projector_left
		(use the projector-variable names specified in config.php when calling this function)
	
	
	Returns:
	
		ON ERROR:
			Array (
				[error] => (bool) true
				[error_reason] => (str) reason for error
			)
		
		ON SUCCESS:
			Array(
				[error] => (bool) false
				[projector_type] => (str) DZ6710
				[serial_number] => (str) SH9440007
				[main_version] => (str) 1.04
				[network_version] => (str) 1.00
				[power] => (str) off
				[remote2_status] => (str) off
				[shutter] => (str) na
				[osd] => (str) na
				[lamp_mode] => (str) na
				[lamp_power] => (str) na
				[input_source] => (str) na
				[input_details] => (str) na
				[air_temp_ambient] => Array(
						[celcius] => (int) 28
						[fahrenheit] => (int) 82
					)

				[air_temp_optics] => Array(
						[celcius] => (int) 30
						[fahrenheit] => (int) 86
					)

				[air_temp_lamp] => Array(
						[celcius] => (int) 25
						[fahrenheit] => (int) 77
					)

				[lamp1_status] => (str) off
				[lamp1_runtime] => (int) 598
				[lamp2_status] => (str) off
				[lamp2_runtime] => (int) 598
				[filter_remaining] => (int) 100
				[projector_runtime] => (int) 2498
				[self_check] => (str) no errors
				[request_name] => str projector_left
			)
	
	NB: If any value is unavailable at the time (projector off, etc), the value 'na' will be returned.
*/


// Headers
header('Content-Type: application/json');

// Load the config, Simple HTML DOM engine and function base
require('plugins/simple_html_dom.php');
require('functions.php');
require('config.php');

// Prepare the output
$output = array();
$output['error'] = false;


/*
$output['request_name'] = $_GET['projector'];
$output['projector_type'] = 'DZ6710';
$output['serial_number'] = '';
$output['network_version'] = '';
$output['power'] = 'on';
$output['remote2_status'] = '';
$output['shutter'] = 'off';
$output['osd'] = '';
$output['lamp_mode'] = '';
$output['lamp_power'] = '';
$output['input_source'] = '';
$output['input_details'] = '';
$output['air_temp_ambient'] = array(
			'celcius' => '',
			'fahrenheit' => ''
		);
$output['air_temp_optics'] = array(
			'celcius' => '',
			'fahrenheit' => ''
		);
$output['air_temp_lamp'] = array(
			'celcius' => '',
			'fahrenheit' => ''
		);
$output['lamp1_status'] = '';
$output['lamp1_runtime'] = '100';
$output['lamp2_status'] = '';
$output['lamp2_runtime'] = '100';
$output['filter_remaining'] = '100';
$output['projector_runtime'] = '100';
$output['self_check'] = 'no errors'; 

echo json_encode($output);

exit;
*/



// Get target host
$projector = $_GET['projector'];
if (empty($projector)) {
	error('No/incorrect projector supplied');
	exit;
}

// Check that the projector exists in the config
if (!array_key_exists($projector, $config['hosts'])) {
	error('Invalid projector name');
	exit;
}

// Fetch the status page
@$html = file_get_html('http://'. $config['credentials'][$projector]['username'] .':'. $config['credentials'][$projector]['password'] .'@'. $config['hosts'][$projector] .'/cgi-bin/projector_status.cgi?lang=e');

// Make sure we've got a valid reply
if (!$html) {
	error('Unable to load projector status');
	exit;
}

// Attach back the request variable name (ie. projector_left)
$output['request_name'] = $projector;

// Loop through the tables
foreach ($html->find('body', 0)->children() as $row=>$table) {
		
	// Row 1 - Projector type and serial number
	if ($row == 0) {
		$td = $table->find('table > td');
		$output['projector_type'] = preg_replace("/&nbsp;/", '', $td[1]->plaintext);
		$output['serial_number'] = preg_replace("/&nbsp;/", '', $td[3]->plaintext);
		
		continue;
	}
	
	
	// Row 2 - Version
	if ($row == 1) {
		$td = $table->find('table > td');
		$output['main_version'] = preg_replace("/&nbsp;/", '', $td[1]->plaintext);
		$output['network_version'] = preg_replace("/&nbsp;/", '', $td[3]->plaintext);
	}
	
	
	// Row 3 - Power and remote2 status
	if ($row == 2) {
		$td = $table->find('table > td');
		$output['power'] = (preg_replace("/&nbsp;/", '', $td[1]->find('font[color=#00ff12]', 0)->plaintext) == "ON") ? 'on' : 'off';
		$output['remote2_status'] = (preg_replace("/&nbsp;/", '', $td[3]->find('font[color=#00ff12]', 0)->plaintext) == "ENABLE") ? 'on' : 'off';
		
		continue;
	}
	
	
	// Row 4 - Shutter and OSD
	if ($row == 3) {
		
		// If power off...
		if (count($table->find('table > td')) < 4) {
			$output['shutter'] = "na";
			$output['osd'] = "na";
		
		// Else parse as normal
		} else {
			$td = $table->find('table > td');
			$output['shutter'] = (preg_replace("/&nbsp;/", '', $td[1]->find('font[color=#00ff12]', 0)->plaintext) == "ON") ? 'on' : 'off';
			$output['osd'] = (preg_replace("/&nbsp;/", '', $td[3]->find('font[color=#00ff12]', 0)->plaintext) == "ON") ? 'on' : 'off';
		}
		continue;
	}
	
	
	// Row 5 - Lamp select and lamp power
	if ($row == 4) {
		
		// If power off...
		if (count($table->find('table > td')) < 4) {
			$output['lamp_mode'] = "na";
			$output['lamp_power'] = "na";
		
		// Else parse as normal
		} else {
			$td = $table->find('table > td');
			$output['lamp_mode'] = strtolower(preg_replace("/&nbsp;/", '', $td[1]->plaintext));
			$output['lamp_power'] = strtolower(preg_replace("/&nbsp;/", '', $td[3]->plaintext));
		}
		continue;
	}
	
	
	// Row 6 - Input details
	if ($row == 5) {
	
		// If power off...
		if (count($table->find('table > td')) < 3) {
			$output['input_source'] = "na";
			$output['input_details'] = "na";
		
		// Else parse as normal
		} else {
			$td = $table->find('table > td');
			$output['input_source'] = preg_replace("/&nbsp;/", '', $td[1]->find('font[color=#00ff12]', 0)->plaintext);
			$output['input_details'] = preg_replace("/&nbsp;/", '', $td[2]->find('font[color=#00ff12]', 0)->plaintext);
		}
		continue;
	}
	
	
	// Row 7 - Input air temp
	if ($row == 6) {
	
		$td = $table->find('table > td');
		$c=preg_match_all ("/(\\d+)(&)(deg)(;).*?(\\d+)(&)(deg)(;)/is", $td[1]->plaintext, $matches);
		$output['air_temp_ambient'] = array(
			'celcius' => $matches[1][0],
			'fahrenheit' => $matches[5][0]
		);
	
		continue;
	}
	
	
	// Row 8 - Optics module air temp
	if ($row == 7) {
		$td = $table->find('table > td');
		$c=preg_match_all ("/(\\d+)(&)(deg)(;).*?(\\d+)(&)(deg)(;)/is", $td[1]->plaintext, $matches);
		$output['air_temp_optics'] = array(
			'celcius' => $matches[1][0],
			'fahrenheit' => $matches[5][0]
		);
		
		continue;
	}
	
	
	// Row 9 - Lamp air temp
	if ($row == 8) {
		$td = $table->find('table > td');
		$c=preg_match_all ("/(\\d+)(&)(deg)(;).*?(\\d+)(&)(deg)(;)/is", $td[1]->plaintext, $matches);
		$output['air_temp_lamp'] = array(
			'celcius' => $matches[1][0],
			'fahrenheit' => $matches[5][0]
		);
	
		continue;
	}
	
	
	// Row 10 - Lamp 1 details
	if ($row == 9) {
		$td = $table->find('table > td > font[color=#00ff12]');
		$output['lamp1_status'] = strtolower(preg_replace("/&nbsp;/", '', $td[0]->plaintext));
		$output['lamp1_runtime'] = filter_var(preg_replace("/&nbsp;/", '', $td[1]->plaintext), FILTER_SANITIZE_NUMBER_INT);
		
		continue;
	}
	
	
	// Row 11 - Lamp 2 details
	if ($row == 10) {
		$td = $table->find('table > td > font[color=#00ff12]');
		$output['lamp2_status'] = strtolower(preg_replace("/&nbsp;/", '', $td[0]->plaintext));
		$output['lamp2_runtime'] = filter_var(preg_replace("/&nbsp;/", '', $td[1]->plaintext), FILTER_SANITIZE_NUMBER_INT);
		
		continue;
	}
	
	
	// Row 12 - Remaining filter details
	if ($row == 11) {
		$output['filter_remaining'] = filter_var(preg_replace("/&nbsp;/", '', $table->find('table > td > font[color=#00ff12]', 0)->plaintext), FILTER_SANITIZE_NUMBER_INT);
		continue;
	}
	
	
	// Row 13 - Projector runtime and self test
	if ($row == 12) {
		$td = $table->find('table > td');
		$output['projector_runtime'] = filter_var(strtolower(preg_replace("/&nbsp;/", '', $td[1]->plaintext)), FILTER_SANITIZE_NUMBER_INT);
		$output['self_check'] = strtolower(preg_replace("/&nbsp;|&nbsp/", '', $td[3]->plaintext)); // includes fixed regex for malformed HTML from the projector...
	
		continue;
	}
	
}


// Output the result as JSON
echo json_encode($output);
//print_r($output);

?>