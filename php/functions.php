<?php

// A function that will handle all our error calls
function error($reason) {
	echo json_encode(array('error' => true, 'error_reason' => $reason));
}

?>