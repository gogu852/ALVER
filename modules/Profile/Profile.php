<?php
/*
	User profile module pERP Backend
*/
namespace Profile;
use \PERP\Controller as Controller;

class Profile extends Controller
{
	var $id = null;
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged)
			$this->redirect('/');
		
		$this->id = $this->f3->get('SESSION.user_data.id');
		
		if(!$this->id)
			$this->redirect('/');
		
		$this->setParam('page_title','My profile');
		$this->module = strtolower(get_class($this));
		$this->languages = require(HELPERS_DIR.'languages_array.php');
		$this->countries = require(HELPERS_DIR.'countries_array.php');
		$this->departments = require(HELPERS_DIR.'departments_array.php');
		$this->avatars 	 = InDirFiles(ABSPATH.'backend/img/avatars/','png');
	}
	
	function get($load_data = true)
	{
		if($load_data)
		{
			$account_data = $profile_data = array();
			
			$account = \R::load('user', $this->id);
			
			if($account->id)
			{
				$account_data = $account->export();
				unset($account_data['password']);
			}
			
			if($profile = \R::findOne('profile', 'userid = :userid', array('userid' => $this->id)));
			{
				if($profile && $profile->id)
					$profile_data = $profile->export();
			}
			
			if(!isset($account_data['userlang']) || !$account_data['userlang'])
				$account_data['userlang'] = 'en';
			
			$this->setParam('user_data',array_merge($account_data, $profile_data));
		}
			
		$this->setParam('avatars', $this->avatars);
		$this->setParam('languages_array', $this->languages);
		$this->setParam('countries_array', $this->countries);
		$this->setParam('departments_array', $this->departments);
	}
	
	function post()
	{
		if(!$this->setProfile())
		{
			$this->setParam('user_data', (isset($_POST['account']) && isset($_POST['profile']))?array_merge($_POST['account'], $_POST['profile']):$_POST);
			return $this->get(false);
		}
		
		$this->redirect('/profile');
	}
	
	function setProfile()
	{
		$account_fields = array('fname' => 'First Name', 'lname' => 'Name', 'email' => 'Email');
		
		$profile_fields = array('mname' => 'Middle Name', 
								'companyemail' => 'Company email', 
								'phone' 	   => 'Phone', 
								'jobposition'  => 'Position', 
								'jobdepartment'=> 'Department',
								'www' 		   => 'Personal website',
								'workingstart' => 'Job starting hour',
								'workingend'   => 'Job ending hour',
								'emailsignature' => 'Email signature', 
								'street' 	   => 'Street', 
								'number' 	   => 'Number', 
								'suite' 	   => 'Suite', 
								'city' 		   => 'City', 
								'country' 	   => 'Country', 
								'userdob' 	   => 'Date of birth', 
								'skypeid' 	   => 'Skype ID', 
								'yahooid' 	   => 'Yahoo ID', 
								'facebook' 	   => 'Facebook', 
								'linkedin' 	   => 'LinkedIn', 
								'twitter' 	   => 'Twitter', 
								'hobby' 	   => 'Hobby');
								
		
		
		foreach($account_fields as $field => $fdesc)
		{
			if(isset($_POST['account'][$field]) && $$field = trim($_POST['account'][$field]))
				$account_data[$field] = $$field;
			else
			{
				$this->setParam('form_error', $fdesc.' is missing!');
				return false;
			}
		}
		
		if($account_data['email'] && !VerifyEmailAdress($account_data['email']))
		{
			$this->setParam('form_error', 'Invalid email address!');
			return false;
		}
		
		foreach($profile_fields as $field => $fdesc)
			$profile_data[$field] = (isset($_POST['profile'][$field]) && $_POST['profile'][$field])?trim($_POST['profile'][$field]):null;
		
		
		
		if($profile_data['companyemail'] && !VerifyEmailAdress($profile_data['companyemail']))
		{
			$this->setParam('form_error', 'Invalid Company email address!');
			return false;
		}
		
		if($profile_data['country'] && !array_key_exists($profile_data['country'], $this->countries))
		{
			$this->setParam('form_error', 'Invalid country!');
			return false;
		}
		
		if(isset($_POST['password']) && $password = trim($_POST['password']))
			$account_data['password'] = \Crypt::generate_hash($password);
		
		if(isset($_POST['userlang']) && array_key_exists($_POST['userlang'], $this->languages) && $_POST['userlang'] != 'en')
			$account_data['userlang'] = $_POST['userlang'];
		
		$new_avatar = false;
		
		if(isset($_FILES['ownavatar']['name']) && $_FILES['ownavatar']['name'] && file_exists($_FILES['ownavatar']['tmp_name']))
		{
			list($width, $height, $type) = getimagesize($_FILES['ownavatar']['tmp_name']);
			
			if($type == 2 || $type == 3)
			{
				$extension = ($type == 2)?'jpg':'png';
				
				$target_file = MEDIA_DIR.$this->id.'.'.$extension;
				
				@rename($_FILES['ownavatar']['tmp_name'], $target_file);
				chmod($target_file, 0644);
				$account_data['custom_avatar'] = true;
				$account_data['avatar']		   = $this->id.'.'.$extension;
				$new_avatar = true;
			}
		}
		
		if(!isset($account_data['custom_avatar']))
		{
			if(isset($_POST['account']['avatar']) && !in_array($_POST['account']['avatar'], $this->avatars))
				$account_data['custom_avatar'] = true;
			else
				$account_data['custom_avatar'] = false;
		}
		
		
		if(!$new_avatar)
		{
			if(!$account_data['custom_avatar'] && isset($_POST['account']['avatar']) && in_array($_POST['account']['avatar'], $this->avatars))
			{
				$account_data['avatar'] = $_POST['account']['avatar'];
				$account_data['custom_avatar'] = false;
			}
			elseif(isset($_POST['account']['avatar']) && !in_array($_POST['account']['avatar'], $this->avatars))
			{
				$account_data['avatar'] = $_POST['account']['avatar'];
				$account_data['custom_avatar'] = true;
			}
			else
			{
				$account_data['avatar'] = 'default.png';
				$account_data['custom_avatar'] = false;
			}
		}
		
		
		$account = \R::load('user', $this->id);
		$account->import($account_data);
		\R::store($account);
		
		$_SESSION['user_data']['custom_avatar'] = $account_data['custom_avatar'];
		$_SESSION['user_data']['avatar'] = $account_data['avatar']; 
		$_SESSION['user_data']['fname'] = $account_data['fname'];
		$_SESSION['user_data']['lname'] = $account_data['lname']; 
		
		if($profile_id = \R::getCell('SELECT id FROM profile WHERE userid = :userid', array('userid' => $this->id)))
			$profile = \R::load('profile', $profile_id);
			
		
		if(!isset($profile) || !$profile->id)
		{
			$profile_data['userid'] = $this->id;
			$profile = \R::dispense('profile');
		}
		
		$profile->import($profile_data);
		\R::store($profile);
		
		return true;
	}
}
?>