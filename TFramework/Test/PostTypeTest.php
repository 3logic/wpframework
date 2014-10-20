<?php
namespace TFramework\Test;

use \PHPUnit_Framework_TestCase,
	TFramework\Test\Fixture\Project;

require( 'Fixture/global.php');

class PostTypeTest extends PHPUnit_Framework_TestCase
{

	protected static $_loerm_ipsum_html = '<p class="content">Lorem ipsum Cillum pariatur elit proident.</p>';

	private function generateProject(){
		return new Project(
			array(
				'post_title' => 'Bellissimo progetto',
				'post_content' => self::$_loerm_ipsum_html
			)
		);
	}

	/**
	 * @test
	 */
    public function testNewPostTypeInstantiable(){
        $project = $this->generateProject();
        $this->assertInstanceOf('TFramework\Test\Fixture\Project', $project);
        $this->assertInstanceOf('TFramework\CustomizablePostType', $project);
    }

    /**
	 * @test
	 */
    public function testPostProperties(){
        $project = $this->generateProject();
        $this->assertEquals('Bellissimo progetto', $project->post_title);
        $this->assertEquals(self::$_loerm_ipsum_html, $project->post_content);
    }

    
    /**
	 * @test
	 */
    public function testPostACFWithGetter(){
        $project = $this->generateProject();
        $this->assertEquals('Field_value for name on post 10', $project->name);
    }
}