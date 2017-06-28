<?php
/*
	Dashboard module pERP Backend
*/
namespace Dashboard;
use \PERP\Controller;

class Dashboard extends \PERP\Controller
{
	function __construct()
	{
		controller::__construct();
		$this->setParam('page_title',get_class($this));
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		if(!$this->is_logged)
			$this->redirect('/');
	}
}
?>