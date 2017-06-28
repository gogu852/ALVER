<?php
namespace Login;
use \PERP\Controller AS Controller;

class Forgot extends Controller
{
	function __construct()
	{
		controller::__construct();
		$this->setParam('page_title','Forgot password');
		$this->module = strtolower(get_class($this));
	}
	
	function get()
	{
		if($this->is_logged)
			$this->redirect('/dashboard');
		
		render_page('forgotpassword.tpl');
	}
	
	function post()
	{
		$email = (isset($_POST['email']) && VerifyEmailAdress($_POST['email']))?$_POST['email']:null;
		$user_data = \R::getRow('SELECT id, email, username, CONCAT(fname, \' \', lname) AS fullname FROM user WHERE email = :email', array('email' => $email));
		
		if($user_data && count($user_data) && isset($user_data['id']) && $user_data['id'])
		{
			$user_data['password'] = str_rand();
			$pass_hash = \Crypt::generate_hash($user_data['password']);
			
			\R::exec('UPDATE user SET password = :password WHERE id = :id', array('password' => $pass_hash, 'id' => $user_data['id']));
			
			$this->sendUserPassword($user_data);
			$this->redirect('/forgot-complete');
		}
		else
			$this->setParam('form_error','Invlid email address!');
	}
	
	function complete()
	{
		$this->setParam('complete',true);
		$this->get();
	}
	
	protected function sendUserPassword($user_data)
	{
		$message = 'Hi '.$user_data['fullname'].'<br> 
					Your pERP account password has been changed. To access the account and modify your password, please click the link below.<br>
					<a href="http://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a><br><br>
					User Name: '.$user_data['username'].'<br> 
					password: '.$user_data['password'].'<br><br>
					For more information, please visit <a href="http://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a>.<br><br> 
					Enjoy using our service,<br>
					The pERP Team';
					
		phpMailHTML(array('address'=> 'noreply@backend.perp.endsoft.ro','name'=> 'pERP'), array('address' => $user_data['email'], 'name' => $user_data['fullname']), 'pERP password change', $message);
	}
}
?>