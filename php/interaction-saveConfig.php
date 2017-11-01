<?php

/*
	_INTERACTION-SAVECONFIG.PHP
    CodeMirror textfile editor implementation (v1.0)

    Daniel Skovli, 2014
    mail@danielskovli.com
    
    
	Receives a huge chunk of text via POST and saves this to file. Keeping a defined number of backups.
	
	
	Usage:
		interaction-saveConfig.php?authentic
		PS: the authentic flag is just to avoid misuse.
	
	
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

// Config
$filename = "config.php";
$backupname = "configBackups/config.php.bak";
$backupversions = 100;
$content = $_POST['content'];
$success = true;

// Check 'authentication'
if (!isset($_GET['authentic'])) {
	echo "You're not allowed to use this service.";
	exit;
}

// Check for content
if (!empty($content)) {
	
	// Rename old files
	for ($i=($backupversions-1); $i>=0; $i--) {
		if ($i > 0) {
			if (file_exists($backupname.$i)) {
				$success = rename($backupname.$i, $backupname.($i+1));
			}
		} else {
			$success = rename($filename, $backupname.($i+1));
		}
	}

	$temp = file_put_contents($filename, $content);
	$success = ($temp === FALSE) ? false : $success;

} else {
	error('Empty content received');
	exit;
}

// Check our status
if (!$success) {
	error('Error occurred while saving (or renaming) the config file');
	exit;
}

// Output the result as JSON
echo json_encode($json);

?>