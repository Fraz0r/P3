<?php

namespace P3\Cli;

/**
 * Description of console
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Console 
{
	const PROMPT = '> ';
	const RETURN_PROMPT = '>> ';

	public function __construct()
	{
		while(true)
		{
			echo self::PROMPT;

			$line = trim(fgets(STDIN));

			if($line === 'exit')
				exit;

			if($line[strlen($line)-1] !== ';')
				$line .= ';';

			try {
				$output = `php -r '$line'`;
			} catch(Exception $e) {
				var_dump($e);
			}

			echo self::RETURN_PROMPT.$output."\n";
		}
	}
}

?>