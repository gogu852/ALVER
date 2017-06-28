<?php
namespace Comenzi;
use \PERP\Controller as Controller;
use \Product\Product as Product;

class Comanda extends Controller
{
	var $order_statuses = array('Initiata', 'Livrata');
	
	var $id = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->id = $this->f3->get('PARAMS.id');
		global $counties_array, $countries_array;
		
		$this->setParam('counties_array',$counties_array);
		$this->setParam('countries_array',$countries_array);
		
		$this->setParam('page_title', ($this->id)?'Comanda #'.$this->id:'Comanda noua');
		$this->setParam('oid',$this->id);
		$this->setParam('order_status',$this->order_statuses);
	}
	
	function get()
	{
		$this->setParam('order_data', $order_data);
		render_page('plugins/'.strtolower(__NAMESPACE__).'/comanda_view.tpl');
	}
	
	function add()
	{
		render_page('plugins/'.strtolower(__NAMESPACE__).'/comanda_add.tpl');
	}
	
	function post()
	{
		$shipping_fields = array('deliveryaddress' => 'Adresa de livrare nu este completa', 'deliverycity' => 'Orasul nu este completat', 'deliverycountry' => 'Tara nu este selectata');
		
		$client_data = (isset($_POST['client-data']) && $_POST['client-data'] && count($_POST['client-data']))?$_POST['client-data']:null;
		$products	 = (isset($_POST['product']) && $_POST['product'] && count($_POST['product']))?$_POST['product']:null;
		$dshipping 	 = false;
		
		if($client_data)
		{
			$data_order['clientid'] 	  = (isset($client_data['clientid']) && $client_data['clientid'])?abs(intval($client_data['clientid'])):null;
			$data_order['isdiffshipping'] = $client_data['isdiffshipping'];
			
			if(!$products)
				die('Nu sunt adaugate produse in comanda');
			
			if($data_order['isdiffshipping'])
			{
				if(isset($client_data['diff-shipping']) && $client_data['diff-shipping'] && count($client_data['diff-shipping']))
				{
					foreach($shipping_fields as $bddfield => $errornotcompleted)
					{
						if(isset($client_data['diff-shipping'][$bddfield]) && $client_data['diff-shipping'][$bddfield])
							$dshipping[$bddfield] = $client_data['diff-shipping'][$bddfield];
						else
							die($errornotcompleted);
					}
				}
				$data_order['diffshipping'] = serialize($dshipping);
			}
			
			foreach($products as $prodid => $proddata)
			{
				if(!Product::isInStock($prodid, $proddata['qty']))
					die('Produsul aflat la pozitia '.$proddata['crt'].' nu are stoc suficient!');
			}
			
			if($this->id)
			{
				$obj = \R::load('order', $this->id);
				$data_order['odate'] = $obj->odate;
				$obj->import($data_order);
				\R::store($obj);
			}
			else
			{
				$data_order['odate'] = date('Y-m-d H:i');
				$obj = \R::dispense('order');
				$obj->import($data_order);
				
				if(!$this->id = \R::store($obj))
					die('Salvare esuata!');
			}
		}
		
	}
	
}
?>