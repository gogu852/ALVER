<?php
namespace Client;
use \PERP\Controller as Controller;
use XBase\Table as Table; //https://github.com/hisamu/php-xbase

class Client extends Controller
{
	var $id = null, $q = null;
	var $affected = 0;
	var $itemsonpage = 20;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		global $counties_array, $countries_array;
		
		$this->setParam('counties_array',$counties_array);
		$this->setParam('countries_array',$countries_array);
		
		$this->setParam('page_title','Clienti');
		$this->module = __NAMESPACE__;
		$this->id = $this->f3->get('PARAMS.id');
		$this->q = (isset($_GET['q']) && $q = trim($_GET['q']))?$q:null;
	}
	
	function get()
	{
		$pn 		= (isset($_GET['pn']) && is_numeric($_GET['pn']) && $_GET['pn'] > 1)?abs(intval($_GET['pn'])):1;
		$cur_index	= ($pn > 1)?($pn - 1)*$this->itemsonpage:0;
		$query = null;
		
		if($this->q)
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS id, cif, name, phone, email, contactperson 
									   FROM client 
									   WHERE (name LIKE :q OR phone LIKE :q OR email LIKE :q OR cif LIKE :q)
									   AND archived != 1
									   ORDER BY name ASC
									   LIMIT 40', array('q' => '%'.$this->q.'%'));
		else
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS id, cif, name, phone, email, contactperson 
									   FROM client 
									   WHERE archived != 1
									   ORDER BY name ASC
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
	
	function loadModalAdd()
	{
		die($this->smarty->fetch(strtolower(__NAMESPACE__).'/modal_addclient.tpl'));
	}
	
	function loadModalImport()
	{
		die($this->smarty->fetch(strtolower(__NAMESPACE__).'/modal_importsaga.tpl'));
	}
	
	function post()
	{
		//if(!cif => tvapayer = 0) / cod = CNP
		
		$name	= (isset($_POST['name']) && $name = trim($_POST['name']))?$name:null;
		$id		= (isset($_POST['id']) && $id = abs(intval($_POST['id'])))?$id:null;
		
		if($name)
		{
			foreach($_POST as $field => $val)
				$data[$field] = ($val)?$val:'';
			
			$data['tvapayer'] = (!$data['tvapayer'])?'0':1;
				
			if(!isset($data['cif']) || !$data['cif'])
				die('CIF/CNP nu este completat!');
			
			
			if(!$id && !$id = \R::getCell('SELECT id FROM client WHERE cif = :cif', array('cif' => $data['cif'])))			
			{
				$obj = \R::dispense('client');
				$obj->import($data);
				
				if(!$obj_id = \R::store($obj))
					die('Salvare esuata!');
					
				die((string)$obj_id);
			}
			else
			{
				$obj = \R::load('client', $id);
				$obj->import($data);
				\R::store($obj);
				die((string)$id);
			}
		}
		
		die('Numele clientului nu este completat!');
	}
	
	function delete()
	{
		if($this->id)
			\R::exec('UPDATE client SET archived = 1 WHERE id = :id', array('id' => $this->id));
		
		$this->redirect('/client');
	}
	
	
	static function getClients()
	{
		$clients =  \R::getAll('SELECT id, name FROM client ORDER BY name ASC');
		
		if($clients && count($clients))
		{
			$clients_list = null;
			
			foreach($clients as $client_data)
				$clients_list[$client_data['id']] = $client_data['clientname'];
			return $clients_list;
			
		}
		
		return null;
	}
	
	function importClients()
	{
		global $counties_array;
		$counties2code_array = require_once(HELPERS_DIR.'counties2code_array.php');
		$dbffile = (isset($_POST['dbffile']) && $_POST['dbffile'])?$_POST['dbffile']:null;
		$fileattachname = (isset($_POST['fileattachname']) && $_POST['fileattachname'])?trim($_POST['fileattachname']):'';
		
		list($type, $filedata) = explode(';', $dbffile);
		$type = str_replace('data:', null, $type);
		$filedata = explode(',', $filedata);
		$tmp_file = dirname(__FILE__).'/tmp/'.$fileattachname;
		file_put_contents($tmp_file, base64_decode($filedata[1]));
			
		if($fileattachname)
		{
			$colheads = array('code' => 'cod', 'name' => 'denumire', 'cif' => 'cod_fiscal', 'regcom' => 'reg_com', 'county' => 'judet', 'address' => 'adresa', 'bank' => 'banca', 'country' => 'tara', 'phone' => 'tel', 'email' => 'email', 'tvapayer' => 'is_tva', );
			$table = new Table($tmp_file,$colheads);
			$items = null;
			
			while ($record = $table->nextRecord())
			{
				$item = null;
				
				foreach ($colheads as $bddf => $csvf)
					$item[$bddf] = ($record->$csvf)?$record->$csvf:'';
				
				
				
				if(array_filter($item) && $item['name'] && $item['cif'])
				{
					if(!$item['country'])
						$item['country'] = 'RO';
					
					if($item['county'])
						$item['county'] = array_search($counties2code_array[$item['county']], $counties_array);
					
					$item['tvapayer'] = ($item['tvapayer'])?1:'0';
					
					if($item['reg_com'] == '-/-/-')
						$item['reg_com'] = '';
					
					if($id = \R::getCell('SELECT id FROM client WHERE cif = :cif', array('cif' => $item['cif'])))
					{
						$itemobj = \R::load('client', $id);
						$itemobj->import($item);
						\R::store($itemobj);
						
						$this->affected++;
					}
					else
					{
						$itemobj = \R::dispense('client');
						$itemobj->import($item);
						
						if(\R::store($itemobj))
							$this->affected++;
					}
				}
			}
			$table->close();
		}
		
		if($this->affected)
		{
			if(file_exists($tmp_file))
				unlink($tmp_file);
			
			die((string) $this->affected);
		}
		die('Operatiunea de import a esuat, verificati fisierul!');
	}
	
	public static function search()
	{
		
		$q = (isset($_GET['q']) && $q = trim($_GET['q']))?$q:null;
		
		if($q)
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS id, name
									   FROM client 
									   WHERE (name LIKE :q OR cif LIKE :q)
									   ORDER BY name ASC
									   LIMIT 40', array('q' => '%'.$q.'%'));
		else
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS id, name 
									   FROM client 
									   ORDER BY name ASC
									   LIMIT 40');
		
		if($items_list && count($items_list))
		{
			foreach($items_list as $item)
				$out[] = array('data' => $item['id'], 'value' => $item['name']);
				
			die(json_encode(array('suggestions' => $out)));
		}
	
		die;
	}
	
	function searchjson()
	{
		$query = (isset($_GET['query']) && trim($_GET['query']))?trim($_GET['query']):null;
		
		if($query)
		{
			$items = \R::getAll('SELECT id, name FROM client WHERE name LIKE :query', array('query' => '%'.$query.'%'));
			
			if($items && count($items))
			{
				foreach($items as $item)
					$suggestions[] = array('value' => $item['name'], 'data' => $item['id']);
						
				die(json_encode(array('suggestions' => $suggestions)));
			}
		}
		
		die('');
	}
	
	function jsondata()
	{
		if($this->id)
		{
			$item = \R::getRow('SELECT * FROM client WHERE id = :id', array('id' => $this->id));
			
			if($item && count($item))
				die(json_encode($item));
		}
		
		die('');
	}
	
	function prefprice()
	{
		if($this->id)
		{
			$productid 	 = (isset($_POST['productid']) && $productid = abs(intval($_POST['productid'])))?$productid:null;
			$clientprice = (isset($_POST['clientprice']) && $clientprice = abs(floatval($_POST['clientprice'])))?$clientprice:'0.0000';
			
			if($productid && $clientprice > 0)
			{
				if($proddata = \Product\Product::getProdData($productid, array('vatprice')))
				{
					if($proddata['vatprice'] >= $clientprice)
						die('Pretul de vanzare nu este corect, acesta trebuie sa fie mai mare decat pretul de baza!');
						
					if($id = \R::getCell('SELECT id FROM clientprefprice WHERE clientid = :clientid AND productid = :productid', array('clientid' => $this->id, 'productid' => $productid)))
					{
						$itemobj = \R::load('clientprefprice', $id);
						$itemobj->import(array('id' => $id, 'clientid' => $this->id, 'productid' => $productid, 'price' => $clientprice));
						\R::store($itemobj);
						die((string)$id);
					}
					else
					{
						$itemobj = \R::dispense('clientprefprice');
						$itemobj->import(array('clientid' => $this->id, 'productid' => $productid, 'price' => $clientprice));
						
						if($id = \R::store($itemobj))
							die((string)$id);
						else
							die('Operatiunea de asciere a esuat!');
					}
				}
				die('Acest produs nu exista sau este arhivat!');
			}
			die('Datele trimise nu sunt corecte!');
		}
		
		die('ID client nu este transmis corect!');
	}
	
	function delprefprice()
	{
		$clientid = $this->f3->get('PARAMS.clientid');
		
		if($clientid && $this->id)
		{
			\R::exec('DELETE FROM clientprefprice WHERE id = :id', array('id' => $this->id));
			$this->redirect('/client/'.$clientid.'#clientprods');
		}
	
		$this->redirect('/client/');
	}
}
?>