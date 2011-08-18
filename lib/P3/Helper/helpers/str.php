<?php

/**
 * String Helpers
 * 
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
class str
{
	/**
	 * Words to not caplitalize, unless forced
	 * 
	 * @var array
	 * @see titleize
	 */
	public static $_noCap = array('a', 'an', 'is', 'from', 'for', 'of', 'the');

	/**
	* Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
	*
	* @param string $str String in camel case format
	* @return string $str Translated into underscore format
	*/
	public static function fromCamelCase($str) {
		return trim(strtolower(preg_replace('/((?<=[a-z])[A-Z])|([A-Z](?![A-Z]|$))/', "_$1$2", $str)), '_');
	}

	/**
	 * Attempts to format passed string (phone number) and return it
	 * 
	 * If the preg match fails, the passed argument will be returned as it was passed
	 * 
	 * @param string $number number to format (can include non number chars, but they will be stripped)
	 * @param array $options options
	 * @return string formatted phone number 
	 */
	public static function phone($number, array $options = array())
	{
		$sep = isset($options['seperator']) ? $options['seperator'] : '-';

		$tmp = preg_replace('/([^\d])/', '', $number);

		if(preg_match('/^([\d]{3})([\d]{3})([\d]{4})([\d]*)?$/', $tmp, $m)) {
			list($full, $area, $pre, $last, $ext) = $m;
			$number = implode($sep, array($area, $pre, $last));

			if(!empty($ext))
				$number .= 'ext. '.$ext;
		}

		return $number;
	}

	/**
	* Returns plural form of passed $str
	*
	* NOTE:  This only handles "regular nouns" (IE: "person" would return "persons")
	*
	* @param string $str String to pluralize
	* @return string Pluralized string
	*/
	public static function pluralize($str)
	{
		$flag = 0;

		$str = preg_replace('/s$/', 'ses', $str, 1, $flag);

		if(!$flag)
			$str = preg_replace('/y$/', 'ies', $str, 1, $flag);

		if(!$flag)
			$str .= 's';

		return $str;
	}

	/**
	* Returns singular form of passed string
	*
	* NOTE:  This only handles "regular nouns" 
	*
	* @param string $str String to pluralize
	* @return string signular string
	*/
	public static function singularize($str)
	{
		$flag = 0;

		$str = preg_replace('/ses$/', 's', $str, 1, $lag);

		if(!$flag)
			$str = preg_replace('/ies$/', 'y', $str, 1, $flag);

		if(!$flag)
			$str = substr($str, 0, -1);

		return $str;
	}

	/**
	* Titleizes (capitalizes each word in a string)
	*
	* @param string $str String to titleize
	* @return string Titelized string
	*/
	public static function titleize($str, $ignore_predicates = true)
	{
		$ex = explode(' ', strtolower($str));
		$x = 0;
		foreach($ex as &$word) {
			//Only capitalize the word if it's not a predicate, unless it's the first word.  All words are capped if $ignore_predicates is false
			if(!$ignore_predicates || $x==0 || FALSE === array_search($word, static::$_noCap)) $word = ucfirst($word);
			$x++;
		}
		return implode(' ', $ex);
	}

	/**
	* Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
	*
	* @param string $str String in underscore format
	* @param bool $capitalise_first_char If true, capitalise the first char in $str
	* @return string $str translated into camel caps
	*/
	public static function toCamelCase($str, $capitalise_first_char = false) {
		if(strlen($str) < 1) return $str;
		if($capitalise_first_char) {
			$str[0] = strtoupper($str[0]);
		}
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $str);
	}

	/**
	* Converts string into human-friendly version (e.g. first_name -&gt; first name, firstName -&gt; first name).
	* Capitlazises words if $titleize is true.
	*
	* @param string $str String in camel case or underscore notation to convert.
	* @param boolean $titleize Titleizes string, if true
	* @return string Converted string
	*/
	public static function toHuman($str, $titleize = false)
	{
		if(FALSE !== strrpos($str, '_')) {
			$func = create_function('$c', 'return " ".$c[1];');
			$ret = preg_replace_callback('/_([a-z])/', $func, $str);
		} else {
			/* From camel case */
			$str[0] = strtolower($str[0]);
			$func = create_function('$c', 'return " " . strtolower($c[1]);');
			$ret = preg_replace_callback('/([A-Z])/', $func, $str);
		}

		return ($titleize) ? self::titleize($ret) : $ret;
	}

	/**
	 * Takes quantity and object name, and returns plural form of the two together
	 * 
	 * Example:
	 * 	str::toPlural(1, 'programmer'); // "1 programmer"
	 * 	str::toPlural(3, 'programmer'); // "3 programmers"
	 * 
	 * @param numeric $quantity quantity of object
	 * @param string $object_name name of object(s)
	 * @return string  parsed text
	 */
	public static function toPlural($quantity, $object_name)
	{
		if($quantity > 1)
			$object_name = self::pluralize($object_name);

		return implode(' ', array($quantity, $object_name));
	}

}
?>