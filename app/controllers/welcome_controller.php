<?php

/** THIS WONT BE IN RELEASE, JUST HERE TO BUILD/TEST DISPATCH PROCESS **/

/**
 * Description of welcome_controller
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class WelcomeController extends ApplicationController 
{
	public function show()
	{
		$this->lol = 'Tits';
		//$this->render_template('show');
		//$this->render_template('show', ['layout' => false]);
		//$this->redirect(':back');
		//$this->redirect('http://www.google.com');
		//return [200, ['Content-type: text/plain'], '\'ello motto'];
	}
}

?>