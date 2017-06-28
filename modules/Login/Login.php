<?php
/*
	Login module pERP Backend
*/
namespace Login;
use \PERP\Controller;

class Login extends \PERP\Controller
{
	function __construct()
	{
		controller::__construct();
		$this->setParam('page_title',get_class($this));
		$this->module = strtolower(get_class($this));
	}
	
	function get()
	{
		if($this->is_logged)
			$this->redirect('/dashboard');
	}
	
	function post()
	{
		$user_data = \R::getRow('SELECT * FROM user WHERE username = :username', array('username' => $_POST['username']));
		
		if($user_data && count($user_data))
		{	
			$log_data['username'] = $user_data['username'];
			$log_data['userid']   = $user_data['id'];
			
			if(\Crypt::validate($_POST['password'], $user_data['password']))
			{
				unset($user_data['password']);
				
				if((isset($user_data['first_login']) && $user_data['first_login']))
					\R::exec('UPDATE user SET first_login = 0 WHERE id = :id', array('id' => $user_data['id']));
				
				$_SESSION['user_data'] = $user_data;
				
				if($user_data['role'] != 2)
				{
					$allowed_plugins = \R::getCell('SELECT GROUP_CONCAT(plugin_id SEPARATOR \', \') FROM user_access WHERE user_id = :user_id', array('user_id' => $user_data['id']));
					if($allowed_plugins)
						$allowed_plugins = explode(',', $allowed_plugins);
					
					$_SESSION['user_data']['allowed_plugins'] = $allowed_plugins;
				}
				
				$log_data['success'] = true;
				
				$this->_log($log_data);
				
				$this->cleanRedirect();
				
				$this->redirect((isset($user_data['first_login']) && $user_data['first_login'])?'/profile':'/dashboard');
			}
			
			$log_data['success'] = false;
			$this->_log($log_data);
		}
		
		$this->setParam('form_error','Invlid username or password!');
	}
	
	protected function _log($data)
	{
		$data['ip'] = ip2long($_SERVER['REMOTE_ADDR']);
		$data['logtime'] = date('Y-m-d H:i:s');
		
		$log = \R::dispense('loginlog');
		$log->import($data);
		\R::store($log);
	}
	
	protected function cleanRedirect()
	{
		$redir_path = (isset($_GET['redir']) && $_GET['redir'])?parse_url($_GET['redir'], PHP_URL_PATH):null;
		
		if($redir_path)
			$this->redirect(str_ireplace(array('http','https','://','.'), null,$redir_path));
		
		return;
	}
	
	function __call($method,$arguments) 
	{
      if(preg_match("/get|head|post|put|patch|delete|connect|options/",strtolower($method)))
        $arguments[0]->error(405,strtoupper($method)." is not a valid method for the pERP method");
    	return false;
	}

}
?>