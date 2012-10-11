<?php

namespace P3\Routing\Route;

/**
 * Description of segment
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
final class Segment 
{ 
	const SEPARATOR = '/';

	private $_token;

//- Public
	public function __construct($token, $optional = false, $parameter = false)
	{
		$this->_token = $token;
	}

	public function is_param()
	{
		return strlen($this->_token) && $this->_token[0] == ':';
	}

	public function token()
	{
		return $this->_token;
	}

//- Magic
	public function __toString()
	{
		return $this->token();
	}

//- Public Static
	public static function get_from_path($path)
	{
		if(empty($path) || $path == self::SEPARATOR)
			return array();

		$path = trim($path, self::SEPARATOR);

		if(!strpos($path, self::SEPARATOR))
			return array(new self($path));
		elseif(FALSE === strpos($path, ':') && FALSE === strpos($path, '('))
			return self::_get_static($path);
		else
			return self::_get_dynamic($path);
	}

//- Private Static
	private static function _get_dynamic($path)
	{
		//TODO: Figure out why this method was even here ( I think it was for optional args... )
		return self::_get_static($path);
	}

	private static function _get_globbed($path)
	{
		/* TODO: Implement _get_globbed in Route\Segment */
		throw new \P3\Exception\MethodException\NotImplemented(array(get_class(), '_get_globbed'));
	}

	private static function _get_static($path)
	{
		$self = get_class();

		return array_map(function($v)use($self){
			return new $self($v);
		}, explode(self::SEPARATOR, $path));
	}
}

?>