<?php
/*
	Plugin Manager module pERP Backend
*/
namespace Register;
use \PERP\Controller;

class Register extends \PERP\Controller
{
	var $code = null;
	
	function __construct()
	{
		controller::__construct();
		
		if($this->is_logged)
			$this->redirect('/');
		
		$this->setParam('page_title',get_class($this));
		$this->module = strtolower(get_class($this));
		
		$this->code = $this->f3->get('PARAMS.icode');
	}
	
	function get()
	{
		if($this->code)
		{
			$code_valid = \R::getRow('SELECT id, email FROM invites WHERE code = :code AND DATE(expdate) >= CURDATE()', array('code' => $this->code));
			if($code_valid && count($code_valid))
			{
				$this->setParam('code_valid', true);
				$this->setParam('invite_email', $code_valid['email']);
			}
			else
				$this->setParam('code_valid', false);
			
			render_page('register.tpl');
		}
		else
			$this->redirect('/');
	}
	
	function post()
	{
		if(!$this->setUser())
		{
			$this->setParam('user_data', $_POST);
			return $this->get();
		}
		
		$this->redirect('/users');
	}
	
	function setUser()
	{
		$required_fields = array('fname' => 'First Name', 'lname' => 'Last Name', 'username' => 'Username', 'password' => 'Password');
		
		if($this->code)
		{
			$basic_data = \R::getRow('SELECT invites.id, invites.email, user.email AS admin_email 
									  FROM invites 
									  LEFT JOIN user ON user.id = invites.who
									  WHERE code = :code 
									  AND DATE(expdate) >= CURDATE()', array('code' => $this->code));
			
			$user_data['email'] = $basic_data['email'];
			$user_data['role']  = 1;
		}
		else
			$this->redirect('/');
		
		foreach($required_fields as $field => $field_description)
		{
			if(isset($_POST[$field]) && $$field = trim($_POST[$field]))
				$user_data[$field] = $$field;
			else
			{
				$this->setParam('form_error', $field_description.' is missing!');
				return false;
			}
		}
		
		$cpassword  = (isset($_POST['cpassword']) && $_POST['cpassword'])?$_POST['cpassword']:null;
		 
		if(!$cpassword)
		{
			$this->setParam('form_error', 'Confirm password is missing!');
			return false;
		}
		 
		if($cpassword != $user_data['password'])
		{
			$this->setParam('form_error', 'Password and confirmation doesn\'t match!');
			return false;
		}
		
		//Encrypt user password before storing
		$user_data['password'] = \Crypt::generate_hash($user_data['password']);
		
		
		if(\R::getCell('SELECT COUNT(id) FROM user WHERE username = :username', array('username' => $user_data['username'])))
		{
			$this->setParam('form_error', 'User with that username already exists!');
			return false;
		}
		
		if(\R::getCell('SELECT COUNT(id) FROM user WHERE email = :email', array('email' => $user_data['email'])))
		{
			$this->setParam('form_error', 'User with that email already exists!');
			return false;
		}
		
		$user_data['created'] = date('Y-m-d H:i');
		$user_data['first_login'] = 1;	
		$user = \R::dispense('user');
		$user->import($user_data);
		$user_data['id'] = \R::store($user);
		$user_data['admin_email'] =  $basic_data['admin_email'];
		
		\R::exec('DELETE FROM invites WHERE id = :id', array('id' => $basic_data['id']));
		
		$this->sendUserPassword($user_data);
		$this->sendAdminNotification($user_data);	
		
		$this->redirect('/');
	}
	
	protected function sendUserPassword($user_data)
	{
		$message = 'Hi '.$user_data['fname'].' '.$user_data['lname'].'<br> 
					Your pERP account has been created. To access the account and modify your password, please click the link below.<br>
					<a href="http://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a><br><br>
					User Name: '.$user_data['username'].'<br> 
					password: '.$_POST['password'].'<br><br>
					For more information, please visit <a href="http://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a>.<br><br> 
					Enjoy using our service,<br>
					The pERP Team';
					
		phpMailHTML(array('address'=> 'noreply@backend.perp.endsoft.ro','name'=> 'pERP'), array('address' => $user_data['email'], 'name' => $user_data['fname'].' '.$user_data['lname']), 'Welcome to pERP', $message);
	}
	
	protected function sendAdminNotification($user_data)
	{
		$message = 'Hi,<br> 
					A new user has registered, being referred by your invitation. Please login <a href="http://'.$_SERVER['HTTP_HOST'].'/?redir=/users/user/'.$user_data['id'].'">'.$_SERVER['HTTP_HOST'].'/?redir=/users/user/'.$user_data['id'].'</a> into your administrative account in order to set the new user credentials.<br><br> 
					New account details:<br><br>
					Name: '.$user_data['fname'].' '.$user_data['lname'].'<br> 
					Email: '.$user_data['email'].'<br> 
					User Name: '.$user_data['username'].'<br><br> 
					
					For more information, please visit <a href="http://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a>.<br><br> 
					Enjoy using our service,<br>
					The pERP Team';
					
		phpMailHTML(array('address'=> 'noreply@backend.perp.endsoft.ro','name'=> 'pERP'), array('address' => $user_data['admin_email'], 'name' => $user_data['admin_email']), 'User registered by invitation', $message);
	}
	
}
?>