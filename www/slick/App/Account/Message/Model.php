<?php
class Slick_App_Account_Message_Model extends Slick_Core_Model
{
	
	public function getMessageForm()
	{
		$form = new Slick_UI_Form;
		
		$username = new Slick_UI_Textbox('username');
		$username->addAttribute('required');
		$username->setLabel('Username');
		$form->add($username);
		
		$subject = new Slick_UI_Textbox('subject');
		$subject->setLabel('Subject');
		$form->add($subject);
		
		$message = new Slick_UI_Textarea('message', 'markdown');
		$message->addAttribute('required');
		$message->setLabel('Message');
		$form->add($message);
		
		$form->setSubmitText('Send');
		
		return $form;
	}
	
	
	public function sendMessage($data)
	{
		$useData = checkRequiredFields($data, array('userId' => true, 'message' => true, 'username' => true));
		
		$getUser = $this->get('users', trim($useData['username']), array('userId', 'username', 'slug'), 'username');
		if(!$getUser){
			//try by slug
			$getUser = $this->get('users', trim($useData['username']), array('userId', 'username', 'slug'), 'slug');
			if(!$getUser){
				throw new Exception('User not found: '.$useData['username']);
			}
		}
		
		$tca = new Slick_App_LTBcoin_TCA_Model;
		$profileModule = $tca->get('modules', 'user-profile', array(), 'slug');
		$checkTCA = $tca->checkItemAccess($useData['userId'], $profileModule['moduleId'], $getUser['userId'], 'user-profile');
		if(!$checkTCA){
			throw new Exception('You cannot send a message to this user');
		}
		
		$insertData = array('userId' => $useData['userId'], 'toUser' => $getUser['userId'], 'message' => encrypt_string(strip_tags($useData['message'])),
							'subject' => encrypt_string(strip_tags($useData['subject'])), 'sendDate' => timestamp());
		
		$add = $this->insert('private_messages', $insertData);
		if(!$add){
			throw new Exception('Error sending private message');
		}
		
		//notify user
		$subject = strip_tags(trim($useData['subject']));
		if(trim($subject) == ''){
			$subject = '(no subject)';
		}
		
		$notifyData = $this->appData;
		$notifyData['messageId'] = $add;
		$notifyData['subject'] = $subject;
		$notifyData['toUser'] = $getUser['userId'];
		Slick_App_Meta_Model::notifyUser($getUser['userId'], 'emails.newMessageNotice', $add, 'private-message', false, $notifyData);
		
		return $add;
		
	}
	
	public function getUserInbox($userId, $perPage = 50, $page = 1)
	{
		$start = 0;
		if($page > 1){
			$start = ($page * $perPage) - $perPage;
		}
		$profModel = new Slick_App_Profile_User_Model;
		$getMessages = $this->getAll('private_messages', array('toUser' => $userId), array(), 'sendDate', 'desc', $perPage, $start);
		foreach($getMessages as &$row){
			$row['subject'] = decrypt_string($row['subject']);
			$row['message'] = decrypt_string($row['message']);
			$row['from'] = $profModel->getUserProfile($row['userId'], $this->appData['site']['siteId']);
			$row['to'] = $profModel->getUserProfile($row['toUser'], $this->appData['site']['siteId']);
			$row['hasReplied'] = $this->checkMessageReplied($row['messageId']);
			
		}
		
		return $getMessages;
	}
	
	public function checkMessageReplied($messageId)
	{
		$get = $this->getAll('private_messages', array('replyId' => $messageId), array('messageId'));
		if($get AND count($get) > 0){
			return true;
		}
		return false;
	}
	
	public function getUserOutbox($userId, $perPage = 50, $page = 1)
	{
		$start = 0;
		if($page > 1){
			$start = ($page * $perPage) - $perPage;
		}
		$profModel = new Slick_App_Profile_User_Model;
		$getMessages = $this->getAll('private_messages', array('userId' => $userId), array(), 'sendDate', 'desc', $perPage, $start);
		foreach($getMessages as &$row){
			$row['subject'] = decrypt_string($row['subject']);
			$row['message'] = decrypt_string($row['message']);
			$row['from'] = $profModel->getUserProfile($row['userId'], $this->appData['site']['siteId']);
			$row['to'] = $profModel->getUserProfile($row['toUser'], $this->appData['site']['siteId']);
			$row['hasReplied'] = $this->checkMessageReplied($row['messageId']);
			
		}
		
		return $getMessages;
	}
	
	public function sendReply($data)
	{
		$useData = checkRequiredFields($data, array('userId' => true, 'message' => true, 'toUser' => true, 'replyId' => true));
		
		$getMessage = $this->get('private_messages', $data['replyId']);
		if(!$getMessage){
			throw new Exception('Message not found');
		}
		
		$getUser = $this->get('users', $useData['toUser'], array('userId'));
		if(!$getUser){
			throw new Exception('User not found');
		}
		
		$insertData = array('userId' => $useData['userId'], 'toUser' => $useData['toUser'], 'message' => encrypt_string(strip_tags($useData['message'])),
							'subject' => encrypt_string(strip_tags($useData['subject'])), 'sendDate' => timestamp(), 'replyId' => $useData['replyId']);
		
		$add = $this->insert('private_messages', $insertData);
		if(!$add){
			throw new Exception('Error sending private message');
		}
		
		//notify user
		$subject = strip_tags(trim($useData['subject']));
		if($subject == ''){
			$subject = '(no subject)';
		}
		
		$notifyData = $this->appData;
		$notifyData['messageId'] = $add;
		$notifyData['subject'] = $subject;
		$notifyData['toUser'] = $useData['toUser'];
		Slick_App_Meta_Model::notifyUser($getUser['userId'], 'emails.newMessageNotice', $add, 'private-message', false, $notifyData);
		
		return $add;
	}
	
	public function getNumUnreadMessages($userId)
	{
		$get = $this->getAll('private_messages', array('toUser' => $userId, 'isRead' => 0, 'userId' => array('op' => '!', 'value' => $userId)), array('messageId'));
		return count($get);
	}
	
	public function getReplyChain($replyId, $chain = array())
	{
		$get = $this->get('private_messages', $replyId);
		if($get){
			if($get['isRead'] == 0 AND $this->appData['user']['userId'] == $get['toUser']){
				$this->edit('private_messages', $get['messageId'], array('isRead' => 1));
			}			
			$chain[] = $this->parseMessage($get);
			if($get['replyId'] != 0){
				$chain = $this->getReplyChain($get['replyId'], $chain);
			}
		}
		
		return $chain;
	}
	
	public function parseMessage($getMessage)
	{
		$getMessage['subject'] = decrypt_string($getMessage['subject']);
		$getMessage['message'] = decrypt_string($getMessage['message']);
		if(trim($getMessage['subject']) == ''){
			$getMessage['subject'] = '(no subject)';
		}
		$profModel = new Slick_App_Profile_User_Model;
		$getMessage['from'] = $profModel->getUserProfile($getMessage['userId'], $this->appData['site']['siteId']);
		$getMessage['to'] = $profModel->getUserProfile($getMessage['toUser'], $this->appData['site']['siteId']);
		
		return $getMessage;		
	}
	
	public function getEndOfChain($messageId)
	{
		$getReply = $this->get('private_messages', $messageId, array('messageId'), 'replyId');
		if(!$getReply){
			return $messageId;
		}
		return $this->getEndOfChain($getReply['messageId']);
	}

}
