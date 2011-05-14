<?php

namespace P3\Mail;

/**
 * Description of Message
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Message 
{
	private $_attachments = array();
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

		if(isset($options['attachments']))
			$this->_attachments = $options['attachments'];

		$this->_to      = $to;
		$this->_subject = $subject;
		$this->_options = $options;
		$this->_x_mailer = isset($options['x_mailer']) ? $option['x_mailer'] : 'PHP v'.PHP_VERSION;

		if(isset($options['from']))
			$this->addHeader('From: '.$options['from']);

		if(!is_null($this->_x_mailer))
			$this->addHeader('X-Mailer: '.$this->_x_mailer);

		$this->_body    = $this->_parseParts($contents);
	}

	public function addHeader($header)
	{
		$this->_headers[] = $header;
	}

	public function attach($attachment, $inline = false)
	{
		$options = $inline ? array('disposition' => 'inline') : array();

		$this->_attachments[] = new Attachment($attachment);
	}

	public function boundry($type, $val = null)
	{
		if(is_null($val)) {
			if(is_null($this->_boundaries[$type]))
				$this->_boundaries[$type] = $this->_generateBoundary($type);

			return $this->_boundaries[$type];
		}
	}

	public function deliver()
	{
		return mail($this->_to, $this->_subject, $this->_body, $this->_headers());
	}

//- Private
	private function _generateBoundary($prepend)
	{
		if(is_null($this->_rand))
			$this->_rand = uniqid('p3m');

		return '==Multipart_Boundary_'.$prepend.'-'.$this->_rand;
	}

	private function _headers()
	{
		return implode($this->_eol, $this->_headers);
	}

	private function _parseParts($contents)
	{
		$eol = $this->_eol;
		$ret = '';

		if(is_string($contents))
			$contents = new Message\Part\Plain($contents);
		elseif(is_array($contents) && count($contents) == 1)
			$contents = current($contents);

		if(count($this->_attachments)) {
			$this->addHeader('Content-Type: multipart/mixed; boundary="'.$this->boundry('mixed').'"');
			$ret .= '--'.$this->boundry('mixed').$eol;
		}

		if(is_array($contents)) {
			if(!count($this->_attachments)) {
				$this->addHeader('Content-Type: multipart/alternative; boundary="'.$this->boundry('alt').'"');
			} else {
				$ret .= 'Content-Type: multipart/alternative; boundary="'.$this->boundry('alt').'"'.$eol.$eol;
			}

			$ret .= $this->_notice.$eol.$eol;

			foreach($contents as $part) {
				$part->setBoundary($this->boundry('alt'));
				$ret .= $part->renderContents();
			}
		} elseif(is_subclass_of($contents, 'P3\Mail\Message\Part')) {
			if(!count($this->_attachments)) {
				$this->addHeader($contents->header('content'));
			} else {
				$ret .= $contents->header('content').$eol;
				$ret .= $contents->header('transfer').$eol.$eol;
			}

			$ret .= $contents->renderContents(false);

			if(count($this->_attachments)) {
				$ret .= $eol.$eol;
			}
		} else {
			/* Need Exception */
		}

		if(0 < ($c = count($this->_attachments))) {
			$x = 0;
			foreach($this->_attachments as $attachment) {
				$attachment->boundary($this->boundry('mixed'));
				$ret .= $attachment->render().$eol;

				if(++$x == $c)
					$ret .= '--'.$this->boundry('mixed').'--'.$eol;
			}
		}


		return $ret;
	}

}

?>