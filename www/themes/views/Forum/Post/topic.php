<?php
$thisURL = SITE_URL.'/'.$app['url'].'/'.$module['url'].'/'.$topic['url'];


function checkUserTCA($userId, $profUserId)
{
	$tca = new Slick_App_LTBcoin_TCA_Model;
	$module = $tca->get('modules', 'user-profile', array(), 'slug');
	if(!$userId OR ($userId AND $userId != $profUserId)){
		$checkTCA = $tca->checkItemAccess($userId, $module['moduleId'], $profUserId, 'user-profile');
		if(!$checkTCA){
			return false;
		}
	}
	return true;
}

?>
<?php
if($user AND $topic['locked'] == 0){
?>
<p style="float: right; vertical-align: top; margin-top: 10px; width: 120px; text-align: center;">
	<?php if($perms['canPostReply']){ ?><a class="board-control-link" href="#post-reply">Post Reply</a><?php }//endif ?>
	<?php
	$subscribeText = 'Subscribe';
	$subscribeClass = 'subscribe';
	$model = new Slick_Core_Model;
	$getSubs = $model->getAll('forum_subscriptions',
				array('userId' => $user['userId'], 'topicId' => $topic['topicId']));
	if(count($getSubs) > 0){
		$subscribeClass = 'unsubscribe';
		$subscribeText = 'Unsubscribe';
	}
	echo '<br><a href="#" class="board-control-link '.$subscribeClass.'">'.$subscribeText.'</a>';	
	?>
</p>
<?php
    }//endif
?>
<h1><?= $topic['title'] ?></h1>

<p>
	<a href="<?= SITE_URL ?>/<?= $app['url'] ?>/board/<?= $board['slug'] ?>" class="board-back-link">Back to <?= $board['name'] ?></a>
</p>
<div class="topic-paging paging">
	<?php
	if($numPages > 1){
		echo '<strong>Pages:</strong> ';
		for($i = 1; $i <= $numPages; $i++){
			$active = '';
			if((isset($_GET['page']) AND $_GET['page'] == $i) OR (!isset($_GET['page']) AND $i == 1)){
				$active = 'active';
			}
			echo '<a href="'.SITE_URL.'/'.$app['url'].'/'.$module['url'].'/'.$topic['url'].'?page='.$i.'" class="'.$active.'">'.$i.'</a>';
		}
	}
	?>
