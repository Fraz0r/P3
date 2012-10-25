<?php

namespace P3\Mail;

/**
 * This is the class returned by ActionMailer::create_[message].  It's also instatiated
 * in ActionMailer::deliver_[message], respectively. 
 * 
 * This can also be used on it's own.  Please see documentation throughout class
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3
 * @subpackage Mail
 * @version $Id$
 * 
 * @todo Rewrite this entire package, bc I hate it (straight moved from v1)
 */
class Message 
{
	const FLAG_SEND_VIA_SMTP = 1;

	/**
	 * Array of attachments to be sent with the message
	 * 
	 * @var array
	 */
	private $_attachments = array();

	/**
	 * Body of email
	 * 
	 * @var string 
	 */
	private $_body    = '';

	/**
	 * Container for the two MIME Multipart boundaries
	 * 
	 * @see _parse_parts
	 * @var array
	 */
	private $_boundaries = array('mixed' => null, 'alt' => null);

	/**
	 * String to use for line endings
	 * 
	 * @var string
	 */
	private $_eol = "\n";

	private $_delivery_handle;

	private $_flags = 0;

	private $_handler = null;

	/**
	 * Array of headers to be sent with message
	 * 
	 * @var array
	 */
	private $_headers = array();

	/**
	 * Message for notice section of Mutlipart/Alternative messages
	 * 
	 * @var string
	 */
	private $_notice  = 'This is a multi-part message in MIME format.';

	/**
	 * Array of options
	 * 
	 * @var type 
	 */
	private $_options = null;

	/**
	 * Subject of message
	 * 
	 * @var string
	 */
	private $_subject = null;

	/**
	 * Recipient(s)
	 * 
	 * @var string
	 */
	private $_to      = null;

	/**
	 * Random number used to generate boundaries
	 * 
	 * @see _generate_boundary
	 * @var string
	 */
	private $_rand    = null;

	/**
	 * Value to use for X-Mailer header.  Set to null to leave off
	 * 
	 * Default: v.PHP_VERSION
	 * 
	 * @var string
	 */
	private $_x_mailer = null;

	/**
	 * Instantiates new mail message
	 * 
	 * 	Options:
	 * 		mixed_boundary:	Boundary to use for multipart/mixed  (Can also be set with boundaries[mixed])
	 * 		alt_boundary:	Boundary to use for multipart/mixed  (Can also be set with boundaries[alt])
	 * 		attachments:	Array of Mail\Attachments to send with message
	 * 		from:       	Value to use for From: header
	 * 		x_mailer:		Value to use for X-Mailer header (null to leave off)
	 * 
	 * @param string $to recipient(s)
	 * @param string $subject subject 
	 * @param string,array $contents can be string, or array of Mail\Parts
	 * @param array $options options
	 */
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
			$this->add_header('From: '.$options['from']);

		if(isset($options['cc']))
			$this->add_header('CC: '.$options['cc']);

		if(isset($options['bcc']))
			$this->add_header('BCC: '.$options['bcc']);

		$this->add_header('Date: '.date('r', time()));

		if(!is_null($this->_x_mailer))
			$this->add_header('X-Mailer: '.$this->_x_mailer);

		if(isset($options['send_handler']))
			$this->_delivery_handle = $options['send_handler'];

		if(isset($options['flags']))
			$this->_flags = $options['flags'];

		if(FALSE !== \P3::config()->mail->delivery->smtp)
			$this->_flags |= self::FLAG_SEND_VIA_SMTP;

