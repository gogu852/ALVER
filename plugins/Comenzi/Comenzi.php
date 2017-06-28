<?php
/*
Plugin Name: Comenzi
Plugin Slug: comenzi
Plugin Icon: glyphicon-shopping-cart
Description: Management comenzi.
Version: 1.0
Author: ENDD
*/

namespace Comenzi;
use \PERP\Controller as Controller;
use \Produse\Produs as Produs;

class Comenzi extends Controller
{
	var $itemsonpage = 10;
	var $showcompleted = false;
	var $order_statuses = array('Initiata', 'Livrata');
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->setParam('page_title','Comenzi');
		$this->setParam('order_status',$this->order_statuses);
		
		$this->module = strtolower(get_class($this));
	}

	function get()
	{
	}
	
	function post()
	{
	}
}
?>