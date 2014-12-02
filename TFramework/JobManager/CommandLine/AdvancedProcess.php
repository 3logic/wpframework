<?php
namespace TFramework\JobManager\CommandLine;
/**
 * Unmantained!
 */

class AdvancedProcess implements IProcess{
    private $_command;
    private $_descriptorspec;
    private $_process;
    private $_started = false;

    public function __construct($command, $arguments, $desc_spec = array()){
        $this->_command = sprintf('exec %s &',$command);
        $this->_descriptorspec = $desc_spec;
    }

    
    public function getPid(){
        if(!$this->_started){
            return false;
        }
        $status = $this->status();
        return $status['pid'];
    }

    public function status(){
       return proc_get_status($this->_process);
    }

    public function running(){
       $status = $this->status();
       return $status['running'];
    }

    public function start(){
        $cwd = plugin_dir_path( __FILE__ );
        $this->_process = proc_open($this->_command, $this->_descriptorspec, $pipes, $cwd);
        return ( $this->_started = is_resource($this->_process) );
    }

    public function stop(){
        return proc_terminate($this->_process);
    }
}