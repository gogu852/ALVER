<?php
namespace Client;
use \PERP\Controller as Controller;

class CFile extends Controller
{
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		global $counties_array, $countries_array;
		
		$this->setParam('counties_array',$counties_array);
		$this->setParam('countries_array',$countries_array);
		
		$this->id = $this->f3->get('PARAMS.id');
		$this->setParam('page_title','Fisa client');
		$this->module = strtolower(get_class($this));
	}
	
	function get()
	{
		if($this->id)
		{
			$item = \R::load('client', $this->id);
			if($item && isset($item->id) && $item->id)
			{
				$item = $item->export();
				$this->setParam('item', $item);
				$this->setParam('assocproducts', $this->assocProduct());
				render_page(strtolower(__NAMESPACE__).'/client_file.tpl');	
				return;
			}
		}
		$this->redirect('/client');
	}
	
	function assocProduct()
	{
		return \R::getAll('SELECT clientprefprice.id, productid, price, name, vatprice 
						   FROM clientprefprice 
						   LEFT JOIN product ON product.id = clientprefprice.productid
						   WHERE clientid = :clientid
						   AND archived < 1
						   ORDER BY clientprefprice.id DESC', array('clientid' => $this->id));
	}
	
}
?>