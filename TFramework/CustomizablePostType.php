<?php
namespace TFramework;

use TFramework\BaseType,
	TFramework\Hookable,
	TFramework\Customizable;

abstract class CustomizablePostType extends BaseType implements Hookable, Customizable{
	
	const CUSTOM_TYPE_PREFIX = '';
	const CUSTOM_TAXONOMY_NAME = '';

	protected static $registered_fields;
    
    protected static function get_registered_fields($id_filter = null){
        $class = get_called_class();
        if(!isset(static::$registered_fields[$class]))
            static::$registered_fields[$class] = array();

        $fields = static::$registered_fields[$class];

        if($id_filter){
        	foreach($fields as $k=>$f){
        		if($k!==$id_filter && $f['field']['name']!==$id_filter)
        			unset($fields[$k]);
        	}
        }

        return $fields;
    }

    protected static function add_registered_fields($newly_registered){
        $class = get_called_class();

        static::$registered_fields[$class] = array_merge_recursive(static::get_registered_fields(), $newly_registered);
    }

    protected static function register_field_group($group_data){
        if(!function_exists("register_field_group")){
            throw new Exception("Missing register_field_group function. Did you include ACF plugin?", 1);           
        }

        $group_id = isset($group_data['id']) ? $group_data['id'] : $group_data['key'];
        $fields = $group_data['fields'];

        $newly_registered = array();
        foreach($fields as $f){
            $field_key = $f['key'];
            $newly_registered[$field_key] = array('group_id'=>$group_id, 'field'=>$f);
        }

        static::add_registered_fields($newly_registered);

        register_field_group($group_data);
    }

	/**
	 * constructor
	 * It's a wrapper around standard wordpress post instance
	 * @param [type] $post [description]
	 */
	public function __construct($post){
		parent::__construct($post);
		foreach ($post as $key => $value) {
			$this->{$key} = $value;
		}
	}

	public function __get($name){
		if(!function_exists("register_field_group")){
			return null;
		}
		return get_field($name, $this->ID);
    }
    
    /**
     * Return actual current post type name
     * @return string This class custom post type name
     */
    public static function getPostTypeName () {
        return CustomizablePostType::CUSTOM_TYPE_PREFIX . static::CUSTOM_TYPE_NAME;
    }
	

	/**
	 * Return this class custom taxonomy name
	 * @return string Custom taxonomy name
	 */
	public static function getCustomTaxonomyName(){
		return self::CUSTOM_TYPE_PREFIX . static::CUSTOM_TAXONOMY_NAME;
	}
	
	
	/**
	 * Empty method to register taxonomy. Because is a not always done operation, the implementation is left to sub-classes
	 * @param Array $options Options passed from current theme
	 */
	static protected function registerTaxonomy($options = null){
		return true;	
	}
	
	/**
	 * Handle registration as custom post type on Wordpress
	 * @param String $type_name Name of the custom post type
	 * @param Array $custom_post_type_args array of arguments. Will override
	 * @return Object|WP_Error
	 */
	static protected function _registerCustomType($type_name, $custom_post_type_args = array(), $options = null){
		$name = $type_name;
		$name_s = $name . 's';
		$name_ucf = ucfirst($type_name);
		$name_ucf_s = $name_ucf . 's';
		$defaults = array(
			'label' => $name_ucf_s,
			'labels' => array(
				'name'               => _x( $name_ucf_s, 'post type general name', $options['text_domain'] ),
				'singular_name'      => _x( $name_ucf, 'post type singular name', $options['text_domain'] ),
				'menu_name'          => _x( $name_ucf_s, 'admin menu', $options['text_domain'] ),
				'name_admin_bar'     => _x( $name_ucf, 'add new on admin bar', $options['text_domain'] ),
				'add_new'            => _x( 'Add New', 'book', $options['text_domain'] ),
				'add_new_item'       => __( "Add New $name_ucf", $options['text_domain'] ),
				'new_item'           => __( "New $name_ucf", $options['text_domain'] ),
				'edit_item'          => __( "Edit $name_ucf", $options['text_domain'] ),
				'view_item'          => __( "View $name_ucf", $options['text_domain'] ),
				'all_items'          => __( "All $name_ucf_s", $options['text_domain'] ),
				'search_items'       => __( "Search $name_ucf_s", $options['text_domain'] ),
				'parent_item_colon'  => __( "Parent $name_ucf_s:", $options['text_domain'] ),
				'not_found'          => __( "No $name_s found.", $options['text_domain'] ),
				'not_found_in_trash' => __( "No $name_s found in Trash.", $options['text_domain'] )
			),
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'show_in_menu' => true,
			'show_in_admin_bar' => true,
			'supports' => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'trackbacks',
				'custom-fields',
				'comments',
				'revisions',
				'post-formats'
			),
			'taxonomies' => array(
				'category',
				'post_tag'
			),
			'has_archive' => true,
			'rewrite' => array('slug' => $type_name),		
		);
		$custom_post_type_args = wp_parse_args( $custom_post_type_args, $defaults );
		return register_post_type( CustomizablePostType::CUSTOM_TYPE_PREFIX . $type_name, $custom_post_type_args );
	}
	
	/**
	 * Handle registration for custom taxonomies
	 * @param String $taxonomy_name Name for custom taxonomies
	 * @param Array $associated_types Array of post type which associate taxonomy to
	 * @param Array $custom_args Array of options to override defaults @see http://codex.wordpress.org/Function_Reference/register_taxonomy
	 */
	static protected function _registerCustomTaxonomy( $taxonomy_name, $associated_types = array(), $custom_args = array() ){
			
		$defaults = array(
			'label' => ucfirst($taxonomy_name).'s',
			'labels' => array(),
			'public' => true,
			'rewrite' => array('slug' => $taxonomy_name)
		);
		
		$custom_args = wp_parse_args( $custom_args, $defaults );
		return register_taxonomy( self::CUSTOM_TYPE_PREFIX . $taxonomy_name, $associated_types, $custom_args);
	}
	
	/**
	 * Register ACF fields. refer to ACF PHP exporter
	 * @param  array $options Options passed from current theme
	 * @return void
	 */
	protected static function registerACFFields($options){
		if(!function_exists("register_field_group")){
			return false; //throw new Exception("Missing register_field_group function. Did you include ACF plugin?", 1);			
		}
	}
	

	/**
	 * Code to be executed on theme initialization
	 * @param Object $options Option passed to theme setup
	 */
	public static function onInit($options = null){
		static::registerCustomPostType($options);
		static::registerTaxonomy($options);
		static::registerACFFields($options);
	}
	
	/**
	 * Code to be executed on theme setup
	 * @param Object $options Option passed from theme
	 */
	public static function onThemeSetup($options = null){
		return true;
	}
}
?>