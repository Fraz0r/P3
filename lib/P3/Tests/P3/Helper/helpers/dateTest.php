<?php

require_once(realpath(dirname(__FILE__).'/../../../..').'/P3.php');
P3\Loader::loadEnv();

/**
 * Description of dateTest
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class dateTest extends PHPUnit_Framework_TestCase
{
	public function testDaysForSelect()
	{
		$days = array();
		for($i = 1; $i <= 31; $i++)
			$days[$i] = $i;

		$this->assertEquals($days, \date::daysForSelect());
		array_pop($days);
		$this->assertEquals($days, \date::daysForSelect(array('max' => 30)));
	}

	public function testMonthsForSelect()
	{
		$this->assertEquals(array(
			1 => 'January',
			2 => 'February',
			3 => 'March',
			4 => 'April',
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December'
		), \date::monthsForSelect());

		$this->assertEquals(array(
			1 => 'Jan',
			2 => 'Feb',
			3 => 'Mar',
			4 => 'Apr',
			5 => 'May',
			6 => 'Jun',
			7 => 'Jul',
			8 => 'Aug',
			9 => 'Sep',
			10 => 'Oct',
			11 => 'Nov',
			12 => 'Dec'
		), \date::monthsForSelect(array('use_short_months' => true)));
	}

	public function testYearsForSelect()
	{
		$this->assertEquals(array(
			2000 => 2000,
			2001 => 2001,
			2002 => 2002,
			2003 => 2003
		), \date::yearsForSelect(array('start_year' => 2000, 'end_year' => 2003)));
	}
}

?>