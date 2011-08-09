<?php

namespace P3\System\Logging\Engine;
use       P3\System\Logging;

/**
 * Logging engine used by P3
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\System\Logging\Engine
 * @version $Id$
 */
abstract class Base 
{
	/**
	 * File handler
	 * 
	 * @var resource
	 */
	private $_fh        = null;

	/**
	 * Contains formatter object
	 * 
	 * @var P3\System\Logging\Formatter
	 */
	private $_formatter = null;

	/**
	 * Current log level
	 * 
	 * @var int
	 */
	private $_level     = null;

	/**
	 * Program name
	 * 
	 * @var string
	 */
	private $_progname  = null;

	/**
	 * Labels for severities
	 * 
	 * @var array
	 */
	private static $SEV_LABEL = array(
		Logging\LEVEL_UNKNOWN => 'UNKNOWN',
		Logging\LEVEL_DEBUG   => 'DEBUG',
		Logging\LEVEL_INFO    => 'INFO',
		Logging\LEVEL_WARN    => 'WARN',
		Logging\LEVEL_ERROR   => 'ERROR',
		Logging\LEVEL_FATAL   => 'FATAL'
	);

	/**
	 * Path to log
	 * 
	 * @var string 
	 */
	protected $_file = null;

//- Magic	
	public function __construct($file, $level, $progname = null) 
	{
		$this->_file = $file;
		$this->_level = $level;
		$this->_progname = $progname;
	}
	
	public function __destruct()
	{
		if($this->_opened())
			$this->_close();
	}

//- Public
	/**
	 * Adds an item to the log with the given severity, if the log is set to write them
	 * 
	 * @param string $message message to log
	 * @param int $severity severity of message
	 * @param string $progname name of program logging
	 * 
	 * @return boolean
	 */
	public function add($message, $severity = null, $progname = null)
	{
		if(is_null($severity))
			$severity = Logging\LOG_LEVEL_UNKNOWN;

		if($severity < $this->_level)
			return true;

		if(is_null($progname))
			$progname = $this->_progname;

		return $this->write($this->_formatMessage($this->_formatSeverity($severity), time(), $progname, $message));
	}

	/**
	 * Log DEBUG level message
	 * 
	 * @param string $message message
	 * @param string $progname program name
	 * 
	 * @return boolean
	 */
	public function debug($message, $progname = null)
	{
		return $this->add($message, Logging\LEVEL_DEBUG, $progname);
	}

	/**
	 * Log ERROR level message
	 * 
	 * @param string $message message
	 * @param string $progname program name
	 * 
	 * @return boolean
	 */
	public function error($message, $progname = null)
	{
		return $this->add($message, Logging\LEVEL_ERROR, $progname);
	}

	/**
	 * Log FATAL level message
	 * 
	 * @param string $message message
	 * @param string $progname program name
	 * 
	 * @return boolean
	 */
	public function fatal($message, $progname = null)
	{
		return $this->add($message, Logging\LEVEL_FATAL, $progname);
	}

	/**
	 * Log INFO level message
	 * 
	 * @param string $message message
	 * @param string $progname program name
	 * 
	 * @return boolean
	 */
	public function info($message, $progname = null)
	{
		return $this->add($message, Logging\LEVEL_INFO, $progname);
	}

	/**
	 * Set logging level
	 * 
	 * @param int $level logger level
	 */
	public function level($level)
	{
		$this->_level = $level;
	}

	/**
	 * Alias of add
	 * 
	 * @see add
	 */
	public function log($message, $severity = null, $progname = null)
	{
		return $this->add($message, $severity, $progname);
	}

	/**
	 * Returns true if this logger would log the passed level
	 * 
	 * @param int $level severity level
	 * @return boolean true if it should log, false otherwise 
	 */
	public function loggable($level)
	{
		return $level >= $this->_level;
	}

	/**
	 * Log WARN level message
	 * 
	 * @param string $message message
	 * @param string $progname program name
	 * 
	 * @return boolean
	 */
	public function warn($message, $progname = null)
	{
		return $this->add($message, Logging\LEVEL_WARN, $progname);
	}

	/**
	 * Write line to file, without formatting
	 * 
	 * @param string $string line
	 * @return boolean 
	 */
	public function write($string)
	{
		if(!$this->_opened())
			$this->_open();

		fwrite($this->_fh, $string."\n");

		return true;
	}

//- Protected
	/**
	 * Determines if the file is open for writing
	 * 
	 * @return boolean true if the file open, false otherwise
	 */
	protected function _opened()
	{
		return !is_null($this->_fh);
	}

//- Private 
	/**
	 * Closes file handle
	 * 
	 * @return void
	 */
	private function _close()
	{
		fclose($this->_fh);
	}

	/**
	 * Returns log formatter
	 * 
	 * @return P3\System\Logging\Formatter log formatter
	 */
	private function _formatter()
	{
		if(is_null($this->_formatter))
			$this->_formatter = new Logging\Formatter;

		return $this->_formatter;
	}

	/**
	 * Formats message for writing
	 * 
	 * @param int $severity message severity
	 * @param int $timestamp timestamp
	 * @param string $progname program name
	 * @param string $msg message
	 * 
	 * @return string formatted message 
	 */
	private function _formatMessage($severity, $timestamp, $progname, $msg)
	{
		$formatter = $this->_formatter();

		return $formatter($severity, $timestamp, $progname, $msg);
	}	

	/**
	 * Renders severity level into a string
	 * 
	 * @param int $severity severity level
	 * @return string severity label 
	 */
	private function _formatSeverity($severity)
	{
		return isset(self::$SEV_LABEL[$severity]) ? self::$SEV_LABEL[$severity] : 'ANY';
	}	

	/**
	 * Opens file for writing and saves resource
	 * 
	 * @return void
	 */
	private function _open()
	{
		$this->_fh = fopen($this->_file, 'a');
	}
}

?>