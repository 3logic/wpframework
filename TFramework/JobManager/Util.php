<?php

namespace TFramework\JobManager;

class Util{
	 private static function _set_json_headers($status = 200){
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
            self::_header_status($status);
    }

    private static function _header_status($statusCode) {
	    static $status_codes = null;

	    if ($status_codes === null) {
	        $status_codes = array (
	            100 => 'Continue',
	            101 => 'Switching Protocols',
	            102 => 'Processing',
	            200 => 'OK',
	            201 => 'Created',
	            202 => 'Accepted',
	            203 => 'Non-Authoritative Information',
	            204 => 'No Content',
	            205 => 'Reset Content',
	            206 => 'Partial Content',
	            207 => 'Multi-Status',
	            300 => 'Multiple Choices',
	            301 => 'Moved Permanently',
	            302 => 'Found',
	            303 => 'See Other',
	            304 => 'Not Modified',
	            305 => 'Use Proxy',
	            307 => 'Temporary Redirect',
	            400 => 'Bad Request',
	            401 => 'Unauthorized',
	            402 => 'Payment Required',
	            403 => 'Forbidden',
	            404 => 'Not Found',
	            405 => 'Method Not Allowed',
	            406 => 'Not Acceptable',
	            407 => 'Proxy Authentication Required',
	            408 => 'Request Timeout',
	            409 => 'Conflict',
	            410 => 'Gone',
	            411 => 'Length Required',
	            412 => 'Precondition Failed',
	            413 => 'Request Entity Too Large',
	            414 => 'Request-URI Too Long',
	            415 => 'Unsupported Media Type',
	            416 => 'Requested Range Not Satisfiable',
	            417 => 'Expectation Failed',
	            422 => 'Unprocessable Entity',
	            423 => 'Locked',
	            424 => 'Failed Dependency',
	            426 => 'Upgrade Required',
	            500 => 'Internal Server Error',
	            501 => 'Not Implemented',
	            502 => 'Bad Gateway',
	            503 => 'Service Unavailable',
	            504 => 'Gateway Timeout',
	            505 => 'HTTP Version Not Supported',
	            506 => 'Variant Also Negotiates',
	            507 => 'Insufficient Storage',
	            509 => 'Bandwidth Limit Exceeded',
	            510 => 'Not Extended'
	        );
	    }

	    if ($status_codes[$statusCode] !== null) {
	        $status_string = $statusCode . ' ' . $status_codes[$statusCode];
	        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status_string, true, $statusCode);
	    }
	}

	/**
     * Costruisce un result JSON. Controlla anche se la richiesta è stata fatta in JSONP e modifica la risposta di conseguenza
     * @param Mixed $data Data to be showed encoded in json
     */                                              
    public static function set_json_result($data = null, $die = true){
        $json = json_encode($data);
        if(!empty($_REQUEST['callback'])){
            $json = $_REQUEST['callback'] . '(' . $json .');';
        }else{
            self::_set_json_headers();
        }
        if($die == true){
        	ob_clean();
        	ob_start();
			echo $json;
			ob_end_flush();
			die();
        }
        return $json;
    }

    /**
     * Produce un errore in json settando anche l'header. L'errore viene stampato
     * @param string  $message [description]
     * @param integer $status  [description]
     */
    public static function set_error($message = 'error', $status = 500){
		self::_set_json_headers(500);		
		echo json_encode(array('error' => $message));
    }
}