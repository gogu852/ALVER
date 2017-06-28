<?php
return array('primary_route' => array('slug' => 'plugin', 'type' => 'map', 'action' => 'PluginManager\PluginManager'),
			 'routes' 		 => array('enable' => array('type' 	=> 'GET',
														'route' => 'enable/@id',
														'action'=> 'PluginManager\PluginManager->enable',),
									  'disable' => array('type' 	=> 'GET',
														'route' 	=> 'disable/@id', 	
														'action'	=> 'PluginManager\PluginManager->disable',),
									  'position' => array('type' 	=> 'GET',
														  'route' 	=> 'position/@id/@position', 		
														  'action'	=> 'PluginManager\PluginManager->position',)));
?>