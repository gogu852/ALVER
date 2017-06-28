<?php
namespace Produse;
use \PERP\Controller as Controller;

class Produs extends Controller
{
	var $id = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->module = __NAMESPACE__;
		$this->id = $this->f3->get('PARAMS.id');
		$this->setParam('page_title',(!$this->id)?'Adauga produs':'Modifica produs');
	}
	
	function get()
	{
	/* 	if($_SESSION['user_data']['role'] > 2)
			$this->redirect('/produse'); */
		
		if($this->id)
		{
			$produs = \R::load('product', $this->id);
			
			if($produs->sapid != $produs->pn || (!$this->is_admin && !$produs->isactive))
				$this->redirect('/produse');
			
			if($produs->id)
			{
				$produs_data = $produs->export();
				
				$subproduse = \R::getAll('SELECT * FROM product WHERE sapid = :sapid AND pn != :sapid ORDER BY id ASC', array('sapid' => $produs->sapid));
			
				$produs_data['subproduse'] = (count($subproduse))?$subproduse:null;
			}
			
			$this->setParam('produs_data', $produs_data);
		}
		
		render_page('plugins/produse/produs.tpl');
	}
	
	function post()
	{
		$id = (isset($_POST['id']) && $id = abs(intval($_POST['id'])))?$id:null;
		
		$fields = array('sku' => 'SKU', 'brand' => 'BRAND', 'sapid' => 'SAP ID', 'description' => 'Descriere', 'revizie' => 'Revizie');
		
		foreach($fields as $field => $desc)
		{
			if(isset($_POST[$field]) && $$field = trim($_POST[$field]))
			{
				$produs[$field] = $$field;
				$message[] = $desc.' : '.$$field;
			}
			else
			{
				die($desc.' nu este completat!');
			}
		}
		
		$produs['pn'] = $produs['sapid'];
		$produs['lvl'] = '0';
		
		
		if($id)
		{
			$pprodus = \R::load('product', $id);
			$pprodus->import($produs);
			\R::store($pprodus);
			self::sendAlert(2, $produs, 'Detalii produs '.join('<br>',$message));
			die((string)$id);
		}	
		else
		{
			if($id_check = \R::getCell('SELECT id FROM product WHERE sapid = :sapid', array('sapid' => $produs['sapid'])))
					die('Acest produs exista deja in baza de date!');
			
			$pprodus = \R::dispense('product');
			$pprodus->import($produs);
			$id = \R::store($pprodus);
			self::sendAlert(1, $produs, 'Detalii produs '.join('<br>',$message));
			die((string)$id);
		}
		
		
		exit;
	}
	
	function delete()
	{
		//if($this->is_admin && $this->id)
		if($this->id)
		{
			\R::exec('UPDATE product SET isactive = :isactive WHERE id = :id', array('isactive' => (isset($_GET['isactive']) && $_GET['isactive'])?1:'0', 'id' => $this->id));
			
			$alert_type = (isset($_GET['isactive']) && $_GET['isactive'])?4:3;
			$message = ($alert_type == 4)?'Activare produs principal':'Dezactivare produs principal';
			self::sendAlert($alert_type, array('sapid' => \R::getCell('SELECT sapid FROM product WHERE id = :id', array('id' => $this->id))), $message);
		}
		
		$this->redirect('/produse');
	}
	
	function getSubProdusTemplate()
	{
		$this->smarty->assign('produs_data', array('sapid' => $this->f3->get('PARAMS.sapid')));
		die($this->smarty->fetch('plugins/produse/subprodus.tpl'));
	}
	
	function setSubProdus()
	{
		$id = (isset($_POST['id']) && $id = abs(intval($_POST['id'])))?$id:null;
		
		$fields = array('sapid' => 'SAP ID produs principal', 'pn' => 'PN', 'qty' => 'Cantitate', 'um' => 'UM', 'revizie' => 'Revizie');
		
		foreach($fields as $field => $desc)
		{
			if(isset($_POST[$field]) && $$field = trim($_POST[$field]))
			{
				$subprodus[$field] = $$field;
				$message[] = $desc.' : '.$$field;
			}
			else
				die($desc.' nu este completat!');
		}
		
		$subprodus['description'] = (isset($_POST['description']) && $description = trim($_POST['description']))?$description:'';
		$subprodus['lvl'] = '1';
		$subprodus['qty'] = (!is_numeric($subprodus['qty']))?1:$subprodus['qty'];
		
		if($id)
		{
			$sprodus = \R::load('product', $id);
			$sprodus->import($subprodus);
			\R::store($sprodus);
			self::sendAlert(2, $subprodus, 'Subprodus modificat '.join('<br>',$message));
			die((string)$id);
		}	
		else
		{
			if($id_check = \R::getCell('SELECT id FROM product WHERE sapid = :sapid AND pn = :pn', array('sapid' => $subprodus['sapid'], 'pn' => $subprodus['pn'])))
					die('Acest subprodus se afla deja in componenta produsului principal!');
			
			$sprodus = \R::dispense('product');
			$sprodus->import($subprodus);
			$id = \R::store($sprodus);
			self::sendAlert(2, $subprodus, 'Subprodus adaugat '.join('<br>',$message));
			die((string)$id);
		}
		exit;
	}
	
	function delSubProdus()
	{
		if($this->id)
		{
			$spdata = \R::getRow('SELECT sapid, pn, description FROM product WHERE id = :id', array('id' => $this->id));
			if($spdata && count($spdata))
			{
				\R::exec('DELETE FROM product WHERE id = :id', array('id' => $this->id));
				self::sendAlert(2, $spdata, 'De la produsul principal : '.$spdata['sapid'].' a fost sters sub-produsul avand<br> Descriere: '.$spdata['description'].'<br>PN : '.$spdata['pn']);
			}
		}
		exit;
	}
	
	public static function hasSubProducts($sapid)
	{
		return \R::getCell('SELECT COUNT(id) FROM product WHERE sapid = :sapid  AND pn != :sapid ORDER BY id ASC', array('sapid' => $sapid));
	}
	
	public static function getSubProducts($sapid)
	{
		$subproduse = \R::getAll('SELECT * FROM product WHERE sapid = :sapid  AND pn != :sapid ORDER BY id ASC', array('sapid' => $sapid));
			
		return (count($subproduse))?$subproduse:null;
	}
	
	function loadQuickViewTemplate()
	{
		if($this->id)
		{
			$produs = \R::load('product', $this->id);
			
			if($produs->id)
			{
				$produs_data = $produs->export();
				
				$subproduse = \R::getAll('SELECT * FROM product WHERE sapid = :sapid AND pn != :sapid ORDER BY id ASC', array('sapid' => $produs->sapid));
			
				$produs_data['subproduse'] = (count($subproduse))?$subproduse:null;
				
				$this->setParam('item', $produs_data);
			}
		}
		
		die($this->smarty->fetch('plugins/produse/produs-modal.tpl'));
	}
	
	function irevision()
	{
		$alpha = range('A','Z');
		$id = $this->f3->get('PARAMS.id');
		if($id)
		{
			if($currev = \R::getCell('SELECT revizie FROM product WHERE id = :id', array('id' => $id)))
			{
				$next_version = $alpha[array_search($currev[1],$alpha)+1];
				$new_revison = $currev[0].$next_version;
				\R::exec('UPDATE product SET revizie = :revizie WHERE id = :id', array('revizie' => $new_revison,'id' => $id));
				die($new_revison);
			}
		}
		die('1');
	}
	
	public static function sendAlert($type = null, $productdata, $message = null)
	{
		switch($type)
		{
			//adaugare produs principal
			case 1: $subject = 'Adaugare produs principal '.$productdata['sapid']; break;
			//modificare produs principal
			case 2: $subject = 'Modificare produs principal '.$productdata['sapid']; break;
			//dezactivare produs
			case 3: $subject = 'Dezactivare produs principal '.$productdata['sapid']; break;
			//activare produs
			case 4: $subject = 'Activare produs principal '.$productdata['sapid']; break;
		}
		
		phpMailHTML(array('address'=> SUPPORT_EMAIL,'name'=> 'ROEL'), array('address'=> SUPPORT_EMAIL,'name'=> 'ROEL'), $subject, $message.'<br> Operatiune executata de userul: '.$_SESSION['user_data']['username']);
	}
}
?>