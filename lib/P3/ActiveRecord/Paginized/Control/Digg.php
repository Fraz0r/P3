<?php

namespace P3\ActiveRecord\Paginized\Control;

/**
 * This class renders the pagination control very similarly to how Digg [used]
 * to do it.
 * 
 * The code in this class is based on the snippet from:
 * http://www.strangerstudios.com/sandbox/pagination/diggstyle.php
 * 
 * The only modifcations done were some refactoring, and the preservation
 * of existing $_GET vars in the URL.
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Paginized\Control
 * @version $Id$
 */
class Digg extends Base
{
	/**
	 * Number of adjacent pages to show on each side of the current page
	 * 
	 * @var int
	 */
	private $_adjacents = 3;

//-Public
	/**
	 * Render and return control
	 * 
	 * @return string rendered control (html)
	 */
	public function renderControl()
	{
		$ret = '';

		$page = $this->page();

		$next = $page + 1;
		$prev = $page - 1;

		$total_pages  = $this->pages();

		$adjacents = $this->_adjacents;
		$lpm1 = $total_pages - 1;

		if($total_pages > 1) {	
			$ret .= "<div class=\"pagination\">";
			//previous button
			if ($page > 1) 
				$ret.= $this->_pageLink($prev, '&laquo;');
			else
				$ret.= '<span class="disabled">&laquo;</span>';	
			
			//pages	
			if ($total_pages < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
			{	
				for ($counter = 1; $counter <= $total_pages; $counter++)
				{
					if ($counter == $page)
						$ret.= '<span class="current">'.$counter.'</span>';
					else
						$ret.= $this->_pageLink($counter);
				}
			}
			elseif($total_pages > 5 + ($adjacents * 2))	//enough pages to hide some
			{
				//close to beginning; only hide later pages
				if($page < 1 + ($adjacents * 2))		
				{
					for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
					{
						if ($counter == $page)
							$ret.= '<span class="current">'.$counter.'</span>';
						else
							$ret.= $this->_pageLink($counter);
					}
					$ret.= '...';
					$ret.= $this->_pageLink($lpm1);
					$ret.= $this->_pageLink($total_pages);
				}
				//in middle; hide some front and some back
				elseif($total_pages - ($adjacents * 2) > $page && $page > ($adjacents * 2))
				{
					$ret.= $this->_pageLink(1);
					$ret.= $this->_pageLink(2);
					$ret.= '...';
					for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
					{
						if ($counter == $page)
							$ret.= '<span class="current">'.$counter.'</span>';
						else
							$ret.= $this->_pageLink($counter);
					}
					$ret.= '...';
					$ret.= $this->_pageLink($lpm1);
					$ret.= $this->_pageLink($total_pages);
				}
				//close to end; only hide early pages
				else
				{
					$ret.= $this->_pageLink(1);
					$ret.= $this->_pageLink(2);
					$ret.= '...';
					for ($counter = $total_pages - (2 + ($adjacents * 2)); $counter <= $total_pages; $counter++)
					{
						if ($counter == $page)
							$ret.= '<span class="current">'.$counter.'</span>';
						else
							$ret.= $this->_pageLink($counter);
					}
				}
			}
			
			//next button
			if ($page < $counter - 1) 
				$ret.= $this->_pageLink($next, '&raquo;');
			else
				$ret.= '<span class="disabled">&raquo;</span>';
			$ret.= '</div>';

			return $ret;
		}
	}

//- Private
	/**
	 * Renders and returns link for control
	 * 
	 * @param int $page page number for the link
	 * @param string $display display text for link, defaults to $page if null
	 * 
	 * @return string link for control
	 */
	private function _pageLink($page, $display = null)
	{
		$display = is_null($display) ? $page : $display;

		return '<a href="'.$this->_pageURLPreservingHash($page).'">'.$display.'</a>';
	}
}

?>