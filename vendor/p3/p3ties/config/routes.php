<?php
/**
 * @var P3\Routing\Map
 */
$map = new P3\Routing\Map;

/* Root Test */
$map->root(['to' => 'welcome#show']);

$map->resources('users', [], function($user){
	//$user->resource('profile');
});

// Legacy
//$map->match('/:controller(/:action(/:id))');



?>