<?php
/*
	Plugin Manager module pERP Backend
*/
namespace User;
use \PERP\Controller;

class Invite extends \PERP\Controller
{
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged || !$this->is_admin)
			$this->redirect('/');
		
		$this->setParam('page_title', 'Invite Users');
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		$this->myInvites();
		render_page('user/invite_form.tpl');
	}
	
	function post()
	{
		$emails = null;
		$who = $_SESSION['user_data']['fname'].' '.$_SESSION['user_data']['lname'];
		
		if(isset($_POST['invite-email']) && $_POST['invite-email'] && count($_POST['invite-email']))
		{
			foreach($_POST['invite-email'] as $email)
			{
				if(VerifyEmailAdress($email) && (!$emails || ($emails && !array_key_exists($email, $emails))))
						$emails[$email] = str_rand(6).hash('adler32',$email);
			}
		}
		
		if($emails && count($emails))
		{
			foreach($emails as $email => $code)
			{
				$invite = \R::dispense( 'invites' );
				$invite->email = $email;
				$invite->code  = $code;
				$invite->expdate  = date('Y-m-d', strtotime('+1 week'));
				$invite->who	  = $_SESSION['user_data']['id'];
				
				if($id = \R::store( $invite ))
					$this->sendInvite($email, $code, $who);
			}
		}
			
		$this->redirect('/users/invite-complete');
	}
	
	protected function sendInvite($email, $code, $who)
	{
		$who 	 = \R::getCell('SELECT CONCAT(fname,\' \', lname) FROM user WHERE id = :id', array('id' => $who));
		
		$message = $message = 'Hi,<br>'.$who.' has sent you an invitation to register into pERP application.<br>
					In order to create your account please click the link below.<br>
					<a href="http://'.$_SERVER['HTTP_HOST'].'/register/'.$code.'">'.$_SERVER['HTTP_HOST'].'/register/'.$code.'</a><br><br>

					For more information, please visit <a href="http://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a>.<br><br> 
					Enjoy using our service,<br>
					The pERP Team';
		
		phpMailHTML(array('address'=> 'noreply@backend.perp.endsoft.ro','name'=> 'pERP'), array('address' => $email, 'name' => $email), 'pERP Invite', $message);
	}
	
	function checkInviteExists()
	{
		$email = (isset($_POST['email']) && VerifyEmailAdress($_POST['email']))?$_POST['email']:null;
		
		if($email)
		{
			if($exists = \R::getCell('SELECT id FROM invites WHERE email= :email', array('email' => $email)))
				die('exists');
			die('valid');
		}
		die('invalid');
	}
	
	protected function myInvites()
	{
		$items_list = \R::getAll('SELECT * FROM invites WHERE who = :who ORDER BY expdate ASC ', array('who' =>  $_SESSION['user_data']['id']));
		
		if($items_list && count($items_list))
		{
			for($i =0; $i < count($items_list); $i++)
			{
				$items_list[$i]['sent'] = date('Y-m-d', strtotime($items_list[$i]['expdate'].'-1 week'));
				$items_list[$i]['is_expired'] = ((time()-(60*60*24)) > strtotime($items_list[$i]['expdate']))?true:false;
			}
			
			$this->setParam('my_invites_list', $items_list);
		}
	}
	
	function delete()
	{
		$id = abs(intval($this->f3->get('PARAMS.id')));
		
		if($id)
			\R::exec('DELETE FROM invites WHERE who = :who AND id = :id', array('who' =>  $_SESSION['user_data']['id'], 'id' => $id));
		
		$this->redirect('/users/invite');
	}
	
	function resend()
	{
		$id = abs(intval($this->f3->get('PARAMS.id')));
		
		if($id)
		{
			$invite = \R::load( 'invites', $id );
			$invite->expdate = date('Y-m-d', strtotime('+1 week'));
			\R::store( $invite );
			$this->sendInvite($invite->email, $invite->code, $invite->who);
			$this->redirect('/users/invite-complete');
		}
		
		$this->redirect('/users/invite');
	}
	
	function complete()
	{
		$this->setParam('submit_complete', true);
		$this->get();
	}
}
?>