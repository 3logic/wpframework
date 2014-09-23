<?php 
namespace TFramework;
/**
 * Interface for classes that need to be called on theme hooks
 */
interface Hookable{
	/**
	 * Called on theme 'init' action
	 */
	static function onInit($options = null);

	/**
	 * Called on theme 'after_setup_theme' action
	 */
	static function onThemeSetup($options = null);
}
?>