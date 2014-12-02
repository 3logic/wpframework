<?php
namespace TFramework\JobManager\CommandLine;

class CliRunner{

	/**
	 * Executes the given callable in a shell through php-cli
	 * @param  callable $callable        descriptor of the method to run
	 * @param  array $params             Numeric Array of the parameters passed to the callable
	 * @param  array  $descriptorspec    An indexed array where the key represents the descriptor number and the value represents how PHP will pass that descriptor to the child process. 0 is stdin, 1 is stdout, while 2 is stderr
	 * @return int|boolean               False if the operation failed, else the shell PID
	 */
	public static function exec_on_shell($callable, $params, $descriptorspec = array()){
		$cwd = plugin_dir_path( __FILE__ );
		//$command = self::get_command($callable, $params);

		$base_args = array(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CliRunner.php', $_SERVER['HTTP_HOST'], ABSPATH, serialize($callable));

		$arguments = array_merge($base_args, $params);		

		$process_class = JOB_DEFAULT_PROCESS_CLASS;
		$process = new $process_class('php', $arguments, $descriptorspec);
		if($process->start()){
			return $process;
		}
		return false;

	}

}

//If this script is launched from shell
if(defined('STDIN') ){
	if(empty($argv)){
		return;
	}
	//throw new \Exception(print_r($argv, true ) );
	$http_host = $argv[1];
	$wp_path = $argv[2];		
	$callable = unserialize($argv[3]);
	$params = array_slice($argv, 4);
	$_SERVER['HTTP_HOST'] = $http_host;

	require_once( $wp_path . '/wp-load.php');
	require_once( dirname( __FILE__ ) . '/CliClassLoader.php');
	$loader = new \CliClassLoader('JobManager', dirname(dirname( __FILE__ )) );
	$loader->register();

	set_time_limit(0);
	ini_set( "memory_limit", "128M" );
	ob_start();
	$res = call_user_func_array($callable, $params);
	ob_end_clean();
	exit(0);	
}