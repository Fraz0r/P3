<?php

namespace P3\ActiveRecord\Paginized\Control;

/**
 * Base class for pagination controls
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Paginized\Control
 * @version $Id$
 */
abstract class Base implements IRendersControl
{ 
	/**
	 * Collection to render control for
	 * 
	 * @var P3\ActiveRecord\Paginized\Collection
	 */
	protected $_collection = null;

//- Public
	/**
	 * Instantiate new control
	 * 
	 * @param P3\ActiveRecord\Paginized\Collection $collection collection to render control for
	 */
	public function __construct($collection)
	{
		$this->_collection = $collection;
	}

	/**
	 * Returns current page of collection
	 * 
	 * @return int current page
	 */
	public function page()
	{
		return $this->_collection->page();
	}

	/**
	 * Returns total pages for collection
	 * 
	 * @return int total pages
	 */
	public function pages()
	{
		return $this->_collection->pages();
	}

//- Protected
	/**
	 * Builds and returns an href based on page (preserving keys that were already in the query string)
	 * 
	 * @param int $page page to build link for
	 * @param str $page_key name of paging key in query string
	 * 
	 * @return string href for link
	 */
	protected function _pageURLPreservingHash($page, $page_key = 'page')
	{
		$arr = strlen($_SERVER['QUERY_STRING']) ? explode('&', $_SERVER['QUERY_STRING']) : array();

		$tmp = array();
		foreach($arr as $set) {
			list($k, $v) = explode('=', $set);
			$tmp[$k] = urldecode($v);
		}

		$tmp[$page_key] = $page;

		$arr = array();
		foreach($tmp as $k => $v)
			$arr[] = $k.'='.urlencode($v);

		return '?'.implode('&', $arr);
	}
}

?>