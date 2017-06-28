<?php
namespace Product;
use \PERP\Controller as Controller;

class PFile extends Controller
{
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		global $coduri_articole;
		
		$this->id = $this->f3->get('PARAMS.id');
		$this->Category_class = new Category();
		$this->categories 	  = $this->Category_class->getCategories();
		$this->setParam('categories', $this->categories);
		
		$this->setParam('page_title','Fisa produs');
		$this->coduri_articole = array('0' => 'Alege tip produs');
		$this->coduri_articole += array_keys($coduri_articole);
		$this->setParam('coduri_articole', $this->coduri_articole);
		$this->module = strtolower(get_class($this));
	}
	
	function get()
	{
		if($this->id)
		{
			$item = \R::load('product', $this->id);
			
			if($item && isset($item->id) && $item->id)
			{
				$item = $item->export();
				$item['typename'] = array_search($item['typename'], $this->coduri_articole);
				$this->setParam('item', $item);
				render_page(strtolower(__NAMESPACE__).'/product.tpl');	
				return;
			}
		}
		$this->redirect('/product');
	}
}
?>