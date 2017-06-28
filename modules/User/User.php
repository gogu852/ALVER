<?php
/*
	Plugin Manager module pERP Backend
*/
namespace User;
use \PERP\Controller;

class User extends \PERP\Controller
{
	var $id = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged || !$this->is_admin)
			$this->redirect('/');
		
		$this->id = $this->f3->get('PARAMS.id');
		$this->module = __NAMESPACE__;
	}
	
	function get()
	{
		$this->setParam('page_title',($this->id)?'Edit user':'Add User');
		
		$this->getPlugins();
		
		if($this->id)
		{
			$this->getUserData();
			$this->userLoginHistory();
			$this->userActionsHistory();
		}
		
		render_page('user/add_form.tpl');
	}
	
	protected function getPlugins()
	{
		$items_list =  \R::getAll('SELECT id, plugin_name FROM plugins WHERE plugin_active = 1');
		
		if($items_list && count($items_list))
			$this->setParam('plugins_list', $items_list);
	}
	
	protected function getUserData()
	{
		if($user = \R::load('user', $this->id))
		{
			$user_data = $user->export();
			unset($user_data['password']);
			$user_data['userplugins'] = $this->getUserPlugins();
			$this->setParam('user_data', $user_data);
		}
		else
			$this->redirect('/users');
	}
	
	protected function getUserPlugins()
	{
		$userplugins = null;
		
		$userp = \R::getAll('SELECT plugin_id FROM user_access WHERE user_id = :user_id', array('user_id' => $this->id));
		
		if($userp && count($userp))
		{
			foreach($userp as $plugin_id)
				$userplugins[] = $plugin_id['plugin_id'];
		}
		
		return $userplugins;
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
		$required_fields = array('fname' => 'First Name', 'lname' => 'Last Name', 'email' => 'Email', 'role' => 'User role');
		$userplugins	 = null;

		if(!$this->id)
		{
			$required_fields['username'] = 'Username';
			$required_fields['password'] = 'Password';
		}
		
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
		
		
		$cpassword = (isset($_POST['cpassword']) && $_POST['cpassword'])?trim($_POST['cpassword']):null;
		
		if(!$this->id && $cpassword && $cpassword != $user_data['password'])
		{
			$this->setParam('form_error', 'Password and confirmation doesn\'t match!');
			return false;
		}
		
		//Check if needed to update existing user password
		if($this->id)
		{
			if(isset($_POST['password']) && $password = trim($_POST['password']))
			{
				if($cpassword && $cpassword != $password)
				{
					$this->setParam('form_error', 'Password and confirmation doesn\'t match!');
					return false;
				}	
				
				$user_data['password'] = $password;
			}
		}
		
		if($user_data['email'] && !VerifyEmailAdress($user_data['email']))
		{
			$this->setParam('form_error', 'Invalid email address!');
			return false;
		}
		
		if($user_data['role'] != 2 && isset($_POST['userplugins']) && count($_POST['userplugins']))
			$userplugins = $_POST['userplugins'];
		
		//Encrypt user password before storing
		if(isset($user_data['password']) && $user_data['password'])
			$user_data['password'] = \Crypt::generate_hash($user_data['password']);
		
		if($this->id) //Update existing user
		{
			
			$user = \R::load('user', $this->id);
			
			//If user email address was changed, check if that email alreay exists in bdd
			if($user->email != $user_data['email'] && \R::getCell('SELECT COUNT(id) FROM user WHERE email = :email', array('email' => $user_data['email'])))
			{
				$this->setParam('form_error', 'User with that email already exists!');
				return false;
			}
			
			if($user->id)
			{
				$user->import($user_data);
				\R::store($user);
			}
			else
			{
				$this->setParam('form_error', 'Invalid user id!');
				return false;
			}
		}
		else //Add a new user
		{
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
			$user_data['first_login'] = '0';	
			$user = \R::dispense('user');
			$user->import($user_data);
			$this->id = \R::store($user);
		}
		
		if($this->id && $user_data['role'] != 2)
			$this->setUserPlugins($userplugins);
		
		if(isset($_POST['inform_new_user']) && $_POST['inform_new_user'])
			$this->sendUserPassword($user_data);	
			
		$this->redirect('/users');
	}
	
	protected function setUserPlugins($userplugins)
	{
		\R::exec('DELETE FROM user_access WHERE user_id = :user_id', array('user_id' => $this->id));
		
		if($userplugins)
		{
			foreach($userplugins as $plugin_id)
				$query[] = '('.$this->id.','.$plugin_id.')';
				
			\R::exec('INSERT INTO user_access (user_id, plugin_id) VALUES '.join(', ', $query));
		}
	}
	
	protected function userLoginHistory()
	{
		$items_list = \R::getAll('SELECT success, INET_NTOA(ip) AS ipaddr, logtime FROM loginlog WHERE userid = :userid ORDER BY logtime DESC', array('userid' => $this->id));
		$this->setParam('user_login_history', $items_list);
	}
	
	protected function userActionsHistory()
	{
		$items_list = \R::getAll('SELECT * FROM logreq WHERE userid = :userid  ORDER BY reqtime DESC', array('userid' => $this->id));
		$this->setParam('user_action_history', $items_list);
	}
	
	protected function sendUserPassword($user_data)
	{
		$message = 'Hi '.$user_data['fname'].' '.$user_data['lname'].'<br> 
					Your account has been created. To access the account and modify your password, please click the link below.<br>
					<a href="https://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a><br><br>
					User Name: '.$user_data['username'].'<br> 
					password: '.$_POST['password'].'<br><br>
					For more information, please visit <a href="https://'.$_SERVER['HTTP_HOST'].'/">'.$_SERVER['HTTP_HOST'].'</a>.<br><br> 
					Enjoy using our service,<br>
					The Alvergeen Team';
					
		phpMailHTML(array('address'=> 'noreply@alvergeen.endsoft.ro','name'=> 'Alvergeen'), array('address' => $user_data['email'], 'name' => $user_data['fname'].' '.$user_data['lname']), 'Welcome to ROEL print order portal', $message);
	}
	
	function delete()
	{	
		if($this->id)
			\R::exec('DELETE FROM user WHERE id =:id AND id != :current_user_id', array('id' => $this->id, 'current_user_id' => $_SESSION['user_data']['id']));
		
		$this->redirect('/users');
	}
}
?>