</div>
<h2 class="topic-heading">Comments</h2>
<?php
if($page == 1){
	
?>
<div class="thread-op">
	<?php
	$userId = 0;
	if($user){
		$userId = $user['userId'];
	}
	$checkUserTCA = checkUserTCA($userId, $topic['userId']);
	
	$avImage = $topic['author']['avatar'];
	if(!isExternalLink($topic['author']['avatar'])){
		$avImage = SITE_URL.'/files/avatars/'.$topic['author']['avatar'];
	}
	$avImage = '<img src="'.$avImage.'" alt="" />';
	if($checkUserTCA){
		$avImage = '<a href="'.SITE_URL.'/profile/user/'.$topic['author']['slug'].'">'.$avImage.'</a>';	
	}
	
	$topicUsername = $topic['author']['username'];
	if($checkUserTCA){
		$topicUsername = '<a href="'.SITE_URL.'/profile/user/'.$topic['author']['slug'].'" target="_blank">'.$topicUsername.'</a>';
	}	
	?>
	<div class="op-author">
		<span class="post-username"><?= $topicUsername ?></span>
		<div class="profile-pic">
			<?= $avImage ?>
		</div>
		<div class="post-author-info">
			Posts: <?= Slick_App_Account_Home_Model::getUserPostCount($topic['userId']) ?>
			<?php
			if(isset($topic['author']['profile']['location'])){
				echo '<br>Location: '.$topic['author']['profile']['location']['value'];
			}
			
			
			if($user AND $user['userId'] != $topic['userId']){
				if($checkUserTCA){
					echo '<br><a href="'.SITE_URL.'/account/messages/send?user='.$topic['author']['slug'].'" target="_blank" class="send-msg-btn" title="Send private message">Message</a>';
				}
			}
			?>
			
		</div>
	</div>
	<div class="op-content">
		<div class="post-content" data-user-slug="<?= $topic['author']['slug'] ?>" data-message="<?= base64_encode($topic['content']) ?>">
			<?= markdown($topic['content']) ?>
		</div>
			<?php
			if(isset($topic['author']['profile']['forum-signature']['value'])){
				echo "		<div class=\"forum-sig\">\n";
				echo markdown($topic['author']['profile']['forum-signature']['value']);
				echo "		</div>\n";
			}
			?>
	</div>
	<div class="clear"></div>
	<span class="post-date">Posted on <?= formatDate($topic['postTime']) ?>
	<?php
	if($topic['editTime'] != null){
		echo '<br>Last Edited: '.formatDate($topic['editTime']);
	}
	?>
	</span>
	<?php
	if($user AND $perms['canReportPost'] AND $topic['userId'] != $user['userId']){
		echo '<div class="report-link">';
		if(isset($topic['isReported']) AND $topic['isReported']){
			echo '<em>Reported</em>'; 
		}
		else{
			echo '<a class="report-post" data-id="'.$topic['topicId'].'" data-type="topic" href="#">Flag/Report</a>';
		}
	
		echo '</div>';
	}
	if($user AND $perms['canRequestBan']){
		echo '<div class="report-link"><a class="request-ban" data-id="'.$topic['userId'].'" href="#">Request Ban</a></div>';
	}	
	
	$likeList = array();
	foreach($topic['likeUsers'] as $likeUser){
		$likeList[] = str_replace('"', '', $likeUser['username']);
	}
	$likeList = join(', ', $likeList);
	
	if($user){
		$model = new Slick_Core_Model;
		$hasLiked = $model->getAll('user_likes', array('userId' => $user['userId'], 'itemId' => $topic['topicId'], 'type' => 'topic'));
		$unlike = '';
		$likeText = 'Like';
		if(isset($hasLiked[0])){
			$unlike = 'unlike';
			$likeText = 'Unlike';
		}
		
		echo '	<div class="post-controls">
					<span class="post-action" style="float: right;">
					<a href="#" class="like-post '.$unlike.'" data-id="'.$topic['topicId'].'" title="'.$likeList.'" data-type="topic">'.$likeText.' <span>(<em>'.$topic['likes'].'</em> '.pluralize('like', $topic['likes'], true).')</span></a>';
					
		if($perms['canPostReply'] AND $topic['locked'] == 0){
			echo ' <a href="#post-reply" class="quote-post">Quote</a>';
		}
		echo '</span>';
		if(($user['userId'] == $topic['userId'] AND $perms['canEditSelf']) OR ($user['userId'] != $topic['userId'] AND $perms['canEditOther'])){
			echo '<a href="'.$thisURL.'/edit">Edit</a>';
		}
		if(($user['userId'] == $topic['userId'] AND $perms['canLockSelf']) OR ($user['userId'] != $topic['userId'] AND $perms['canLockOther'])){
			$lockUrl = 'lock';
			$lockLabel = 'Lock';
			if($topic['locked'] != 0){
				$lockUrl = 'unlock';
				$lockLabel = 'Unlock';
			}
			echo '<a href="'.$thisURL.'/'.$lockUrl.'">'.$lockLabel.'</a>';
		}
		if(($user['userId'] == $topic['userId'] AND $perms['canStickySelf']) OR ($user['userId'] != $topic['userId'] AND $perms['canStickyOther'])){
			$stickyUrl = 'sticky';
			$stickyLabel = 'Sticky';
			if($topic['sticky'] != 0){
				$stickyUrl = 'unsticky';
				$stickyLabel = 'Un-sticky';
			}
			echo '<a href="'.$thisURL.'/'.$stickyUrl.'">'.$stickyLabel.'</a>';
		}
		if(($user['userId'] == $topic['userId'] AND $perms['canMoveSelf']) OR ($user['userId'] != $topic['userId'] AND $perms['canMoveOther'])){
			echo '<a href="'.$thisURL.'/move">Move</a>';
		}
		if($perms['canPermaDeleteTopic']){
			echo '<a href="'.$thisURL.'/permadelete" class="delete">Permadelete</a>';
		}
		if(($user['userId'] == $topic['userId'] AND $perms['canDeleteSelfTopic']) OR ($user['userId'] != $topic['userId'] AND $perms['canDeleteOtherTopic'])){
			echo '<a href="'.$thisURL.'/delete" class="delete" style="float: right; margin-right: 60px;">Bury Thread</a>';
		}
		
		if($user){
			//old subscribe spot
		}

		echo '<div class="clear"></div></div>';
		
	}//endif
	else{
		echo '<div class="post-controls">
					<span class="post-action" style="float: right;"><em title="'.$likeList.'">'.$topic['likes'].' '.pluralize('like', $topic['likes'], true).'</em></span>
				<div class="clear"></div>
			  </div>';
	}
	?>
</div>

<?php
}//endif
?>

