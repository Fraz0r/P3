<?php

namespace P3\Mail;

/**
 * Description of Message
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Message 
{
	private $_body    = '';
	private $_boundaries = array('mixed' => null, 'alt' => null);
	private $_eol     = "\n";
	private $_from    = null;
	private $_headers = array();
	private $_notice  = 'This is a multi-part message in MIME format.';
	private $_options = null;
	private $_subject = null;
	private $_to      = null;
	private $_rand    = null;
	private $_x_mailer = null;

	public function __construct($to, $subject, $contents, array $options = array())
	{
		if(isset($options['mixed_boundary']))
			$this->_boundaries['mixed'] = $options['mixed_boundary'];

		if(isset($options['alt_boundary']))
			$this->_boundaries['alt'] = $options['alt_boundary'];

		if(isset($options['boundaries']['mixed']))
			$this->_boundaries['mixed'] = $options['mixed'];

		if(isset($options['boundaries']['alt']))
			$this->_boundaries['alt'] = $options['alt'];

		$this->_to      = $to;
		$this->_subject = $subject;
		$this->_body    = is_string($contents) ? $contents : $this->_parseParts($contents);
		$this->_options = $options;

		if(isset($options['from']))
			$this->_from = $options['from'];

		$this->_x_mailer = isset($options['x_mailer']) ? $option['x_mailer'] : 'PHP '.PHP_VERSION;

		if(isset($options['attach']))
			$this->_parseAttachments();
	}

	public function addHeader($header)
	{
		$this->_headers[] = $header;
	}

	public function boundry($type, $val = null)
	{
		if(is_null($val)) {
			if(is_null($this->_boundaries[$type]))
				$this->_boundaries[$type] = $this->_generateBoundary();

			return $this->_boundaries[$type];
		}
	}

	public function deliver()
	{
		return mail($this->_to, $this->_subject, $this->_body, $this->_headers());
	}

//- Private
	private function _generateBoundary()
	{
		if(is_null($this->_rand))
			$this->_rand = uniqid('p3m');

		return '==Multipart_Boundary_'.$this->_rand;
	}

	private function _headers()
	{
		if(!is_null($this->_from))
			$this->addHeader('From: '.$this->_from);

		if(!is_null($this->_x_mailer))
			$this->addHeader('X-Mailer: '.$this->_x_mailer);

		return implode($this->_eol, $this->_headers);
	}

	private function _parseParts($contents)
	{
		$eol = $this->_eol;

		if(is_array($contents)) {
			$this->addHeader('Content-Type: multipart/alternative; boundary="'.$this->boundry('alt').'"');

			$ret = $this->_notice.$eol.$eol;

			foreach($contents as $part) {
				$part->setBoundary($this->boundry('alt'));
				$ret .= $part->renderContents();
			}
		} elseif(is_subclass_of($contents, 'P3\Mail\Message\Part')) {
			$ret = $contents->renderContents();
		} else {
			/* Need Exception */
		}

		return $ret;
	}

}

?>