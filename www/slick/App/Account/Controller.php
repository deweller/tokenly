<?php
class Slick_App_Account_Controller extends Slick_App_AppControl
{
    function __construct()
    {
        parent::__construct();
        
        
    }
    
    public function init()
    {
		$output = parent::init();
		
		return $output;
    }
    
	public function __install($appId)
	{
		$update = parent::__install($appId);
		if(!$update){
			return false;
		}
		
		$meta = new Slick_App_Meta_Model;
		$meta->updateAppMeta($appId, 'avatarWidth', 150, 'Avatar Width (px)', 1);
		$meta->updateAppMeta($appId, 'avatarHeight', 150, 'Avatar Height (px)', 1);
		$meta->updateAppMeta($appId, 'disableRegister', 0, 'Disable New User Registration', 1, 'bool');
	}
    
}
