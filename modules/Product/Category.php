<?php
/*
	Product module
*/
namespace Product;
use \PERP\Controller;

class Category extends \PERP\Controller
{
	var $id = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged || !$this->is_admin)
			$this->redirect('/');
		
		$this->setParam('page_title','Categorii');
		$this->id = $this->f3->get('PARAMS.id');
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		$items_list =  \R::getAll('SELECT * FROM category WHERE undeletable = 0 ORDER BY parent, id, name ASC');
		
		if($items_list && count($items_list))
			$items_list = $this->buidTree($items_list);
		
		$this->setParam('items_list', $items_list);
		render_page(strtolower($this->module).'/category.tpl');
	}

	function view()
	{
		if($this->id)
		{
			$item_data = \R::load('category', $this->id);
			die(json_encode($item_data->export()));
		}
		die('0');
	}
	
	function post()
	{
		$parent = (isset($_POST['parent']) && $parent = abs(intval($_POST['parent'])))?$parent:0;
		$name	= (isset($_POST['name']) && $name = trim($_POST['name']))?$name:null;
		$description = (isset($_POST['description']) && $description = trim($_POST['description']))?$description:'';
		$id			 = (isset($_POST['id']) && $id = abs(intval($_POST['id'])))?$id:null;
		$lvl 		 = 0;
		
		if($name)
		{
			if($parent)
			{
				$parent_lvl = \R::getCell('SELECT lvl FROM category WHERE id = :id', array('id' => $parent));
				$lvl = $parent_lvl+1;
			}
				
			if(!$id)
			{
				if(!$id_exists = \R::getCell('SELECT id FROM category WHERE name = :name AND parent = :parent', array('name' => $name, 'parent' => $parent)))
				{
					$obj = \R::dispense('category');
					$obj->import(array('name' => $name, 'parent' => $parent, 'description' => $description, 'lvl' => $lvl));
					
					if(!$obj_id = \R::store($obj))
						die('Salvare esuata!');
					
					die((string)$obj_id);
				}
				die('O categorie cu acest nume si parinte exista deja!');
			}
			else
			{
				$obj = \R::load('category', $id);
				$obj->import(array('name' => $name, 'parent' => $parent, 'description' => $description, 'lvl' => $lvl));
				\R::store($obj);
				die((string)$id);
			}
		}
		
		die('Numele categoriei nu este completat!');
	}
	
	function delete()
	{	
		$to_delete = array();
		
		if($this->id)
		{
			$items_list =  \R::getAll('SELECT * FROM category WHERE undeletable = 0 ORDER BY parent, id, name ASC');
			$nocat_id 	=  \R::getCell('SELECT id FROM category WHERE undeletable = 1 LIMIT 1');
			$to_delete[] = $this->id;
			
			if($items_list && count($items_list))
			{
				$items_list = $this->getSubcats($items_list, $this->id);
				
				if($items_list && count($items_list))
				{
					foreach($items_list as $item)
						$to_delete[] = $item['id'];
				}
				
			}
			\R::exec('DELETE FROM category WHERE id IN ('.join(',', $to_delete).')');
			Product::setBulkCategory($to_delete, $nocat_id);
		}
		$this->redirect('/'.strtolower($this->module).'/category');
	}
	
	protected function buidTree($items_list)
	{
		$out_list = $subcats = $lvls = null;
		 
		foreach($items_list as $item)
		{
			$out_list[$item['id']] = $item;
			$out_list = $out_list + $this->getSubcats($items_list, $item['id']);
		}
		
		return $out_list;
	}
	
	function getSubcats($items_list, $parent)
	{
		$out_list = array();
		
		foreach($items_list as $item)
		{
			if($item['parent'] == $parent)
			{
				$out_list[$item['id']] = $item;
				$out_list = $out_list + $this->getSubcats($items_list, $item['id']);
			}
		}
		
		return $out_list;
	}
	
	public static function getNoNameCategory()
	{
		return \R::getCell('SELECT id FROM category WHERE undeletable = 1 LIMIT 1');
	}
	
	public function getCategories($parent = null)
	{
		$items_list =  \R::getAll('SELECT * FROM category WHERE undeletable = 0 ORDER BY parent, id, name ASC');
		
		if($items_list && count($items_list))
		{
			if($parent)
				return $this->getSubcats($items_list, $this->id);
			
			return $this->buidTree($items_list);
		}
		
		return null;
	}
}
?>