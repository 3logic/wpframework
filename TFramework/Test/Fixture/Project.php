<?php

namespace TFramework\Test\Fixture;

use TFramework\CustomizablePostType;

/**
 * Questa classe rappresenta un custom post type di esempio di tipo `project`
 * @class Project
 */
class Project extends CustomizablePostType{

	/**
	 *  Should handle registration as custom post type on wordpress
	 * @param  array $options Options passed from current theme
	 * @return void
	 */
	static function registerCustomPostType($options = null){}

}