<?php
if(count($replies) == 0){
	echo '<p>No replies yet</p>';
}
?>
<ul class="reply-list">
	<?php
	foreach($replies as $reply){
		$postClass = '';
		if($reply['buried'] != 0){
			$postClass = 'buried';
		}
		echo '<li class="'.$postClass.'"><a name="post-'.$reply['postId'].'" class="anchor"></a>';
		?>
		<div class="reply-author">
			<?php
			if($reply['buried'] != 0){
				echo '<div class="post-buried post-username">[deleted]</div>';
			}
			else{
				$userId = 0;
				if($user){
					$userId = $user['userId'];
				}
				$checkUserTCA = checkUserTCA($userId, $reply['userId']);
				
				$avImage = $reply['author']['avatar'];
				if(!isExternalLink($reply['author']['avatar'])){
					$avImage = SITE_URL.'/files/avatars/'.$reply['author']['avatar'];
				}
				$avImage = '<img src="'.$avImage.'" alt="" />';
				if($checkUserTCA){
					$avImage = '<a href="'.SITE_URL.'/profile/user/'.$reply['author']['slug'].'">'.$avImage.'</a>';	
				}
				
				$replyUsername = $reply['author']['username'];
				if($checkUserTCA){
					$replyUsername = '<a href="'.SITE_URL.'/profile/user/'.$reply['author']['slug'].'" target="_blank">'.$replyUsername.'</a>';
				}
			?>
			<span class="post-username"><?= $replyUsername ?></span>
			<div class="profile-pic">
				<?php

				echo $avImage;
				
				?>
			</div>
			
			<div class="post-author-info">
				Posts: <?= Slick_App_Account_Home_Model::getUserPostCount($reply['userId']) ?>
				<?php
				if(isset($reply['author']['profile']['location'])){
					echo '<br>Location: '.$reply['author']['profile']['location']['value'];
				}
				if($user AND $user['userId'] != $reply['userId']){
					if($checkUserTCA){					
						echo '<br><a href="'.SITE_URL.'/account/messages/send?user='.$reply['author']['slug'].'" target="_blank" class="send-msg-btn" title="Send private message">Message</a>';
					}
				}				
				?>
			</div>
			<?php
			}//endif
			?>
		</div>
		<div class="reply-content">
			<div class="post-content" <?php if($reply['buried'] == 0){ ?>data-user-slug="<?= $reply['author']['slug'] ?>" data-message="<?= base64_encode($reply['content']) ?>" <?php }//endif ?>>
				<?= markdown($reply['content']) ?>
			</div>
				<?php
				if($reply['buried'] != 1 AND isset($reply['author']['profile']['forum-signature']['value'])){
					echo "		<div class=\"forum-sig\">\n";
					echo markdown($reply['author']['profile']['forum-signature']['value']);
					echo "		</div>\n";
				}
				?>
		</div>
		<div class="clear"></div>
		<span class="post-date">Posted on <?= formatDate($reply['postTime']) ?>
		<?php
		if($reply['editTime'] != null){
			echo '<br>Last Edited: '.formatDate($reply['editTime']);
		}
		?>
		</span>
		<?php
		if($user AND $perms['canReportPost'] AND $reply['userId'] != $user['userId']){
			echo '<div class="report-link">';
			if(isset($reply['isReported']) AND $reply['isReported']){
				echo '<em>Reported</em>'; 
			}
			else{
				echo '<a class="report-post" data-id="'.$reply['postId'].'" data-type="post" href="#">Flag/Report</a>';
			}
			echo '</div>';
		}	
		if($user AND $perms['canRequestBan']){
			echo '<div class="report-link"><a class="request-ban" data-id="'.$reply['userId'].'" href="#">Request Ban</a></div>';
		}			
		$permaPage = '';
		$returnPage = '';
		if(isset($_GET['page']) AND intval($_GET['page']) > 1){
			$permaPage = '?page='.intval($_GET['page']);
			$returnPage = '?retpage='.intval($_GET['page']);
		}
		?>
		<span class="post-permalink"><a href="<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?><?= $permaPage ?>#post-<?= $reply['postId'] ?>">Permalink</a></span>
		<?php
			$likeList = array();
			foreach($reply['likeUsers'] as $likeUser){
				$likeList[] = str_replace('"', '', $likeUser['username']);
			}
			$likeList = join(', ', $likeList);
			
			if($user AND $reply['buried'] != 1){
				$hasLiked = $model->getAll('user_likes', array('userId' => $user['userId'], 'itemId' => $reply['postId'], 'type' => 'post'));
				$unlike = '';
				$likeText = 'Like';
				if(isset($hasLiked[0])){
					$unlike = 'unlike';
					$likeText = 'Unlike';
				}
				echo '	<div class="post-controls">
					<span class="post-action" style="float: right;">
				<a href="#" class="like-post '.$unlike.'" data-id="'.$reply['postId'].'" title="'.$likeList.'" data-type="reply">'.$likeText.' <span>(<em>'.$reply['likes'].'</em> '.pluralize('like', $reply['likes'], true).')</span></a>';
							
				if($perms['canPostReply'] AND $topic['locked'] == 0){
					echo ' <a href="#post-reply" class="quote-post">Quote</a>';
				}
				echo '</span>';
				if(($user['userId'] == $reply['userId'] AND $perms['canEditSelf']) OR ($user['userId'] != $reply['userId'] AND $perms['canEditOther'])){
					echo '<a href="'.$thisURL.'/edit/'.$reply['postId'].$returnPage.'">Edit Post</a>';
				}
				if(($user['userId'] == $reply['userId'] AND $perms['canBurySelf']) OR ($user['userId'] != $reply['userId'] AND $perms['canBuryOther'])){
					echo '<a href="'.$thisURL.'/delete/'.$reply['postId'].$returnPage.'" class="delete">Bury Post</a>';
				}
				if($perms['canPermaDeletePost']){
					echo '<a href="'.$thisURL.'/permadelete/'.$reply['postId'].$returnPage.'" class="delete">Permadelete</a>';
				}
				echo '<div class="clear"></div></div>';
			
		}
		elseif($reply['buried'] != 1){
			echo '<div class="post-controls">
						<span class="post-action" style="float: right;"><em title="'.$likeList.'">'.$reply['likes'].' '.pluralize('like', $reply['likes'], true).'</em></span>
					<div class="clear"></div>
				  </div>';
		}
				


		?>
		<?php
		echo '</li>';
	}
	?>
