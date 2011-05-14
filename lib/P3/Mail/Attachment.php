<?php

namespace P3\Mail;

/**
 * Description of Attachment
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Attachment 
{
	private $_contents   = null;
	private $_boundary   = null;
	private $_dispostion = 'attachment';
	private $_eol        = "\n";
	private $_filename   = null;
	private $_mime_type  = null;

	public function __construct($filepath, array $options = array())
	{
		if(isset($options['disposition']))
			$this->_dispostion = $options['disposition'];

		if(isset($options['inline']) && $options['inline'])
			$this->_dispostion = 'inline';

		if(!file_exists($filepath)) {
			var_dump("TODO: NEED EXCEPTION HERE");
			die;
		}

		$ih = finfo_open(FILEINFO_MIME_TYPE);

		if(!$ih) {
			var_dump("TODO: NEED EXCEPTION HERE");
			die;
		}

		$this->_contents  = chunk_split(base64_encode(file_get_contents($filepath)));
		$this->_mime_type = finfo_file($ih, $filepath);
		$this->_filename  = isset($options['name']) ? $options['name'] : basename($filepath);

		finfo_close($ih);
	}

	public function boundary($boundary = null)
	{
		if(is_null($boundary))
			return $this->_boundary;
		else
			return $this->_boundary = $boundary;
	}

	public function render()
	{
		$eol = $this->_eol;
		if(is_null($this->_boundary)) {
			var_dump("TODO: NEED EXCEPTION HERE");
			die;
		}

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