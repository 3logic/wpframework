<?php
namespace TFramework\JobManager\Shell;

use TFramework\JobManager\CommandLine\CliRunner,
	TFramework\JobManager\JobManager,
	TFramework\JobManager\Process,
	\Exception,
	\DateTime;

class JobAccessor{

	/**
	 * Base table name for jobs
	 * @var string
	 */
	private static $_base_table_name = 'tf_jobs';

	/**
	 * Returns the complete DB table name for jobs
	 * @return string $context_name  name of the context
	 */
	public static function get_table_name($context_name){
		global $wpdb;
		return $wpdb->base_prefix . self::$_base_table_name .'_'. $context_name;
	}

	/**
	 * Creates the DB Job table
	 * @param  string $context  name of the context
	 * @return null        
	 */
	public static function create_table($context){
        global $wpdb;
        $state_running = JobManager::STATUS_RUNNING;
        $table_name = self::get_table_name($context);
        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
          $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
          $charset_collate .= " COLLATE {$wpdb->collate}";
        }
        $state_enum = implode(',', array_map(function($status){
          return "'$status'";
        },JobManager::$STATUS));
        $sql = "CREATE TABLE $table_name (
                  id INT NOT NULL AUTO_INCREMENT,
                  params_digest VARCHAR(64) NOT NULL,
                  job_class TEXT NOT NULL,
                  status ENUM($state_enum) DEFAULT '$state_running' NOT NULL,
                  parameters TEXT NOT NULL,
                  extra TEXT NULL,
                  last_seen TIMESTAMP NULL,
                  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  cleanup TIMESTAMP NULL,
                  UNIQUE KEY id (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
	
	/**
	 * Drops the DB Job table
	 * @param  string $context  name of the context
	 * @return null
	 */
	public static function drop_table($context){
        global $wpdb;
        $table_name = self::get_table_name($context);
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
    }

    /**
     * Add a job in DB
     * @param string $context           [description]
     * @param string $job_class         [description]
     * @param array  $params            [description]
     * @param string $params_digest     [description]
     * @param int    $cleanup_timestamp [description]
     */
    public static function add_job( $context, $job_class, $params, $params_digest, $cleanup_timestamp ){
		global $wpdb;
		$table_name = self::get_table_name($context);
		$cleanup_datetime = $cleanup_timestamp ? new DateTime('@'.$cleanup_timestamp) : null;
		$cleanup = $cleanup_datetime ? $cleanup_datetime->format('Y-m-d H:i:s') : null;

		$values = array(
            'job_class' => $job_class,
            'parameters' => serialize($params),
            'params_digest' => $params_digest
        );
        if($cleanup)
        	$values['cleanup'] = $cleanup;

		$insert_job_operation = $wpdb->insert( $table_name, $values );

		$now = new DateTime();
		$cleanup_operation = $wpdb->query(
			sprintf("DELETE FROM %s WHERE status = '%s' AND cleanup <= '%s'",
			    $table_name, 
			    JobManager::STATUS_SUCCEDED, 
			    $now->format('Y-m-d H:i:s')
			)
		);

		return $insert_job_operation;
	}

	/**
	 * Return a Job instanced as an object of the correct class, given a job id
	 * @param  string $context  name of the context 
	 * @param  int $id          job id
	 * @return OperationJob     job object
	 */
	public static function get_job_by_id( $context, $id ){
		global $wpdb;
		$table_name = self::get_table_name($context);
		$select_query = "SELECT * FROM {$table_name} WHERE id=$id";
		$found_job = $wpdb->get_row($select_query, ARRAY_A);
		if( $found_job == null ){
			return false;
		}
		$class = $found_job['job_class'];
		if(class_exists($class))
			return new $class($found_job);
		else
			throw new \Exception("Unknown job class \"$class\"");
	}

	/**
	 * Return a Job instanced as an object of the correct class, given a digest
	 * @param  string $context         name of the context
	 * @param  string $params_digest   digest string indenitfying the parameters set
	 * @param  string [$status]        job status to filter on
	 * @return OperationJob|false      job object
	 */
	public static function get_job_by_params_digest($context, $params_digest, $status = 'any'){
		global $wpdb;
		$table_name = self::get_table_name($context);
		$select_query = "SELECT * FROM {$table_name} WHERE params_digest='$params_digest'";
		if(in_array( $status, JobManager::$STATUS )){
			$select_query .= " AND status='$status'";
		}
		$found_jobs = $wpdb->get_results($select_query, ARRAY_A);
		if(is_array($found_jobs)){
			return array_map(function($j){
				$class = $j->job_class;
				if(class_exists($class))
					return new $class($found_job);
				else
					return null;
			}, $found_jobs);
		}
		return false;
	}


	/**
	 * Sets a job status on DB
	 * @param  string $context  name of the context 
	 * @param  int $id          job id
	 * @param  string $status   job status
	 * @return int|boolean
	 */
	protected static function set_job_status($context, $job_id, $status = JobManager::STATUS_RUNNING){
		if(!in_array( $status, JobManager::$STATUS )){
			throw new Exception("Invalid status in set_job_status: " . $status, 1);			
		}
		global $wpdb;
		$table = self::get_table_name($context);
		//echo "setting $status on $job_id";
		return $wpdb->update( 
			$table, 
			array( 'status' => $status ), 
			array( 'id' => $job_id,)
		);
	}


	/**
	 * Deletes a job on DB
	 * @param  string $context   name of the context 
	 * @param  int $id           job id
	 * @return boolean
	 */
	protected static function delete_job($context, $job_id){
		global $wpdb;
		$table = self::get_table_name($context);
		return $wpdb->delete( 
			$table, 
			array( 'id' => $job_id,)
		);
	}


	/**
	 * Sets a last-seen timestamp for a job on DB
	 * @param  string $context   name of the context 
	 * @param  int $id           job id
	 * @param  DateTime $date    date and timeto set. Defaults to "now"
	 * @return int|boolean
	 */
	public static function set_job_last_seen($context, $job_id, $date = null){
		if(is_null($date)){
			$date = new DateTime('now');
		}
		global $wpdb;
		$table = self::get_table_name($context);
		return $wpdb->update( 
			$table, 
			array( 'last_seen' => $date->format('Y-m-d H:i:s') ), 
			array( 'id' => $job_id)
		);
	}


	/**
	 * Returns true if a job is alive
	 * A job is consdered alive if it's in running status and not past its max execution time
	 * @param  string $context   name of the context 
	 * @param  int $id           job id
	 * @return boolean           
	 */
	public static function is_job_alive($context, $job_id){
		$job = self::get_job_by_id($context, $job_id);
		if( $job->get_status() != JobManager::STATUS_RUNNING ){
			return false;
		}

		if($job->is_over_max_execution_time()){
			self::set_job_status($context,  $job_id,  JobManager::STATUS_TIMEOUT);
			return false;
		}
		$last_seen = $job->get_last_seen();
		if(is_null($last_seen)){
			return true;
		}
		$now = new DateTime();
		$last_seen = DateTime::createFromFormat('Y-m-d H:i:s',$last_seen);
		
		$interval = $now->getTimestamp() - $last_seen->getTimestamp();
		return $interval < (JOB_CHECK_INTERVAL_SECS + 5);
	}

	
	/**
	 * Starts a job
	 * @param  string $context   name of the context 
	 * @param  int $id           job id
	 * @return boolean           True if job started, False otherwise
	 */
	public static function start_job($context, $job_id){
		return self::_start_job_monitor($context, $job_id );
	}


	/**
	 * Starts a monitor process for a job. CLI method
	 * @param  string $context   name of the context 
	 * @param  int $id           job id
	 * @return boolean|int       monitor pid. False if monitor could not be started
	 */
	protected static function _start_job_monitor($context, $job_id){
		$callable = array(get_called_class(), 'cli_monitor');
		$params = array($context, $job_id);
		$desc_spec = array(2 => array("file", OperationJob::get_temporary_working_path('monitors_errors.txt'), "a"));
		return CliRunner::exec_on_shell($callable, $params, $desc_spec);
	}


	/**
	 * Executes a job's actual action. CLI method
	 * @param  string $context   name of the context 
	 * @param  int $job_id       job id
	 * @return null
	 */
	public static function cli_act($context, $job_id){
		$job = self::get_job_by_id($context, $job_id);
		$success = false;
		try{
			$success = $job->act();
		}catch(Exception $e){
			$job->get_logger()->error($e->getMessage());
			$success = false;
		}
		$status = $success ? JobManager::STATUS_SUCCEDED : JobManager::STATUS_ERRORED;
		self::set_job_status($context, $job_id, $status);
	}

	
	/**
	 * Monitors a job process and updates its last-seen timestamp
	 * @param  string $context   name of the context 
	 * @param  int $job_id       job id
	 * @return null
	 */
	public static function cli_monitor($context, $job_id){
		$job = self::get_job_by_id($context, $job_id);
		if(!$job){
			error_log("Job $job_id unknown");
			exit(1);
		}	

		$process = $job->start($context);
		if($process === false){
			error_log("Job $job_id failed to start");
			self::set_job_status($context, $job_id,  JobManager::STATUS_ERRORED);
			exit(1);
		}	
		
		while(true){
			error_log(print_r($process, true));
			if(!$process->running()){
				error_log("Job $job_id is not running.Exiting");
				exit(0);
			}else{
				// check for max execution time and if necessary update status to timeout
				if($job->is_over_max_execution_time()){
					self::set_job_status($context, $job_id,  JobManager::STATUS_TIMEOUT);
					$killed = $process->stop();
					if($killed == false){
						error_log("Job $job_id failed to kill. Possible zombie");
					}
					exit(0);
				}
				self::set_job_last_seen($context, $job_id);
			}
			sleep(JOB_CHECK_INTERVAL_SECS);
		}
	}

}