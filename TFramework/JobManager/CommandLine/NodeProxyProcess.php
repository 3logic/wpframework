<?php
namespace TFramework\JobManager\CommandLine;

if(!defined('BROADCAST_PROXY_HOST')){
    define('BROADCAST_PROXY_HOST', '127.0.0.1');
}

if(!defined('BROADCAST_PROXY_PORT')){
    define('BROADCAST_PROXY_PORT', '1247');
}

/* An easy way to keep in track of external processes.
* Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
* @compability: Linux only. (Windows does not work).
* @author: Peec
*/
class NodeProxyProcess implements IProcess{
    private $_command;
    private $_desc;
    private $_host ;
    private $_port;
    private $_exec_url;
    private $_pid;
    
    public function __construct($command, $arguments, $desc_spec = array()){
        $this->_command = $command;
        $this->_arguments = $arguments;
        $this->_host = BROADCAST_PROXY_HOST;
        $this->_port = BROADCAST_PROXY_PORT;
        $this->_dispatch_url = sprintf('http://%s:%d/api/dispatch', $this->_host, $this->_port);
        
        $this->_desc = array('null','null','null');
        
        foreach ($desc_spec as $i => $desc) {
            if($desc[0] != 'file'){
                continue;
            }
            $this->_desc[$i] = $desc[1];
        }
    }

    private function _build_url($path, $query_arr){
       $url_parts = array(
            'host' => $this->_host,
            'port' => $this->_port,
            'path' => $path,
            'query' => http_build_query($query_arr)
        );
        return http_build_url($url_parts);
    }

    private function _execute_query($url){
        $response = \http_get($url, array(), $response_info);
        if( $response_info['response_code'] != 200 || !$response ){
            return false;
        }
        $data = http_parse_message($response)->body;
        try{
            $data = json_decode($data, true);
        }catch(Exception $e){
            return false;
        }
        return $data;
    }

    private function runCom(){
        $query = array(
                    'command' => $this->_command,
                    'arguments' => $this->_arguments,
                    'stdio' => $this->_desc
                );
        $url = $this->_build_url('api/dispatch', $query);        
        $data = $this->_execute_query($url);

        if(is_array($data) && array_key_exists('result', $data) && array_key_exists('pid', $data['result']) && is_numeric($data['result']['pid'])){
            $this->_pid = (int)$data['result']['pid'];
        }else{
            return false;
        }
        return true;
    }

    public function getPid(){
        return $this->_pid;
    }

    public function status(){
        if(!$this->_pid){
            return false;
        }
        $query = array(
            'pid' => $this->_pid
        );
        $url = $this->_build_url('api/is_running', $query);        
        $data = $this->_execute_query($url);
        if(is_array($data) && array_key_exists('result', $data)){
            return (bool)$data['result'];
        }else{
            return false;
        }
    }

    public function running(){
       return $this->status();
    }

    public function start(){
        if ($this->_command != ''){
            return $this->runCom();
        }
        return true;
    }

    public function stop(){
        if(!$this->_pid){
            return false;
        }
        $query = array(
            'pid' => $this->_pid
        );
        $url = $this->_build_url('api/stop', $query);        
        $data = $this->_execute_query($url);
        if(is_array($data) && array_key_exists('result', $data)){
            return (bool)$data['result'];
        }else{
            return false;
        }
    }
}