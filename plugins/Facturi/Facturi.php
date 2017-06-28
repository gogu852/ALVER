<?php
/*
Plugin Name: Facturi
Plugin Slug: facturi
Plugin Icon: glyphicon-list-alt
Description: Management facturi.
Version: 1.0
Author: ENDD
*/

namespace Facturi;
use \PERP\Controller as Controller;
use \Produse\Produs as Produs;

class Facturi extends Controller
{
	var $itemsonpage = 10;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->setParam('page_title',__NAMESPACE__);
		$this->module = strtolower(get_class($this));
	}

	function get()
	{
		$x = 0;
	}
	
	function post()
	{}
	
	function export()
	{}
	
}
?>