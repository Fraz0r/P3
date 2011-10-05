<?php

require_once(realpath(dirname(__FILE__).'/../../../..').'/P3.php');
P3\Loader::loadEnv();

class strTest extends PHPUnit_Framework_TestCase
{
	public function testFromCamelCase()
	{
		$this->assertEquals('user', str::fromCamelCase('User'));
		$this->assertEquals('user_profile', str::fromCamelCase('UserProfile'));
		$this->assertEquals('abc_test', str::fromCamelCase('ABCTest'));
	}

	public function testPhone()
	{
		$this->assertEquals('660-909-2628', str::phone('6609092628'));
		$this->assertEquals('660-909-2628', str::phone('(660) 909-2628'));
	}

	public function testPluralize()
	{
		$this->assertEquals('users', str::pluralize('user'));
		$this->assertEquals('businesses', str::pluralize('business'));
		$this->assertEquals('families', str::pluralize('family'));
		$this->assertEquals('people', str::pluralize('person'));
	}

	public function testSingularize()
	{
		$this->assertEquals('user', str::singularize('users'));
		$this->assertEquals('family', str::singularize('families'));
		$this->assertEquals('person', str::singularize('people'));
	}

	public function testTitleize()
	{
		$this->assertEquals('Title', str::titleize('title'));
		$this->assertEquals('Test Title', str::titleize('test title'));
		$this->assertEquals('Test a Title', str::titleize('test a title'));
	}

	public function testToCamelCase()
	{
		$this->assertEquals('User', str::toCamelCase('user'));
		$this->assertEquals('UserProfile', str::toCamelCase('user_profile'));
		$this->assertEquals('UserProfileStats', str::toCamelCase('user_profile_stats'));
	}

	public function testToHuman()
	{
		$this->assertEquals('user profile', str::toHuman('user_profile'));
		$this->assertEquals('user profile', str::toHuman('UserProfile'));
		$this->assertEquals('test', str::toHuman('test'));
		$this->assertEquals('test', str::toHuman('Test'));
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