</ul>
<div class="topic-paging paging">
	<?php
	if($numPages > 1){
		echo '<strong>Pages:</strong> ';
		for($i = 1; $i <= $numPages; $i++){
			$active = '';
			if((isset($_GET['page']) AND $_GET['page'] == $i) OR (!isset($_GET['page']) AND $i == 1)){
				$active = 'active';
			}
			echo '<a href="'.SITE_URL.'/'.$app['url'].'/'.$module['url'].'/'.$topic['url'].'?page='.$i.'" class="'.$active.'">'.$i.'</a>';
		}
	}
	?>
</div>
<?php
if(!$user OR $perms['canPostReply']){
?>
	<a name="post-reply"></a>
	<?php
	if($user){
			echo '<p style="float: right; vertical-align: top; margin-top: 10px; width: 120px; text-align: center;">';
			echo '<a href="#" class="board-control-link '.$subscribeClass.'">'.$subscribeText.'</a>';	
			echo '</p>';	
	}
	?>
	<h2>Post Reply</h2>
	<div class="reply-form">
	<?php
	if($user){

		if($topic['locked'] != 0){
			$model = new Slick_Core_Model;
			$getLockedUser = $model->get('users', $topic['lockedBy'], array('username', 'slug'));
			$lockedUser = '';
			if($getLockedUser){
				$lockedUser = 'by <a href="'.SITE_URL.'/profile/user/'.$getLockedUser['slug'].'">'.$getLockedUser['username'].'</a>';
				
			}
			echo '<p><em>This thread was locked on '.formatDate($topic['lockTime']).' '.$lockedUser.' </em></p>';
		}
		elseif(isset($form)){
			echo $form->display();
			
			echo '<div class="markdown-preview">
						<h4>Live Preview</h4>
						<div class="markdown-preview-cont">
						
						</div>
					</div>';
					
			echo '<p><em>Use <strong>markdown</strong> formatting for post. See <a href="#" class="markdown-trigger" target="_blank">formatting guide</a>
						for more information.</em></p>
					<div style="display: none;" id="markdown-guide">
					'.$this->displayBlock('markdown-guide').'
					</div>
					
					';
		}
	}
	else{
	?>
		<p>
		Please <a href="<?= SITE_URL ?>/account?r=/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>">Login</a>
		to post a reply to this thread.
		</p>
	<?php
	}
	echo '</div>';
}
?>

