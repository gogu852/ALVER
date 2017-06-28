<?php
namespace Facturi;
use \PERP\Controller as Controller;
use \Serie\Serie as Serie;
use \Client\Client as Client;
use \Product\Product as Product;
use \Company\Company as Company;

class Factura extends Controller
{
	public static $doctype = 1;
	var $id = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');

		global $currency_array;
		//preluare serie pentru document nou
		$this->seriesdata = Serie::getSerie(self::$doctype);
		$this->setParam('seriesdata',$this->seriesdata);
		$this->setParam('currency_array', $currency_array);
	
		if(!$this->seriesdata)
			$this->setParam('series_modal',Serie::returnModal(self::$doctype));
		
		$this->id = $this->f3->get('PARAMS.id');
		$this->setParam('page_title','Factura');
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		if($this->id)
		{
			/* $item = \R::load('product', $this->id);
			
			if($item && isset($item->id) && $item->id)
			{
				$item = $item->export();
				$item['typename'] = array_search($item['typename'], $this->coduri_articole);
				$this->setParam('item', $item); */
				render_page('/plugins/'.strtolower(__NAMESPACE__).'/factura.tpl');	
				return;
			//}
		}
		$this->redirect('/product');
	}
	
	function post()
	{
		$pdata = $_POST;
		$pdata['dataemitere'] = date2sql($pdata['dataemitere']);
		$pdata['datascadenta'] = date2sql($pdata['datascadenta']);
		$pdata['entryid'] = Serie::getDocNumberBySerieId($pdata['serieid']);
		$serieid = $pdata['serieid'];
		unset($pdata['serieid']);
		
		//print_r($pdata);
	}
}
?>