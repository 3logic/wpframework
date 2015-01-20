<?php
namespace TFramework\JobManager;

use \DateTime;

class Logger{

	protected static $_levels = array(
		'debug' => 0,
		'info' => 1,
		'error' => 2
	);
	private static $_date_time_format = 'Y-m-d H:i:s';


	private $_min_level;
	private $_log_file;
	private $_error_file;
	private $_logger_identifier;
	

	public function __construct($min_level = SHELL_DEFAULT_LOG_LEVEL, $output_files = array()){
		$this->_min_level = $min_level;
		$this->_log_file = $output_files[0];
		$this->_error_file = !empty($output_files[1]) ? $output_files[1] : $output_files[0];
	}

	private function _generate_data_message($level = 'info', $var = null){
		$var_to_dump = array_slice(func_get_args(),1);
		$debug_message = '';
		foreach ($var_to_dump as $var) {
			if($var === null)
				$var = '(null)';
			if($var === false)
				$var = '(bool)false';
			if($var === true)
				$var = '(bool)true';
			if($var === 0)
				$var = '(int)0';
			if($var === '')
				$var = '(string)\'\'';
			// if(is_string($var))
			// 	$var = htmlspecialchars($var, ENT_QUOTES);
			$debug_message .= print_r($var ,true). PHP_EOL; 
		}
		if($level == 'debug'){
			$backtrace = debug_backtrace();
			//Il chiamante è 5 chiamate indietro
			$calle_distance = 6;
			if(isset($backtrace[$calle_distance]) && !empty($backtrace[$calle_distance])){
				$file_name = substr($backtrace[$calle_distance]['file'],strripos($backtrace[$calle_distance]['file'],'/') + 1);
				$line = $backtrace[$calle_distance]['line'];
			}else{
				$file_name = 'missing filename calles';
				$line = '-';
			}
			return sprintf('%s:%d %s', $file_name, $line, $debug_message);
		}else{
			return $debug_message;
		}
	}

	private function _generate_log_message($level, $data){
		$date = new DateTime();
		return sprintf("[%s - %s] %s%s", strtoupper($level), $date->format(self::$_date_time_format), call_user_func_array(array($this, '_generate_data_message'), func_get_args()), PHP_EOL  );
	}

	public function log($level = 'info', $data){
		if(self::$_levels[$level] < $this->_min_level || !array_key_exists($level, self::$_levels)){
			return;
		}		
		$file = $level == 'error' ? $this->_error_file : $this->_log_file;

		$dir_is_writable = is_writable(str_replace(basename($file), '', $file));
		if( !( $dir_is_writable && ( !file_exists($file) || is_writable($file)  )  ) ){
			return false;
		}

		return file_put_contents($file, call_user_func_array(array($this,'_generate_log_message'), func_get_args()), FILE_APPEND);
	}

	public function debug($data){
		$args = array_merge(array('debug'),func_get_args());
		return call_user_func_array(array($this,'log'), $args);
	}

	public function info($data){
		$args = array_merge(array('info'),func_get_args());
		return call_user_func_array(array($this,'log'), $args);
	}

	public function error($data){
		$args = array_merge(array('error'),func_get_args());
		return call_user_func_array(array($this,'log'), $args);
	}

	public function close(){
		return true;
	}
}