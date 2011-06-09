<?php

require_once(realpath(dirname(__FILE__).'/../../..').'/P3.php');
P3\Loader::loadEnv();

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function testAutoTableName()
	{
		$user = new User;
		$this->assertEquals('users', $user::table());
	}

	public function testStaticTableName()
	{
		$user = new UserStatic;
		$this->assertEquals('users_overriden', $user::table());
	}

}

class User extends P3\ActiveRecord\Base
{
}

class UserStatic extends P3\ActiveRecord\Base
{
	public static $_table = 'users_overriden';
}

?>
