<?php
return array('primary_route' => array('slug' => 'client', 'type' => 'map', 'action' => 'Client\Client'),
			 'routes' 		 => array('@id'		 => array('type' 	=> 'map',
														  'route' => '@id',
														  'action'=> 'Client\CFile',),						  
									  'delete/@id'		 => array('type' 	=> 'GET',
																  'route' => 'delete/@id',
																  'action'=> 'Client\Client->delete',),
									  'modaladd'	 => array('type' 	=> 'GET',
																  'route' => 'modaladd [ajax]',
																  'action'=> 'Client\Client->loadModalAdd',),
									  'modalimport'	 => array('type' => 'GET',
															  'route' => 'modalimport [ajax]',
															  'action'=> 'Client\Client->loadModalImport',),
									  'import'	 => array('type' 	=> 'POST',
														  'route' => 'import [ajax]',
														  'action'=> 'Client\Client->importClients',),
									  'searchjson'	 => array('type' 	=> 'GET',
														  'route' => 'searchjson [ajax]',
														  'action'=> 'Client\Client->searchjson',),
									  'jsondata/@id' => array('type' 	=> 'GET',
														  'route' => 'jsondata/@id [ajax]',
														  'action'=> 'Client\Client->jsondata',),
									  'prefprice/@id'	 => array('type' 	=> 'POST',
														  'route' => 'prefprice/@id [ajax]',
														  'action'=> 'Client\Client->prefprice',),
									  'delprefprice/@clientid/@id'	 => array('type' 	=> 'GET',
																			  'route' => 'delprefprice/@clientid/@id',
																			  'action'=> 'Client\Client->delprefprice',),));
?>