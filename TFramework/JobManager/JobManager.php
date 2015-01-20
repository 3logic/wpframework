<?php
namespace TFramework\JobManager;

/**
 * Plugin Name: JobManager
 * Description: run and monitor jobs as background PHP processes 
 * Version: 0.4.0
 * Author: 3Logic
 */

use TFramework\JobManager\Shell\JobAccessor;

if(!defined('JOB_DEFAULT_LOG_LEVEL') )
    define('JOB_DEFAULT_LOG_LEVEL', 'error');
if(!defined('JOB_CHECK_INTERVAL_SECS') )
    define('JOB_CHECK_INTERVAL_SECS', 5);
if(!defined('JOB_MAX_EXECUTION_SECS') )
    define('JOB_MAX_EXECUTION_SECS', 60);
if(!defined('JOB_TEMPORARY_PATH') )
    define('JOB_TEMPORARY_PATH', '/tmp');
if(!defined('JOB_DEFAULT_PROCESS_CLASS') )
    define('JOB_DEFAULT_PROCESS_CLASS', 'TFramework\JobManager\CommandLine\SimpleProcess');
if(!defined('JOB_BEFORE_CLEANUP_SECS') )
    define('JOB_BEFORE_CLEANUP_SECS', 86400);

class JobManager{

    const STATUS_RUNNING  = 'running';
    const STATUS_SUCCEDED = 'success';
    const STATUS_ERRORED  = 'error';
    const STATUS_TIMEOUT  = 'timeout';

    public static $STATUS = array('running','success','error','timeout');
    protected static $_instances = array();

    protected $_context_name;

    public static function get_instance($context_name){
        if(!isset(static::$_instances[$context_name]) )
            static::$_instances[$context_name] = new JobManager($context_name);
        return static::$_instances[$context_name];
    }

    protected function __construct($context_name){
        $this->_context_name = $context_name;
    }

    protected function _get_table_name(){
        return JobAccessor::get_table_name($this->_context_name);
    }

    public function get_context_name(){
        return $this->_context_name;
    }

    public function get_job_by_id( $id ){
        return JobAccessor::get_job_by_id( $this->_context_name, $id );
    }

    public function activate(){
        JobAccessor::create_table($this->_context_name);
    }

    public function deactivate(){
        JobAccessor::drop_table($this->_context_name);
    }

    /**
     * Create a job
     * @param  string $job_class      Fully qualified job class name
     * @param  array  $params         Associative Array with execution parameters for the job
     * @return OperationJob|boolean   Job Object. False if the job could not be created.
     */
    public function create_job($job_class, $params){
        
        global $wpdb;
        $defaults = $job_class::get_defaults();
        $params = array_merge($defaults, $params);
        $params_digest = $job_class::params_digest_from_params($params);
        $table_name = $this->_get_table_name();
        $cleanup_time = $job_class::new_cleanup_time();

        $add_job_operation = JobAccessor::add_job( $this->_context_name, 
            $job_class, 
            $params, 
            $params_digest,
            $cleanup_time
        );

        if($add_job_operation != false){

            $select_query = "SELECT * FROM {$table_name} WHERE params_digest='$params_digest' ORDER BY created DESC LIMIT 1";
         
            $last_inserted_job = $wpdb->get_row($select_query, ARRAY_A);
  
            if( $last_inserted_job == null ){
                throw new \Exception("Error creating job", 1);
            }
            $job = new $job_class($last_inserted_job);

            if(!JobAccessor::start_job( $this->_context_name, $job->get_id())){
                return false;
            }
            return $job;
        }
        return false;
    }


    public static function get_plugin_dir_url($filename = ''){
        return sprintf('%s/%s', dirname(plugin_dir_url(__FILE__)), $filename);
    }

}


