<?php

namespace P3\Mail;

/**
 * This is the class to use to attach files to any P3\Mail\Message's
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Mail
 * @version $Id$
 */
class Attachment 
{
	/**
	 * Base64 (chunked) contents of attachment - safe for mail()
	 * 
	 * @see mail()
	 * @see render
	 * @var string
	 */
	private $_contents   = null;

	/**
	 * MIME Multipart/Mixed Boundary to use in rendering
	 * 
	 * @var string
	 */
	private $_boundary   = null;

	/**
	 * Disposition of attachment (attachment or inline)
	 * 
	 * @var string
	 */
	private $_dispostion = 'attachment';

	/**
	 * String to use for end of lines
	 * 
	 * @var string
	 */
	private $_eol        = "\n";

	/**
	 * Filename to use for attached file
	 * 
	 * @var string
	 */
	private $_filename   = null;

	/**
	 * MIME Type of attachment
	 * 
	 * @see finfo_file
	 * @var string
	 */
	private $_mime_type  = null;

	/**
	 * Instantiates new Mail\Attachement
	 * 
	 * 	Options:
	 * 		disposition: "attachment" or "inline" (default "attachment")
	 * 		inline: true or false  (same as disposition = 'inline')
	 * 		name:	optional filename to use for attachment.  basename() is used if null
	 * 
	 * @param string $filepath full path to attachment
	 * @param array $options options
	 * @see basename
	 */
	public function __construct($filepath, array $options = array())
	{
		if(isset($options['disposition']))
			$this->_dispostion = $options['disposition'];

		if(isset($options['inline']) && $options['inline'])
			$this->_dispostion = 'inline';

		if(!file_exists($filepath))
			throw new \P3\Exception\MailAttachmentException("File does not exist");

		$ih = finfo_open(FILEINFO_MIME_TYPE);

		if(!$ih)
			throw new \P3\Exception\MailAttachmentException("Failed to stat file");

		$this->_contents  = chunk_split(base64_encode(file_get_contents($filepath)));
		$this->_mime_type = finfo_file($ih, $filepath);
		$this->_filename  = isset($options['name']) ? $options['name'] : basename($filepath);

		finfo_close($ih);
	}

	/**
	 * Gets boundary
	 * 	-OR-
	 * Sets boundary if boundary !== null
	 * 
	 * @param type $boundary
	 * @return mixed void if set(), string if get()
	 */
	public function boundary($boundary = null)
	{
		if(is_null($boundary))
			return $this->_boundary;
		else
			return $this->_boundary = $boundary;
	}

	/**
	 * Renders attachment into text, including headers -  usable in Mail\Message
	 * 
	 * @return string rendered string
	 * @see P3\Mail\Message::_parseParts()
	 */
	public function render()
	{
		$eol = $this->_eol;
		if(is_null($this->_boundary))
			throw new \P3\Exception\MailAttachmentException("No Multi-Part MIME boundary was set before render()");

		$ret = '';
		$ret .= '--'.$this->_boundary.$eol;
		$ret .= 'Content-Type: '.$this->_mime_type.'; name="'.$this->_filename.'"'.$eol;
		$ret .= 'Content-Transfer-Encoding: base64'.$eol;
		$ret .= 'Content-Disposition: '.$this->_dispostion.$eol.$eol;
		$ret .= $this->_contents;

		return $ret;
	}
}

?>