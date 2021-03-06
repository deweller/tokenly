<?php
class Slick_App_API_V1_Profile_Controller extends Slick_Core_Controller
{
	public $methods = array('POST', 'GET');
	
	function __construct()
	{
		parent::__construct();
		$this->model = new Slick_App_API_V1_Profile_Model;
		
	}
	
	public function init($args = array())
	{
		$this->args = $args;
		$output = array();
		
		if(isset($this->args[1])){
			switch($this->args[1]){
				case 'user':
					$output = $this->getUser();
					break;
				case 'update':
					$output = $this->updateProfile();
					break;
				case 'get-users':
					$output = $this->getAllUsers();
					break;
				case 'get-fields':
					$output = $this->profileFields();
					break;
				default:
					http_response_code(400);
					$output['error'] = 'Invalid request';
					break;
			}
			
		}
		else{
			http_response_code(400);
			$output['error'] = 'Invalid request';
		}
		
		return $output;
	}
	
	private function getUser()
	{
		$output = array();
		
		if(!isset($this->args[2])){
			http_response_code(400);
			$output['error'] = 'Invalid request';
			return $output;
		}
		
		$model = new Slick_App_Profile_User_Model;
		$getUser = $model->get('users', $this->args[2], array('userId'), 'slug');
		if(!$getUser){
			http_response_code(400);
			$output['error'] = 'User not found';
			return $output;
		}
		
		$profile = $model->getUserProfile($getUser['userId'], $this->args['data']['site']['siteId']);
		unset($profile['userId']);
		if($profile['showEmail'] == 0){
			unset($profile['email']);
		}
		unset($profile['showEmail']);
		unset($profile['pubProf']);
		unset($profile['lastActive']);
		unset($profile['lastAuth']);
		
		
		$output['profile'] = $profile;
		
		return $output;
	}
	
	private function updateProfile()
	{
		$output = array();
		
		if($this->useMethod != 'POST'){
			http_response_code(400);
			$output['error'] = 'Invalid request method';
			$output['methods'] = array('POST');
			return $output;
		}

		try{
			$user = Slick_App_API_V1_Auth_Model::getUser($this->args['data']);
		}
		catch(Exception $e){
			http_response_code(403);
			$output['error'] = $e->getMessage();
			return $output;
		}
		
		$data = $this->args['data'];
		$data['user'] = $user;
		
		try{
			$update = $this->model->updateProfile($data);
		}
		catch(Exception $e){
			http_response_code(400);
			$output['error'] = $e->getMessage();
			return $output;
		}
		
		$output['result'] = 'success';
		
		return $output;
	}
	
	private function profileFields()
	{
		$output = array();

		try{
			$user = Slick_App_API_V1_Auth_Model::getUser($this->args['data']);
		}
		catch(Exception $e){
			http_response_code(403);
			$output['error'] = $e->getMessage();
			return $output;
		}
				
		$output['fields'] = $this->model->getProfileFields($user, $this->args['data']['site']['siteId']);
		
		return $output;
	}
	
	private function getAllUsers()
	{
		$output = array();
		
		$profModel = new Slick_App_Profile_User_Model;
		$max = 20;
		$page = 1;
		if(isset($this->args['data']['page'])){
			$page = intval($this->args['data']['page']);
			if($page <= 0){
				$page = 1;
			}
		}
		if(isset($this->args['data']['limit'])){
			$max = intval($this->args['data']['limit']);
		}
		
		$start = ($page * $max) - $max;
		
		$totalUsers = $this->model->count('users');
		
		$users = $this->model->fetchAll('SELECT userId, username, email, regDate, lastActive, slug
										FROM users
										ORDER BY userId DESC
										LIMIT '.$start.', '.$max);
										
		try{
			$thisUser = Slick_App_API_V1_Auth_Model::getUser($this->args['data']);
		}
		catch(Exception $e){
			$thisUser = false;
		}		
		
		$tca = new Slick_App_LTBcoin_TCA_Model;
		$profileModule = $tca->get('modules', 'user-profile', array(), 'slug');			
										
		foreach($users as $key => $user){
			$profile = $profModel->getUserProfile($user['userId'], $this->args['data']['site']['siteId']);
			if($profile['pubProf'] == 0){
				unset($users[$key]);
				$totalUsers--;
				continue;
			}
			if($profile['showEmail'] == 0){
				unset($profile['email']);
				unset($user['email']);
			}
			$user['avatar'] = $profile['avatar'];
			$userTCA = $tca->checkItemAccess($thisUser, $profileModule['moduleId'], $user['userId'], 'user-profile');
			if(!$userTCA){
				$user['profile'] = array();
			}
			else{
				$profile = $profile['profile'];
				unset($profile['regDate']);
				unset($profile['userId']);
				unset($profile['lastAuth']);
				unset($profile['lastActive']);
				$user['profile'] = $profile;
			}
			unset($user['lastAuth']);
			unset($user['lastActive']);
			unset($user['userId']);
			$users[$key] = $user;
			
		}
		$output['numPages'] = ceil($totalUsers / $max);
		
		$output['users'] = $users;
		
		return $output;
	}
	
}

?>
