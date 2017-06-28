<?php
return array('primary_route' => array('slug' => 'serie', 'type' => 'map', 'action' => 'Serie\Serie'),
			 'routes' 		 => array('@id'  => array('type' 	=> 'GET',
													  'route' => '@id',
													  'action'=> 'Serie\Serie->delete',),
									  'setprimary/@id' => array('type' 	=> 'GET',
														  'route' => 'setprimary/@id [ajax]',
														  'action'=> 'Serie\Serie->setPrimary',),				  
									  'ajx-form' => array('type' 	=> 'GET',
														  'route' => 'ajx-form',
														  'action'=> 'Serie\Serie->ajxForm',),
									  ));
?>