<script type="text/javascript" src="<?= THEME_URL ?>/js/Markdown.Converter.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$('.quote-post').click(function(e){
			var message = $(this).parent().parent().parent().find('.post-content').data('message');
			message = Base64.decode(message);
			var user = $(this).parent().parent().parent().find('.post-content').data('user-slug');
			var newMessage = '> @' + user + "\n";
			var splitMessage = message.split("\n");
			$.each(splitMessage, function(k, v){
				newMessage = newMessage + '> ' + v + "\n";
			});
			
			var curVal = $('.reply-form').find('#markdown').val();
			if(curVal.trim() != ''){
				newMessage = curVal + "\n\n" +  newMessage;
			}
			
			$('.reply-form').find('#markdown').val(newMessage);
		
			
		});
		
		$('#markdown').on('input', function(e){
			var thisVal = $(this).val();
			var converter = new Markdown.Converter();
			
			getMarkdown = converter.makeHtml(thisVal);
			$('.markdown-preview-cont').html(getMarkdown);
		});
		
		$('.like-post').click(function(e){
			e.preventDefault();
			var id = $(this).data('id');
			var type = $(this).data('type');
			var numLikes = parseInt($(this).find('span').find('em').html());
			
			var action = 'like';
			if($(this).hasClass('unlike')){
				action = 'unlike';
			}
			
			if(type == 'topic'){
				var url = '<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>/' + action;
			}
			if(type == 'reply'){
				var url = '<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>/' + action + '/' + id;
			}
			
			var thisLink = $(this);
			console.log(url);
			
			$.get(url, function(data){
				console.log(data);
				if(typeof data.error != 'undefined'){
					console.log(data.error);
					return false;
				}
				else{
					if(action == 'like'){
						thisLink.addClass('unlike');
						numLikes++;
						var likeText = 'like';
						if(numLikes == 0 || numLikes > 1){
							likeText = 'likes';
						}
						thisLink.html('Unlike <span>(<em>' + numLikes + '</em> ' + likeText + ')</span>');
					}
					else{
						thisLink.removeClass('unlike');
						numLikes--;
						var likeText = 'like';
						if(numLikes == 0 || numLikes > 1){
							likeText = 'likes';
						}
						thisLink.html('Like <span>(<em>' + numLikes + '</em> ' + likeText + ')</span>');						
					}
				}
			});
		});
		
		$('.content').delegate('.subscribe', 'click', function(e){
			e.preventDefault();
			var thisLink = $('.subscribe');
			var url = '<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>/subscribe';
			$.post(url, function(data){
				if(typeof data.error != 'undefined'){
					alert(data.error);
					return false;
				}
				else{
					thisLink.html('Unsubscribe');
					thisLink.addClass('unsubscribe');
					thisLink.removeClass('subscribe');
					
				}
			});
			
		});
		
		$('.content').delegate('.unsubscribe', 'click', function(e){
			e.preventDefault();
			var thisLink = $('.unsubscribe');
			var url = '<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>/unsubscribe';
			$.post(url, function(data){
				if(typeof data.error != 'undefined'){
					alert(data.error);
					return false;
				}
				else{
					thisLink.html('Subscribe');
					thisLink.addClass('subscribe');
					thisLink.removeClass('unsubscribe');
				}
			});
			
		});
		
		$('.report-post').click(function(e){
			e.preventDefault();
			var check = confirm('Are you sure you want to report this post as spam/inappropriate? (a moderator will be notified)');
			if(!check || check == null){
				return false;
			}
			
			var url = '<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>/report';
			var thisId = $(this).data('id');
			var thisType = $(this).data('type');
			var thisLink = $(this);
			$.post(url, {type: thisType, itemId: thisId}, function(data){
				//console.log(data);
				if(typeof data.error != 'undefined'){
					console.log(data.error);
					return false;
				}
				else{
					$(thisLink).parent().html('<em>Reported</em>');
				}
			});
		});
		
		<?php
		if($user AND $perms['canRequestBan']){
		?>
		$('.request-ban').click(function(e){
			e.preventDefault();
			var reason = prompt('Please enter a reason for the ban request. An Admin will investigate');
			if(!prompt || prompt == null){
				return false;
			}
			
			var thisId = $(this).data('id');
			var thisLink = $(this);
			var url = '<?= SITE_URL ?>/<?= $app['url'] ?>/<?= $module['url'] ?>/<?= $topic['url'] ?>/request-ban/' + thisId;
			$.post(url, {message: reason}, function(data){
				console.log(data);
				if(typeof data.error != 'undefined'){
					console.log(data.error);
					return false;
				}
				else{
					$(thisLink).parent().html('<em>Request Sent</em>');
				}
			});
			
		});
		
		<?php
		}//endif
		?>
		
	});
</script>
