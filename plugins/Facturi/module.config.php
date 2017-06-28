<?php
return array('primary_route' => array('slug' => 'facturi', 'type' => 'map', 'action' => 'Facturi\Facturi'),
			 'routes' 		 => array('@id' => array('type' 	=> 'map',
													 'route' => '@id',
													 'action'=> 'Facturi\Factura',),
									 'export' 	   => array('type' 	=> 'GET',
															 'route' => 'export',
															 'action'=> 'Facturi\Facturi->export',),));
?>