<?php
namespace TFramework\JobManager\CommandLine;

interface IProcess{

	public function __construct($command, $arguments, $desc_spec = array());

	/**
	 * Retruns the PID
	 * @return int PID
	 */
	public function getPid();

	/**
	 * Returns the process status
	 * @return array process descriptor
	 */
    public function status();

    /**
     * Returns true if the process is running
     * @return boolean
     */
    public function running();

    /**
     * Starts the process
     * @return boolean False on fail
     */
    public function start();

    /**
     * Stops the process
     * @return boolean False on fail
     */
    public function stop();
}