		$this->_body = $this->_parse_parts($contents);
	}

	/**
	 * Adds header to email 
	 * 
	 * Do NOT append the EOL [Important]
	 * 
	 * @param string $header header to add
	 */
	public function add_header($header)
	{
		$this->_headers[] = $header;
	}

	/**
	 * Adds attachment to message
	 * 
	 * @param P3\Mail\Attachment $attachment attachment
	 * @param boolean $inline Whether or not to send inline, false by default
	 */
	public function attach($attachment, $inline = false)
	{
		$options = $inline ? array('disposition' => 'inline') : array();

		$this->_attachments[] = new Attachment($attachment);
	}

	/**
	 * Gets boundary for type
	 * 	-OR-
	 * Sets boundary for type, if val != null
	 * 
	 * @param string $type type of boundary
	 * @param string $val value to set, get() mode if null
	 * @return mixed void if set(), string if get() 
	 */
	public function boundary($type, $val = null)
	{
		if(is_null($val)) {
			if(is_null($this->_boundaries[$type]))
				$this->_boundaries[$type] = $this->_generate_boundary($type);

			return $this->_boundaries[$type];
		}
	}

	/**
	 * Delivers message, returning successfullness
	 * 
	 * @see mail()
	 * @return boolean success of mail()
	 */
	public function deliver()
	{
		return $this->_handler()->deliver($this);
		//return mail($this->_to, $this->_subject, $this->_body, $this->_headers());
	}

	/**
	 * Retreives headers for message, glueing them together with $this->_eol
	 * 
	 * @see mail()
	 * @return string headers ready for mail()
	 */
	public function headers($glue = true)
	{
		return $glue ? implode($this->_eol, $this->_headers) : $this->_headers;
	}

//- Private
	/**
	 * Generates and returns a new MIME boundary
	 * 
	 * @param string $prepend string to prepend, to keep uniqueness amongst types
	 * @return string generated MIME boundary
	 */
	private function _generate_boundary($prepend)
	{
		if(is_null($this->_rand))
			$this->_rand = uniqid('p3m');

		return '==Multipart_Boundary_'.$prepend.'-'.$this->_rand;
	}

	private function _handler()
	{
		if(is_null($this->_handler)) {
			$class = $this->_delivery_handle;

			$this->_handler = new $class();
		}

		return $this->_handler;
	}

	/**
	 * Renders body of messaage
	 * 
	 * @param string,array $contents Can be string for plain text email.  Or one or more Message\Parts
	 * @return string body text
	 */
	private function _parse_parts($contents)
	{
		$eol = $this->_eol;
		$ret = '';

		if(is_string($contents))
			$contents = new Message\Part\Plain($contents);
		elseif(is_array($contents) && count($contents) == 1)
			$contents = current($contents);

		if(count($this->_attachments)) {
			$this->add_header('Content-Type: multipart/mixed; boundary="'.$this->boundary('mixed').'"');
			$ret .= '--'.$this->boundary('mixed').$eol;
		}

		if(is_array($contents)) {
			if(!count($this->_attachments)) {
				$this->add_header('Content-Type: multipart/alternative; boundary="'.$this->boundary('alt').'"');
			} else {
				$ret .= 'Content-Type: multipart/alternative; boundary="'.$this->boundary('alt').'"'.$eol.$eol;
			}

			$ret .= $this->_notice.$eol.$eol;

			foreach($contents as $part) {
				$part->set_boundary($this->boundary('alt'));
				$ret .= $part->render_contents();
			}
		} elseif(is_subclass_of($contents, 'P3\Mail\Message\Part')) {
			if(!count($this->_attachments)) {
				$this->add_header($contents->header('content'));
			} else {
				$ret .= $contents->header('content').$eol;
				$ret .= $contents->header('transfer').$eol.$eol;
			}

			$ret .= $contents->render_contents(false);

			if(count($this->_attachments)) {
				$ret .= $eol.$eol;
			}
		} else {
			/* TODO: Need Exception */
		}

		if(0 < ($c = count($this->_attachments))) {
			$x = 0;
			foreach($this->_attachments as $attachment) {
				$attachment->boundary($this->boundary('mixed'));
				$ret .= $attachment->render().$eol;

				if(++$x == $c)
					$ret .= '--'.$this->boundary('mixed').'--'.$eol;
			}
		}


		return $ret;
	}

//- Magic
	public function __get($var)
	{
		switch($var) {
			case 'to':
			case 'subject':
			case 'body':
			case 'flags':
				return $this->{"_{$var}"};
				break;
		}

		return null;
	}

}

?>