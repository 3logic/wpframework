<?php
namespace TFramework\Ajax;

use TFramework\Utils;

class AjaxHelper{
    const BASENAME_QUERY_KEY = 'ajaxcontext';

    private $_basename = '';
    private $_basepath = '';
    private $_registered = array();

    /**
     * Cotruisce un AjaxHelper
     * @param string $basename Un base name per la tu applicazione. Costituirà la base dei path che sarà 'ajax/$basename/'
     * @param string $basepath Path al file del plugin (di solito __FILE__ nel file del plugin)
     */
    public function __construct($basename, $basepath){
        $this->_basename = $basename;
        $this->_basepath = $basepath;
    }

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
    public static function print_json_result($data = null, $die = true){
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
     * Registra un nuovo path per essere chiamato in ajax
     * @param  string $name       Nome del path (dovrebbe essere univoco altrimenti sovrascriverà eventuali altri path già creati)
     * @param  array $parameters [description]
     * @param  Callable $callable   Funzione da chiamare
     * @return boolean
     */
    public function register_path($name, $parameters, $callable){
        $this->_registered[$name] = array(
            'parameters'    => $parameters,
            'callable'      => $callable
        );
        return true;
    }

    public function enable(){
        add_action( 'init', array($this, 'rewrites_init'));
        add_filter( 'query_vars', array($this,'query_vars'));
        add_action( 'parse_request', array($this,'parse_request'));

        register_activation_hook( $this->_basepath, array($this,'activate') );
        // register_deactivation_hook( $this->_basepath, array($this,'deactivate') );
    }

    public function activate(){
        if ( !is_multisite() ){
            flush_rewrite_rules(false);
        }else{
            $sites = wp_get_sites();
            foreach ($sites as $site) {
              switch_to_blog($site['blog_id']);
              $this->rewrites_init();
              flush_rewrite_rules(false);
              restore_current_blog();
            }    
        }        
    }

    public function rewrites_init(){
        add_rewrite_rule(
            'ajax/'.$this->_basename.'/([^/]*)',
            sprintf('index.php?%s=%s&method=$matches[1]', self::BASENAME_QUERY_KEY, $this->_basename),
            'top'
        );
    }

    public function query_vars( $query_vars ){
        $query_vars[] = self::BASENAME_QUERY_KEY;
        $query_vars[] = 'method';
        return $query_vars;
    }
    
    
    public function parse_request($wp){
        if ( array_key_exists( self::BASENAME_QUERY_KEY, $wp->query_vars ) && $wp->query_vars[self::BASENAME_QUERY_KEY] == $this->_basename ){
            $name = isset($wp->query_vars['method'])? $wp->query_vars['method'] : '';
            if(isset($this->_registered[$name])){
                call_user_func($this->_registered[$name]['callable']);
            }
            else {
                self::_set_json_headers(501);
                die();
            }
        }
    }
    
}