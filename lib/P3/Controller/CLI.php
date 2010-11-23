<?php
/**
 * Description of CLI
 *
 * @author Tim Frazier <tim@essential-elements.net>
 */

class P3_Controller_CLI extends P3_Controller_Abstract
{
	/**
	 * Cli Indexer (Overidable if not wanted)
	 *
	 * Indexes functions of a controller, and makes them accessable via the command line
	 */
	public function index()
	{
		/* Print Intro */
		$this->_printIntro();

		/* Run all functionality */
		if(!empty($this->_args[0]) && strtolower($this->_args[0]) == 'runall'){
			$funcs = array_diff(get_class_methods(get_called_class()), get_class_methods(__CLASS__));
			foreach($funcs as $f) {
				$this->_printLine('Running '.$f.'...');
				$this->_runAction($f, false);
			}
		}

		/* Print Menu */
		$this->_printMenu();
	}

	/**
	 * Simply echos $str
	 *
	 * @param string $str
	 */
	protected function _print($str)
	{
		echo $str;
	}

	/**
	 * Echos $str with a new line appended
	 *
	 * @param string $str
	 */
	protected function _printLine($str = '')
	{
		echo $str."\n";
	}

	/**
	 * Prints a header to display program info
	 */
	private function _printIntro()
	{
		system('clear');
		$this->_printLine('**');
		$this->_printLine('* P3 Cli Controller Command Line Indexer MVC v1.0.0 beta');
		$this->_printLine('*');
		$this->_printLine('* Author: Tim Frazier <tim@essential-elements.net>');
		$this->_printLine('* Date:   2010-04-07');
		$this->_printLine('**');
		$this->_printLine();
	}

	/**
	 * Prints Menu (To access Controller Methods)
	 */
	private function _printMenu()
	{
		$class = get_called_class();
		echo $class.' Menu:'."\n";

		/* Retrieve methods ONLY from the extending class */
		$funcs = array_diff(get_class_methods($class), get_class_methods(__CLASS__));

		/* Display Options (Functions) */
		$x = 1;
		foreach($funcs as $function) {
			echo "  \t".$x.')  '.$function."\n";
			$x++;
		}

		/* Display an exit */
		echo "  \t".'0)  '."Exit\n";

		/* Take user input */
		echo "Run Function:  ";
		fscanf(STDIN, "%d\n", $choice);

		/* Exit if 0, otherwise try and run script */
		if($choice == 0) {
			exit;
		} elseif($choice > 0 && $choice < $x) {
			/* If method is expecting arguments, give user a chance to define them */
			if(!empty($this->_argMap[$funcs[$choice-1]])) {
				$this->_printLine("\n".'Hmm... it looks like this action is expecting an argument.');
				$this->_print('Would you like to provide this now? [y/N]: ');
				fscanf(STDIN, "%s\n", $response);
				$response = empty($response) ? 'n' : strtolower(substr($response, 0, 1));
				if($response == 'y') {
					system('clear');
					echo "* * * * * * * * * * * * * * *\n";
					echo "*      ACTION ARGUMENTS     *\n";
					echo "* * * * * * * * * * * * * * *\n";
					foreach($this->_argMap[$funcs[$choice-1]] as $k => $v) {
						$this->_print($v.': ');
						fscanf(STDIN, "%s\n", $arg);
						$this->_args[$v] = $arg;
						$this->_args[$k] = $arg;
					}
				}
			}

			/* Run the action */
			$this->_runAction($funcs[$choice-1]);
		} else {
			echo "\n**\n* Error:  Please choose a valid entry from the menu \n**\n\n";
		}

		/* Print menu again (Untill exit) */
		$this->_printMenu();
	}

	/**
	 * Runs an action and shows the output
	 *
	 * @param string $action
	 * @param boolean $clear Whether or not to clear previous output
	 */
	private function _runAction($action, $clear = true)
	{

		if($clear) {
			system('clear');
		}
		echo "* * * * * * * * * * * * * * *\n";
		echo "*      ACTION OUTPUT        *\n";
		echo "* * * * * * * * * * * * * * *\n\n";
		$this->{$action}();
		echo "\n\n";
	}
}

?>
