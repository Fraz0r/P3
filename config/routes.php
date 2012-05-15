<?php
/**
 * @var P3\Routing\Map
 */
$map = new P3\Routing\Map;

/* Root Test */
$map->root(array('to' => 'welcome#show'));

$map->resources('users', array(), function($user){
	//$user->resource('profile');
});

// Legacy
//$map->match('/:controller(/:action(/:id))');



?>
