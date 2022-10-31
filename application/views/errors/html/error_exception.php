<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$data = [
	'type' => get_class($exception),
	'message' => $message,
	'filename' => $exception->getFile(),
	'lineNumber' => $exception->getLine(),
];

if(defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE){
	$data['backtraces'] = [];

	foreach ($exception->getTrace() as $error){
		if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0){
			$data['backtraces'] = [
				'file' => $error['file'],
				'line' => $error['line'],
				'function' => $error['function']
			];
		}	
	}
}

header('Content-Type: application/json');
echo json_encode($data);
?>