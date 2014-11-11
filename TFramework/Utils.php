<?php
namespace TFramework;

class Utils{

    private static $_sites;

    /**
     * Generate content for debug function
     * @param  mixed $var Everything to debug
     * @return string      The string representing the debug text
     */
    protected static function _generate_debug($var = null){
        $var_to_dump = func_get_args();
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
                $var = '\'\'';
            if(is_string($var))
                $var = htmlspecialchars($var, ENT_QUOTES);
            $debug_message .= print_r($var ,true). '<br/>'; 
        }
        $backtrace = debug_backtrace();
        $backtrace = $backtrace[2];
        $file_name = $backtrace['file'];// substr($backtrace['file'],strripos($backtrace['file'],'/') + 1);
        return sprintf('<pre class="debug">%s:%d<br />%s</pre>',$file_name,$backtrace['line'],$debug_message);
    }

    /**
     * Echoes debug informations
     * @param  mixed $var Everything
     * @return void
     */
    public static function debug($var = null){
        if( WP_DEBUG == false || WP_DEBUG_DISPLAY == false)
            return;
        echo call_user_func_array(array('TFramework\Utils','_generate_debug'), func_get_args());// self::_generate_debug(); 
    }
    
    /**
     * Retrieve blog list
     * @return array Array of registered blogs
     */
    public static function wp_list_sites() {
        if( !is_multisite() ){return false;}
        if(is_callable('wp_get_sites')){
            return wp_get_sites();
        }
        if(!empty(self::$_sites)){
            return self::$_sites;
        }
        global $wpdb;       
        self::$_sites = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} ORDER BY blog_id" );
        return self::$_sites;
     }

     /**
      * Ritorna true se ACF è attivo sul sito
      * @return boolean 
      */
     public static function is_acf_active(){
        if(!function_exists("register_field_group"))
            return false;
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return \is_plugin_active( 'advanced-custom_fields/acf.php' );
     }

     
}
?>