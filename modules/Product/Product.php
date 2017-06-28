<?php
namespace Product;
use \PERP\Controller as Controller;
use XBase\Table as Table; //https://github.com/hisamu/php-xbase

class Product extends Controller
{
	var $id = null, $q = null;
	var $affected = 0;
	var $itemsonpage = 20;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		global $coduri_articole;
		
	
		$this->coduri_articole = array('0' => 'Alege tip produs');
		$this->coduri_articole += array_keys($coduri_articole);
		$this->Category_class = new Category();
		
		$this->categories 	  = $this->Category_class->getCategories();
		$this->setParam('categories', $this->categories);
		$this->setParam('coduri_articole', $this->coduri_articole);
		
		$this->setParam('page_title','Produse');
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
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS * 
									   FROM product 
									   WHERE (name LIKE :q OR sagacode LIKE :q)
									   ORDER BY name ASC
									   LIMIT 40', array('q' => '%'.$this->q.'%'));
		else
			$items_list =  \R::getAll('SELECT SQL_CALC_FOUND_ROWS * 
									   FROM product 
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
	
	function edit()
	{
		
	}
	
	function loadModalAdd()
	{
		die($this->smarty->fetch(strtolower(__NAMESPACE__).'/modal_addproduct.tpl'));
	}
	
	function loadModalImport()
	{
		die($this->smarty->fetch(strtolower(__NAMESPACE__).'/modal_importsaga.tpl'));
	}
	
	function post()
	{
		$id		= (isset($_POST['id']) && $id = abs(intval($_POST['id'])))?$id:null;
		
		foreach($_POST as $field => $val)
			$data[$field] = ($val)?$val:'';
			
		if(!isset($data['sagacode']) || $data['sagacode'] == '')
			die('Codul SAGA nu este completat!');
		
		if(!isset($data['name']) || $data['name'] == '')
			die('Denumire produs nu este completat!');
		/* 
		if(!isset($data['typename']) || $data['typename'] == '')
			die('Tip produs nu este completat!');
		else
		{
			if(array_key_exists($data['typename'], $this->coduri_articole))
				$data['typename'] = $this->coduri_articole[$data['typename']];
			else
				die('Tip produs nu este valid!');
		} */
		
		if(!isset($data['category']) || !array_key_exists($data['category'],$this->categories))
			die('Categoria nu este valida!');
		
		
		if(!$id && !$id = \R::getCell('SELECT id FROM product WHERE sagacode = :sagacode', array('sagacode' => $data['sagacode'])))			
		{
			$obj = \R::dispense('product');
			$obj->import($data);
			
			if(!$obj_id = \R::store($obj))
				die('Salvare esuata!');
				
			die((string)$obj_id);
		}
		else
		{
			$obj = \R::load('product', $id);
			$obj->import($data);
			\R::store($obj);
			die((string)$id);
		}
		
		die('Operatiunea de slavare a esuat!');
	}
	
	function delete()
	{
		if($this->id)
			\R::exec('UPDATE product SET archived = 1 WHERE id = :id', array('id' => $this->id));
		
		$this->redirect('/product');
	}
	
	function undelete()
	{
		if($this->id)
			\R::exec('UPDATE product SET archived = 0 WHERE id = :id', array('id' => $this->id));
		
		$this->redirect('/product');
	}
	
	function import()
	{
		$dbffile = (isset($_POST['dbffile']) && $_POST['dbffile'])?$_POST['dbffile']:null;
		$fileattachname = (isset($_POST['fileattachname']) && $_POST['fileattachname'])?trim($_POST['fileattachname']):'';
		
		list($type, $filedata) = explode(';', $dbffile);
		$type = str_replace('data:', null, $type);
		$filedata = explode(',', $filedata);
		$tmp_file = dirname(__FILE__).'/tmp/'.$fileattachname;
		file_put_contents($tmp_file, base64_decode($filedata[1]));
			
		if($fileattachname)
		{
			$colheads = array('sagacode' => 'cod', 'name' => 'denumire', 'um' => 'um', 'vatval' => 'tva' , 'typename' => 'den_tip',  'stock' => 'stoc', 'novatprice' => 'pret_vanz',  'vatprice' => 'pret_v_tva') ;
			$table = new Table($tmp_file,$colheads);
			$items = null;
			
			while ($record = $table->nextRecord())
			{
				$item = null;
				
				foreach ($colheads as $bddf => $csvf)
					$item[$bddf] = ($record->$csvf)?$record->$csvf:'';
				
				if(array_filter($item) && $item['name'] && $item['sagacode'])
				{
					if($id = \R::getCell('SELECT id FROM product WHERE sagacode = :sagacode', array('sagacode' => $item['sagacode'])))
					{
						$itemobj = \R::load('product', $id);
						$itemobj->import($item);
						\R::store($itemobj);
						
						$this->affected++;
					}
					else
					{
						$itemobj = \R::dispense('product');
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
	
	
	
	function searchjson()
	{
		$query = (isset($_GET['query']) && trim($_GET['query']))?trim($_GET['query']):null;
		
		if($query)
		{
			$items = \R::getAll('SELECT id, name FROM product WHERE (name LIKE :query OR sagacode LIKE :query) AND archived != 1', array('query' => '%'.$query.'%'));
			
			if($items && count($items))
			{
				foreach($items as $item)
					$suggestions[] = array('value' => $item['name'], 'data' => $item['id']);
						
				die(json_encode(array('suggestions' => $suggestions)));
			}
		}
		
		die('');
	}
	
	function searchjsonstock()
	{
		$query = (isset($_GET['query']) && trim($_GET['query']))?trim($_GET['query']):null;
		
		if($query)
		{
			$items = \R::getAll('SELECT id, name, stock FROM product WHERE (name LIKE :query OR sagacode LIKE :query) AND archived != 1', array('query' => '%'.$query.'%'));
			
			if($items && count($items))
			{
				foreach($items as $item)
					$suggestions[] = array('value' => $item['name'].' - Stoc '.$item['stock'], 'data' => $item['id']);
						
				die(json_encode(array('suggestions' => $suggestions)));
			}
		}
		
		die('');
	}
	
	function jsondata()
	{
		$clientid = (isset($_GET['clientid']) && $clientid = abs(intval($_GET['clientid'])))?$clientid:null;
		if($this->id)
		{
			if($clientid)
				$item = \R::getRow('SELECT product.*, clientprefprice.price AS prefprice 
									FROM product 
									LEFT JOIN clientprefprice ON clientprefprice.productid = product.id AND clientprefprice.clientid = :clientid
									WHERE product.id = :id', array('clientid' => $clientid,'id' => $this->id));
			else
				$item = \R::getRow('SELECT * FROM product WHERE id = :id', array('id' => $this->id));
			
			if($item && count($item))
			{
				if(isset($item['prefprice']) && !$item['prefprice'])
					$item['prefprice'] = '0';
				
				die(json_encode($item));
			}
		}
		
		die('');
	}
	
	public static function setBulkCategory($from_categories, $to_category)
	{
		\R::exec('UPDATE product SET category = :to_category WHERE category IN (:from_categories)', array('to_category' => $to_category, 'from_categories' => join(',', $from_categories)));
	}
	
	static function getProdData($id = null, $fields = null)
	{
		if($id)
		{
			$query = ($fields && is_array($fields) && count($fields))?join(',',$fields):'*';
			
			return \R::getRow('SELECT '.$query.' FROM product WHERE id = :id AND archived != 1',array('id' => $id));
		}
		
		return null;
	}
	
	
	public static function isInStock($prodid, $reqqty)
	{
		return (\R::getCell('SELECT id FROM product WHERE id = :prodid AND archived != 1 AND stock >= :reqqty', array('prodid' => $prodid, 'reqqty' => $reqqty))?true:false);
	}
}
?>