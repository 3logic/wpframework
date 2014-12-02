<?php
namespace TFramework\JobManager\Shell;

use TFramework\JobManager\CommandLine\CliRunner,
	TFramework\JobManager\Logger,
	\DateTime;


abstract class OperationJob{

	protected $_id;
	protected $_status;
	protected $_parameters;
	protected $_last_seen;
	protected $_created;
	protected $_cleanup;
	protected $_params_digest;

	protected $_logger;

	public function __construct($properties){
		$this->_id = $properties['id'];
		$this->_params_digest = $properties['params_digest'];
		$this->_status = $properties['status'];
		$this->_parameters = unserialize($properties['parameters']);
		$this->_last_seen = $properties['last_seen'];
		$this->_created = $properties['created'];
	}

	/**
	 * Generates a logger for this job
	 * @return TFramework\JobManager\Logger l logger
	 */
	protected function _generate_logger(){
		if(empty($this->_logger)){
			$creation = $this->get_creation_datetime();
			$temp_id = $this->_params_digest . '-' . $creation->format('Y_m_d-H_i_s');
			$this->_logger = new Logger(
				JOB_DEFAULT_LOG_LEVEL, 
				array(
					self::get_temporary_working_path("jobmanager_out_$temp_id.txt"),
				    self::get_temporary_working_path("jobmanager_err_$temp_id.txt")
				)
			);
		}
		return $this->_logger;
	}

	/**
	 * Executes this job's actual operation
	 * @return boolean True on success
	 */
	abstract public function act();

	/**
	 * Returns this job's id
	 * @return int job Id
	 */
	public function get_id(){
		return $this->_id;
	}

	public function get_last_seen(){
		return $this->_last_seen;
	}

	public function get_creation_datetime(){
		return DateTime::createFromFormat('Y-m-d H:i:s', $this->_created);
	}

	public function get_status(){
		return $this->_status;
	}

	public function get_parameters(){
		return $this->_parameters;
	}

	public function get_logger(){
		return $this->_generate_logger();
	}

	public static function get_defaults(){
		return array();
	}

	public static function params_digest_from_params($params){
		return md5(serialize($params));
	}

	/**
	 * Returns true if more than JOB_MAX_EXECUTION_SECS seconds have passed since the job's creation
	 * @return boolean 
	 */
	public function is_over_max_execution_time(){
		$now = new DateTime();
		$creation_date = $this->get_creation_datetime();
		$execution_time = $now->getTimestamp() - $creation_date->getTimestamp();
		return ($execution_time > JOB_MAX_EXECUTION_SECS);
	}

	public static function new_cleanup_time(){
		$now = new DateTime();
		return JOB_BEFORE_CLEANUP_SECS ? $now->getTimestamp() + JOB_BEFORE_CLEANUP_SECS : null;
	}

	/**
	 * Returns a path where temporary files can be written
	 * @param String [$file_name] file name to include in the path
	 * @return String Directory path
	 */
	public static function get_temporary_working_path($file_name = ''){
		return sprintf( JOB_TEMPORARY_PATH . DIRECTORY_SEPARATOR . '%s', $file_name);
	}


	/**
	 * Starts an external process to run this operation
	 * @return boolean
	 */
	public function start($context){
		// $class = get_class($this);
		// $class_name = explode('\\', $class);
		// $class_name = $class_name[ count($class_name) - 1];

		//Register the job without the pid, as we still don't have one
		$job_id = $this->get_id();
		
		$callable = array('TFramework\JobManager\Shell\JobAccessor', 'cli_act');
		$job_params = $this->get_parameters();
		$params = array( $context, $job_id );

		return CliRunner::exec_on_shell( $callable, $params, array(2 => array("file", self::get_temporary_working_path('errors.txt'), "a")) );
	}
}