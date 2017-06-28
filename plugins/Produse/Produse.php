<?php
/*
Plugin Name: Produse
Plugin Slug: produse
Plugin Icon: glyphicon-list-alt
Description: management de produse.
Version: 1.0
Author: ENDD
*/

namespace Produse;
use \PERP\Controller as Controller;


class Produse extends Controller
{
	var $itemsonpage = 20;
	var $q;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->setParam('page_title','Produse');
		$this->setParam('show_import_form','display:none;');
		$this->setParam('show_search_form',\R::getCell('SELECT COUNT(id) FROM product WHERE sapid = pn'));
		$this->module = strtolower(get_class($this));
		$this->q	  = (isset($_GET['q']) && $q = CleanString($_GET['q']))?$q:null;
	}
	
	
	function get()
	{
		Cart::checkOpenOrder();
		Cart::getcartitems();
		
		if($this->q)
			$this->search();
		else
		{
			$pn 		= (isset($_GET['pn']) && is_numeric($_GET['pn']) && $_GET['pn'] > 1)?abs(intval($_GET['pn'])):1;
			$cur_index	= ($pn > 1)?($pn - 1)*$this->itemsonpage:0;
			//$query = (!$this->is_admin)?' AND isactive = 1 ':null;
			$query =  null;
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS *
									   FROM product 
									   WHERE sapid = pn'.$query.'
									   ORDER BY id DESC
									   LIMIT '.$cur_index.', '.$this->itemsonpage);
									   
			if($items_list && count($items_list))
			{
				$total_items = \R::getCell('SELECT FOUND_ROWS() AS total');
				$total_pages = ($total_items > $this->itemsonpage)?ceil($total_items/$this->itemsonpage):1;
				$this->paginate->max_numeric_pages(7);
				$this->setParam('total_items', $total_items);
				$this->setParam('pagination',$this->paginate->do_pagination($total_pages, $this->itemsonpage, $total_items, $pn,explode('@',$this->f3->get('PATTERN'))[0],$this->extra_params));
				$this->setParam('items_list', $items_list);
			}
		}
	}
	
	function post()
	{
		$keys 	 = array('sku', 'brand', 'sapid', 'lvl', 'pn', 'description', 'qty', 'um', 'revizie');
		$product = $failed = null;
		$success = $updated = 0;
		
		if(isset($_FILES['products-file']) && isset($_FILES['products-file']['name']) && $_FILES['products-file']['tmp_name'] && file_exists($_FILES['products-file']['tmp_name']))
		{
			$products = array_map('str_getcsv', explode("\n",file_get_contents($_FILES['products-file']['tmp_name'])));
			
			if(count($products))
			{
				foreach($products as $p)
				{
					if(count($p) == count($keys))
					{
						$product = array_combine($keys, array_values($p));
						
						if(!$obj_id = \R::getCell('SELECT id FROM product WHERE sapid = :sapid AND pn = :pn', array('sapid' => $product['sapid'],'pn' => $product['pn'])))
						{
							$obj = \R::dispense('product');
							$obj->import($product);
							
							if(!$obj_id = \R::store($obj))
								$failed[] = $product['sapid'];
							else
								$success++;
						}
						else
						{
							$product['id'] = $obj_id;
							$obj = \R::load('product', $obj_id);
							$obj->import($product);
							\R::store($obj);
							$updated++;
						}
					}
					$product = null;
				}
			}
			
			$this->setParam('import_success',$success);
			$this->setParam('import_updated',$updated);
			$this->setParam('import_failed',($failed && count($failed))?join('<br>',$failed):0);
			$this->setParam('show_import_form','');
		}
		$this->get();
	}
	
	function delete()
	{
		/* $id = abs(intval($this->f3->get('PARAMS.id')));
		if($id)
			\R::exec('DELETE FROM balance WHERE id = :id', array('id' => $id)); */
	}
	
	function search()
	{
		$q = (isset($_GET['q']) && $q = trim($_GET['q']))?$q:null;
		$query = (!$this->is_admin)?' AND isactive = 1 ':null;
		$items_list = \R::getAll('SELECT * FROM product WHERE sapid = pn '.$query.' AND (sapid LIKE :q OR sku LIKE :q)', array('q' => '%'.$q.'%'));
			
		if($items_list && count($items_list))
			$this->setParam('items_list', $items_list);
			
		$this->setParam('query_term', $q);
	}
	
}
?>