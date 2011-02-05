<?php

/**
 * Description of str
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class str
{
	public static $_noCap = array('a', 'an', 'is', 'from', 'for', 'of', 'the');

 /**
   * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
  *
   * @param string $str String in camel case format
   * @return string $str Translated into underscore format
   */
  public static function fromCamelCase($str) {
    $str[0] = strtolower($str[0]);
    $func = create_function('$c', 'return "_" . strtolower($c[1]);');
    return preg_replace_callback('/([A-Z])/', $func, $str);
  }

  /**
   * Titleizes (capitalizes each word in a string)
   *
   * @param string $str String to titleize
   * @return string Titelized string
   */
  public static function titleize($str, $ignore_predicates = true)
  {
	  $ex = explode(' ', $str);
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

}
?>