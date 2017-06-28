<?php
/*
	Plugin Manager module pERP Backend
*/
namespace PluginManager;
use \PERP\Controller;

class PluginManager extends \PERP\Controller
{
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged || !$this->is_admin)
			$this->redirect('/');
		
		$this->setParam('page_title','Plugins Manager');
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		$items_list =  \R::getAll('SELECT * FROM plugins');
		
		if($items_list && count($items_list))
			$this->setParam('plugins_list', $items_list);
	}
	
	function enable()
	{
		if($id = $this->f3->get('PARAMS.id'))
			\R::exec('UPDATE plugins SET plugin_active = 1 WHERE id = :id', array('id' => abs(intval($id))));
		
		$this->redoPluginMenu();
	}
	
	function disable()
	{
		if($id = $this->f3->get('PARAMS.id'))
			\R::exec('UPDATE plugins SET plugin_active = 0 WHERE id = :id', array('id' => abs(intval($id))));
		
		$this->redoPluginMenu();
	}
	
	function position()
	{
		$id 	  = $this->f3->get('PARAMS.id');
		$position = $this->f3->get('PARAMS.position');
		
		if($id && $position)
			\R::exec('UPDATE plugins SET plugin_menu = :position WHERE id = :id', array('id' => abs(intval($id)), 
																						'position' => abs(intval($position)), ));
																						
		$this->redoPluginMenu();
	}
	
	function redoPluginMenu()
	{
		global $submenus;
		
		if($getActivePlugins = getActivePlugins(PLUGINS_DIR))
		{
			for($i =0; $i < count($getActivePlugins); $i++)
			{
				if($submenus && isset($submenus['plugin'][$getActivePlugins[$i]['plugin_dir']]) && count($submenus['plugin'][$getActivePlugins[$i]['plugin_dir']]))
					$getActivePlugins[$i]['submenus'] = $submenus['plugin'][$getActivePlugins[$i]['plugin_dir']];
			}
			die(json_encode($getActivePlugins));
		}
		die;
	}
}
?>