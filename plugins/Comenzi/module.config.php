<?php
return array('primary_route' => array('slug' => 'comenzi', 'type' => 'map', 'action' => 'Comenzi\Comenzi'),
			 'routes' 		 => array('@id' => array('type' 	=> 'map',
													 'route' => '@id',
													 'action'=> 'Comenzi\Comanda',),
									  'add' 	   => array('type' 	=> 'GET',
															'route' => 'add',
															'action'=> 'Comenzi\Comanda->add',),),);
?>