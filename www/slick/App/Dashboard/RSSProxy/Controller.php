<?php
class Slick_App_Dashboard_RSSProxy_Controller extends Slick_App_ModControl
{
    public $data = array();
    public $args = array();
    
    function __construct()
    {
        parent::__construct();
        
        $this->model = new Slick_App_Dashboard_RSSProxy_Model;
        
        
    }
    
    public function init()
    {
		$output = parent::init();
        
        if(isset($this->args[2])){
			switch($this->args[2]){
				case 'view':
					$output = $this->showProxies();
					break;
				case 'add':
					$output = $this->addProxy();
					break;
				case 'edit':
					$output = $this->editProxy();
					break;
				case 'delete':
					$output = $this->deleteProxy();
					break;
				default:
					$output = $this->showProxies();
					break;
			}
		}
		else{
			$output = $this->showProxies();
		}
		$output['template'] = 'admin';
        
        return $output;
    }
    
    private function showProxies()
    {
		$output = array('view' => 'list');
		$output['proxyList'] = $this->model->getAll('proxy_url');

		
		return $output;
		
	}
	
	
	private function addProxy()
	{
		$output = array('view' => 'form');
		$output['form'] = $this->model->getProxyForm();
		$output['formType'] = 'Add';
		
		if(posted()){
			$data = $output['form']->grabData();
			try{
				$add = $this->model->addProxy($data);
			}
			catch(Exception $e){
				$output['error'] = $e->getMessage();
				$add = false;
			}
			
			if($add){
				$this->redirect($this->site.'/'.$this->moduleUrl);
				return true;
			}
			
		}
		
		return $output;
		
	}
	

	
	private function editProxy()
	{
		if(!isset($this->args[3])){
			$this->redirect('/');
			return false;
		}
		
		$getProxy = $this->model->get('proxy_url', $this->args[3]);
		if(!$getProxy){
			$this->redirect($this->site.'/'.$this->moduleUrl);
			return false;
		}
		
		$output = array('view' => 'form');
		$output['form'] = $this->model->getProxyForm($this->args[3]);
		$output['formType'] = 'Edit';
		
		if(posted()){
			$data = $output['form']->grabData();
			try{
				$add = $this->model->editProxy($this->args[3], $data);
			}
			catch(Exception $e){
				$output['error'] = $e->getMessage();
				$add = false;
			}
			
			if($add){
				$this->redirect($this->site.'/'.$this->moduleUrl);
				return true;
			}
			
		}
		$output['form']->setValues($getProxy);
		
		return $output;
		
	}
	

	
	
	private function deleteProxy()
	{
		if(!isset($this->args[3])){
			$this->redirect($this->site.'/'.$this->moduleUrl);
			return false;
		}
		
		
		$getProxy = $this->model->get('proxy_url', $this->args[3]);
		if(!$getProxy){
			$this->redirect($this->site.'/'.$this->moduleUrl);
			return false;
		}
		
		$delete = $this->model->delete('proxy_url', $this->args[3]);
		$this->redirect($this->site.'/'.$this->moduleUrl);
		return true;
	}
	


}

?>
