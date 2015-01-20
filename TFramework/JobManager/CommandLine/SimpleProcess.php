<?php
namespace TFramework\JobManager\CommandLine;

/* An easy way to keep in track of external processes.
* Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
* @compability: Linux only. (Windows does not work).
* @author: Peec
*/
class SimpleProcess implements IProcess{
    
    public function __construct($command, $arguments, $desc_spec = array()){
        $args = array_map(function($argument){
            return escapeshellarg($argument);
        },$arguments);
        $this->command = $command . ' ' . implode(' ', $args);
        $this->desc = array('/dev/null','/dev/null','/dev/null');
        
        foreach ($desc_spec as $i => $desc) {
            if($desc[0] != 'file'){
                continue;
            }
            $touched = touch($desc[1]);
            if($touched){
                $this->desc[$i] = $desc[1];
            }
        }
    }

    private function runCom(){
        $command = sprintf('nohup %s > %s 2> %s & echo $!',$this->command,$this->desc[1],$this->desc[2]);
        //'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
        exec($command ,$op);
        $this->pid = (int)$op[0];
        return is_numeric($this->pid);
    }

    public function getPid(){
        return $this->pid;
    }

    public function status(){
        $command = 'ps -p '.$this->pid;
        exec($command,$op);
        if (!isset($op[1]))return false;
        else return true;
    }

    public function running(){
       return $this->status();
    }

    public function start(){
        if ($this->command != ''){
            return $this->runCom();
        }
        else return true;
    }

    public function stop(){
        $command = 'kill '.$this->pid;
        exec($command);
        if ($this->status() == false)return true;
        else return false;
    }
}