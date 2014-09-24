<?php 
namespace TFramework;


/**
 * Interface for classes that need to be called on theme hooks
 */
interface Customizable{

	/**
	 *  Should handle registration as custom post type on wordpress
	 * @param  array $options Options passed from current theme
	 * @return void
	 */
	static function registerCustomPostType($options = null);
}
?>