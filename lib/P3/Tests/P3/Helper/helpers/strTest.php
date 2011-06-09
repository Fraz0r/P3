<?php

require_once(realpath(dirname(__FILE__).'/../../../..').'/P3.php');
P3\Loader::loadEnv();

class strTest extends PHPUnit_Framework_TestCase
{
	public function testPluralize()
	{
		$this->assertEquals('users', str::pluralize('user'));
		$this->assertEquals('businesses', str::pluralize('business'));
		$this->assertEquals('families', str::pluralize('family'));
		$this->assertEquals('people', str::pluralize('person'));
	}

	/**
	* @depends testPluralize
	*/
	public function testToPlural()
	{
		$this->assertEquals('1 user', str::toPlural(1, 'user'));
		$this->assertEquals('2 users', str::toPlural(2, 'user'));
	}
}

?>
