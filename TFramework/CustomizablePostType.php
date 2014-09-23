<?php
namespace TFramework;

use TFramework\BaseType,
	TFramework\Hookable,
	TFramework\Customizable;

abstract class CustomizablePostType extends BaseType implements Hookable, Customizable{
	
	const CUSTOM_TYPE_PREFIX = 'mf_';
	const CUSTOM_TAXONOMY_NAME = '';
	
	/**
	 * constructor
	 * It's a wrapper around standard wordpress post instance
	 * @param [type] $post [description]
	 */
	protected function __construct($post){
		parent::__construct($post);
		foreach ($post as $key => $value) {
			$this->{$key} = $value;
		}
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
	 * Auto set custom fields properties as a native property for the customizable type
	 * @param Array|Associative Array $property_names An array of names of custom fields properties
	 * 												If the parameter is an Associative array the follow keys are allowed
	 * 												@key String name The name of the properties. Will be used instead of property key name. Optional, default null
	 * 												@key Function after_get Will be called after field retrieving. Default null. Following parameters will be passed
	 * @param Mixed $property retrieved property
	 * @param Object &$custom_type The entire object representing custom post type passed by reference											
	 */
	protected function setCustomFieldProperties( $property_names = array() ){
		$instance = $this;
		foreach ($property_names as $property => $property_name) {
			$this->{$property_name} = function() use($instance,$property_name){
				return get_field($property_name, $instance->ID);
			};
		}
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
	static protected function _registerCustomType($type_name, $custom_post_type_args = array()){
		$defaults = array(
			'label' => ucfirst($type_name).'s',
			'labels' => array(),
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