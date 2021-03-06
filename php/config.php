<?php

/*
	Configuration for the Control Panel application
	
	This system makes the assumption that you have exactly two (2) public display projectors,
	one for the left image and one for the right image in a stereo pair. Throughout the whole application
	these projectors will have the ID labels "projector_right" and "projector_left".
	
	Core functionality will remain stable without this being true, but the projector specific integration will be 
	completely useless. 
	
	As for the type of projector supported, please refer to parser-projectorStatus.php and interaction-projector.php
*/


// Misc PHP configuration
error_reporting(0); // turn off error reporting completely
ini_set('default_socket_timeout', 3); // define the timeout for socket/web requests


// IP ADDRESSES
$config['hosts'] = array(
						'matrix' => '10.148.192.53',
						'matrix_kramer' => '10.148.192.54',
						'matrix_kramer_port' => 5000,
						'projector_right' => '10.148.192.48',
						'projector_left' => '10.148.192.47'
					);


// CREDENTIALS
$config['credentials'] = array(
							'projector_right' => array('username'=>'admin1', 'password'=>'panasonic'),
							'projector_left' => array('username'=>'admin1', 'password'=>'panasonic')
						);


// SHUTTER TIMER 
// (how long to close the projector shutters for while switching)
// Outputs that need this timeout must set the `isProjector` flag in the config below
$config['shutter_timer'] = 300; // time in milliseconds


// iBOOT DETAILS
$config['iboot'] = array(
						'host' => '10.148.192.50',
						'port' => 80,
						'password' => "\x1bPASS\x1b",
						'commands' => array(
										'query' => "q\r", 
										'on' => "n\r", 
										'off' => "f\r"
									)
					);


// DISPLAY ORDER FOR INPUTS AND OUTPUTS 
// (will be discarded if it does not match the $config['inputs'] and $config['outputs'] arrays -- you've been warned)
$config['inputs_displayOrder']  = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15);
$config['outputs_displayOrder'] = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15);


// PRESETS
// Switching is defined in an associative array where the key is the display, and the value is the input (mon => inp.)
$config['presets'] = array(
            /*
						array(
							'name' => 'Default workstation layout',
							'switching' => array(2=>0, 3=>1, 4=>2, 5=>3, 6=>4, 7=>5, 8=>6, 9=>7, 10=>10),
							'img' => '',
							'enabled' => true
						)
            */
					);


// SWITCH INPUTS
$config['inputs'] = array(
						
						// Input 0
						array(
							'name' => 'Mocap 1 <br /><strong>Left</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 1
						array(
							'name' => 'Mocap 1 <br /><strong>Right</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 2
						array(
							'name' => 'Mocap 2',
							'img' => '',
							'enabled' => true
						),
						
						// Input 3
						array(
							'name' => 'Mocap 3 <br /><strong>Left</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 4
						array(
							'name' => 'Mocap 3 <br /><strong>Right</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 5
						array(
							'name' => 'Mocap 4 <br /><strong>Left</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 6
						array(
							'name' => 'Mocap 4 <br /><strong>Right</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 7
						array(
							'name' => 'N/A',
							'img' => '',
							'enabled' => false
						),
						
						// Input 8
						array(
							'name' => 'Roland <br /><strong>Left</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 9
						array(
							'name' => 'Roland <br /><strong>Right</strong>',
							'img' => '',
							'enabled' => true
						),
						
						// Input 10
						array(
							'name' => 'Breakout #1<br />(DVI 1)',
							'img' => '',
							'enabled' => true
						),
						
						// Input 11
						array(
							'name' => 'Breakout #2<br />(DVI 2)',
							'img' => '',
							'enabled' => true
						),
						
						// Input 12
						array(
							'name' => 'N/A',
							'img' => '',
							'enabled' => false
						),
						
						// Input 13
						array(
							'name' => 'N/A',
							'img' => '',
							'enabled' => false
						),
						
						// Input 14
						array(
							'name' => 'N/A',
							'img' => '',
							'enabled' => false
						),
						
						// Input 15
						array(
							'name' => 'N/A',
							'img' => '',
							'enabled' => false
						)
					);


// SWITCH OUTPUTS
$config['outputs'] = array(
						
						// Output 0
						array(
							'name' => 'Projector <br /><strong>Left</strong>',
							'img' => '',
							'isProjector' => true,
							'projectorID' => 'projector_left',
							'enabled' => true
						),
						
						// Output 1
						array(
							'name' => 'Projector <br /><strong>Right</strong>',
							'img' => '',
							'isProjector' => true,
							'projectorID' => 'projector_right',
							'enabled' => true
						),
						
						// Output 2
						array(
							'name' => 'Roland Left <br /><strong>Channel 1</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 3
						array(
							'name' => 'Roland Left <br /><strong>Channel 2</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 4
						array(
							'name' => 'Roland Left <br /><strong>Channel 3</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 5
						array(
							'name' => 'Roland Left <br /><strong>Channel 4</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 6
						array(
							'name' => 'Roland Right <br /><strong>Channel 1</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 7
						array(
							'name' => 'Roland Right <br /><strong>Channel 2</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 8
						array(
							'name' => 'Roland Right <br /><strong>Channel 3</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 9
						array(
							'name' => 'Roland Right <br /><strong>Channel 4</strong>',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 10
						array(
							'name' => 'Virtual Camera',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 11
						array(
							'name' => 'Vid Ref Monitor',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 12
						array(
							'name' => 'N/A',
							'img' => '',
							'isProjector' => false,
							'enabled' => false
						),
						
						// Output 13
						array(
							'name' => 'N/A',
							'img' => '',
							'isProjector' => false,
							'enabled' => false
						),
						
						// Output 14
						array(
							'name' => 'Tripod Monitor',
							'img' => '',
							'isProjector' => false,
							'enabled' => true
						),
						
						// Output 15
						array(
							'name' => 'N/A',
							'img' => '',
							'isProjector' => false,
							'enabled' => false
						)
					);
?>																																																																																																									