<?php
/**
 * Description of Filter
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */

/**
 * P3_Filter
 *
 * The filter class is an OOP wrapper for PHP's filter extension
 */
class P3_Filter
{
	// <editor-fold defaultstate="collapsed" desc="Constants">
	const INPUT_POST = 0;
	const INPUT_GET = 1;
	const INPUT_COOKIE = 2;
	const INPUT_ENV = 4;
	const INPUT_SERVER = 5;
	const INPUT_SESSION = 6;
	const INPUT_REQUEST = 99;
	const FILTER_FLAG_NONE = 0;
	const FILTER_REQUIRE_SCALAR = 33554432;
	const FILTER_REQUIRE_ARRAY = 16777216;
	const FILTER_FORCE_ARRAY = 67108864;
	const FILTER_NULL_ON_FAILURE = 134217728;
	const FILTER_VALIDATE_INT = 257;
	const FILTER_VALIDATE_BOOLEAN = 258;
	const FILTER_VALIDATE_FLOAT = 259;
	const FILTER_VALIDATE_REGEXP = 272;
	const FILTER_VALIDATE_URL = 273;
	const FILTER_VALIDATE_EMAIL = 274;
	const FILTER_VALIDATE_IP = 275;
	const FILTER_DEFAULT = 516;
	const FILTER_UNSAFE_RAW = 516;
	const FILTER_SANITIZE_STRING = 513;
	const FILTER_SANITIZE_STRIPPED = 513;
	const FILTER_SANITIZE_ENCODED = 514;
	const FILTER_SANITIZE_SPECIAL_CHARS = 515;
	const FILTER_SANITIZE_EMAIL = 517;
	const FILTER_SANITIZE_URL = 518;
	const FILTER_SANITIZE_NUMBER_INT = 519;
	const FILTER_SANITIZE_NUMBER_FLOAT = 520;
	const FILTER_SANITIZE_MAGIC_QUOTES = 521;
	const FILTER_CALLBACK = 1024;
	const FILTER_FLAG_ALLOW_OCTAL = 1;
	const FILTER_FLAG_ALLOW_HEX = 2;
	const FILTER_FLAG_STRIP_LOW = 4;
	const FILTER_FLAG_STRIP_HIGH = 8;
	const FILTER_FLAG_ENCODE_LOW = 16;
	const FILTER_FLAG_ENCODE_HIGH = 32;
	const FILTER_FLAG_ENCODE_AMP = 64;
	const FILTER_FLAG_NO_ENCODE_QUOTES = 128;
	const FILTER_FLAG_EMPTY_STRING_NULL = 256;
	const FILTER_FLAG_ALLOW_FRACTION = 4096;
	const FILTER_FLAG_ALLOW_THOUSAND = 8192;
	const FILTER_FLAG_ALLOW_SCIENTIFIC = 16384;
	const FILTER_FLAG_SCHEME_REQUIRED = 65536;
	const FILTER_FLAG_HOST_REQUIRED = 131072;
	const FILTER_FLAG_PATH_REQUIRED = 262144;
	const FILTER_FLAG_QUERY_REQUIRED = 524288;
	const FILTER_FLAG_IPV4 = 1048576;
	const FILTER_FLAG_IPV6 = 2097152;
	const FILTER_FLAG_NO_RES_RANGE = 4194304;
	const FILTER_FLAG_NO_PRIV_RANGE = 8388608;
	// </editor-fold>

	/**
	 * Input passed into the Constructor
	 * @var int
	 */
	protected $_input;

	/**
	 *
	 * @param int $input Self contained constant
	 */
	public function  __construct($input = null)
	{
		if(!extension_loaded('filter'))
			throw new P3_Exception('P3_Filters Require PHP\'s Fitler Extension', null);

		if($input == null) {
			$input = self::INPUT_REQUEST;
		}

		$this->_input = $input;
	}

	/**
	 * Returns a variable contained in the input passed to the __constructor
	 *
	 * @param string $varName Variable being accessed
	 * @param int $filter Self Contained constant
	 * @param array $options Flags, [if any]
	 * @return string On failure, returns FALSE
	 */
	public function input($varName, $filter=null, array $options = array())
	{
		if($filter == null)
		{
			switch($this->_input) {
				case self::INPUT_REQUEST:
					$var = filter_input(self::INPUT_GET, $varName);
					if (empty($var)) {
						$var = filter_input(self::INPUT_POST, $varName);
					}
					return($var);
				case self::INPUT_SESSION:
					return(false);
				default:
					return(filter_input($this->_input, $varName));
			}
		} elseif (!empty($options)) {
			switch($this->_input) {
				case self::INPUT_REQUEST:
					$var = filter_input(self::INPUT_GET, $varName, $filter, $options);
					if (empty($var)) {
						$var = filter_input(self::INPUT_POST, $varName, $filter, $options);
					}
					return($var);
				case self::INPUT_SESSION:
					return(false);
				default:
					return(filter_input($this->_input, $varName, $filter, $options));
			}
		} else {
			switch($this->_input) {
				case self::INPUT_REQUEST:
					$var = filter_input(self::INPUT_GET, $varName, $filter, $options);
					if (empty($var)) {
						$var = filter_input(self::INPUT_POST, $varName, $filter, $options);
					}
					return($var);
				case self::INPUT_SESSION:
					return(false);
				default:
					return(filter_input($this->_input, $varName, $filter, $options));
			}
		}
	}

	public function hasVar($var)
	{
	}

}

?>