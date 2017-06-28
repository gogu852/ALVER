<?php
namespace Produse;
use \PERP\Controller as Controller;

class Cart extends Controller
{
	var $id = null;
	var $oid = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->module = __NAMESPACE__;
		$this->id = $this->f3->get('PARAMS.id');
		$this->setParam('page_title','Cos cumparaturi');
		
		$this->oid = (isset($_SESSION['user_data']['orderid']) && $_SESSION['user_data']['orderid'])?$_SESSION['user_data']['orderid']:null;
	}
	
	function get()
	{
		$this->getcartitems();
		render_page('plugins/produse/cart.tpl');
	}
	
	function addToCart()
	{
		if($this->id)
		{
			$produs = \R::load('product', $this->id);
			
			if($produs->id)
			{
				$produs_data = $produs->export();
				
				if(!$this->oid)
					$this->setOrderId();
				
				if($this->oid)
				{
					if(!$id_check = \R::getCell('SELECT id FROM cart WHERE oid = :oid AND sapid = :sapid', array('oid' => $this->oid,'sapid' => $produs_data['sapid'])))
					{
				
						$cartprod = \R::dispense('cart');
						$cartprod->import(array('oid' => $this->oid, 'sapid' => $produs_data['sapid'], 'revision' => ($produs_data['revision'])?$produs_data['revision']:'AA','status'=>'0'));
						$id = \R::store($cartprod);
						
						if($id)
							echo $id;
					}
				}
			}
		}
		exit;
	}
	
	protected function setOrderId()
	{
		$order = \R::dispense('orders');
		$order->import(array('odelivery' => '0000-00-00', 'odate' => date('Y-m-d H:i:s'), 'submited' => '0', 'uid' => $_SESSION['user_data']['id']));
		
		if($this->oid = \R::store($order))
			$_SESSION['user_data']['orderid'] = $this->oid;
		
		return;
	}
	
	function post()
	{
		if($this->oid)
		{
			if(isset($_POST['action']) && $_POST['action'] == 'updateqty')
			{
				foreach($_POST['qty'] as $id => $qty)
					\R::exec('UPDATE cart SET qty = :qty WHERE id = :id', array('id' => $id, 'qty' => ($qty)?$qty:'1'));
			}
			else
			{
				$odelivery = (isset($_POST['odelivery']) && $odelivery = trim($_POST['odelivery']))?$odelivery:null;
				
				if($odelivery)
				{
					sscanf($odelivery, "%d/%d/%d", $day, $month, $year);
					$odelivery = $year.'-'.$month.'-'.$day;
					\R::exec('UPDATE orders SET odelivery = :odelivery, submited = 1 WHERE id = :id', array('odelivery' => $odelivery, 'id' => $this->oid));
					unset($_SESSION['user_data']['orderid']);
					\Comenzi\Comenzi::sendMailAlert(1, $this->oid, $_SESSION['user_data']['email']);
					render_page('plugins/produse/ordercomplete.tpl');
					return;
				}
				
				$this->setParam('is_order_error','Data de livrare nu este completata!');
			}
		}
		$this->getcartitems();
		render_page('plugins/produse/cart.tpl');
		return;
	}
	
	function delete()
	{
		if($this->oid && $this->id)
			\R::exec('DELETE FROM cart WHERE id = :id  AND oid = :oid', array('id' => $this->id, 'oid' => $this->oid));
		
		$this->redirect('/produse/cart');
	}
	
	function emptycart()
	{
		if($this->oid)
		{
			\R::exec('DELETE FROM cart WHERE oid = :oid', array('oid' => $this->oid));
			\R::exec('DELETE FROM orders WHERE id = :id', array('id' => $this->oid));
			unset($_SESSION['user_data']['orderid']);
		}
		
		$this->redirect('/produse/cart');
	}
	
	function showcart()
	{
		if(self::getcartitems())
				die($this->smarty->fetch('plugins/produse/cartwidget.tpl'));
		exit;
	}
	
	public static function getcartitems()
	{
		global $smarty;
		$cart_prods = null;
		$oid = (isset($_SESSION['user_data']['orderid']) && $_SESSION['user_data']['orderid'])?$_SESSION['user_data']['orderid']:null;
		
		if($oid)
		{
			$cart_prods = \R::getAll('SELECT * FROM cart WHERE oid = :oid', array('oid' => $oid));
			
			if($cart_prods && count($cart_prods))
				$smarty->assign('cart_prods',$cart_prods);
		}
		
		return ($cart_prods)?count($cart_prods):0;
	}
	
	public static function checkOpenOrder()
	{
		global $smarty;
		$has_unfinished_order = false;
		
		if(!isset($_SESSION['user_data']['orderid']))
		{
			if($oid = \R::getCell('SELECT id FROM orders WHERE uid = :uid AND submited = \'0\'', array('uid' => $_SESSION['user_data']['id'])))
			{
				$has_unfinished_order = true;
				$_SESSION['user_data']['orderid'] = $oid;
			}	
			
			$smarty->assign('has_unfinished_order',$has_unfinished_order);
		}
	}
}
?>