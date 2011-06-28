<?php

/**
 * Convinience function for rendering and displaying pagination control for the collection
 * 
 * @param P3\ActiveRecord\Paginized\Collection $collection collection to build controls from
 * @param int $style style to render (currently only one supported)
 */
function will_paginate($collection, $style = pagination::CONTROL_STYLE_DIGG)
{
	echo pagination::renderControlForCollection($collection, $style);
}

/**
 * Pagination Helper
 * 
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\Helper\helpers
 * @version $Id$
 */
class pagination 
{
	/**
	 * Control Styles
	 */
	const CONTROL_STYLE_DIGG = 1;

	/**
	 * Renders and returns a paging control for the given collection
	 * 
	 * Currently only supporting one style (The default [digg's old paging control, which ROCKS])
	 * 
	 * @param P3\ActiveRecord\Paginized\Collection $collection collection to build control from
	 * @param int $style style to render, currently only one supported
	 * 
	 * @return string paging control (html)
	 */
	public static function renderControlForCollection(P3\ActiveRecord\Paginized\Collection $collection, $style = self::CONTROL_STYLE_DIGG)
	{
		switch($style) {
			case self::CONTROL_STYLE_DIGG:
				$control_class = '\P3\ActiveRecord\Paginized\Control\Digg';
				break;
			default:
				throw new \P3\Exception\HelperException("Uknown pagination control render style passed.");
		}

		$control = new $control_class($collection);

		return $control->renderControl();
	}
}

?>