<?php
/*
	Plugin Manager module pERP Backend
*/
namespace User;
use \PERP\Controller;

class Users extends \PERP\Controller
{
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged || !$this->is_admin)
			$this->redirect('/');
		
		$this->setParam('page_title','Users Management');
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		$items_list =  \R::findAll('user', 'ORDER BY fname ASC');
		
		if($items_list && count($items_list))
			$this->setParam('items_list', $items_list);
	}
	
	function delete()
	{
		
	}
	
}
?>