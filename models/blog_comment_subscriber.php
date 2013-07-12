<?php

class Blog_Comment_Subscriber extends Db_ActiveRecord
{
	public $table_name = 'blog_comment_subscribers';

	public function is_subscribed($post_id, $email)
	{
		return Db_Helper::scalar(
			"select count(*) from blog_comment_subscribers where post_id=:post_id and email=:email",
			array(
				'post_id'=>$post_id,
				'email'=>$email
			)
		);
	}

	// Events
	// 
	
	public function before_save($deferred_session_key = null)
	{
		$this->email_hash = md5($this->email);
	}

	// Service methods
	// 

    public function set_notify_vars(&$template, $prefix='')
    {
        $template->set_vars(array(
            $prefix.'email' => $this->email,
            $prefix.'name' => $this->subscriber_name,
        ));
    }

	public static function send_notifications($post, $comment)
	{
		$subscribers = self::create()->where('post_id=?', $post->id)->find_all();
		foreach ($subscribers as $subscriber)
		{
			if ($subscriber->email == $comment->author_email)
				continue;

			Notify::trigger('blog:comment_alert', array(
				'post'=>$post, 
				'comment'=>$comment, 
				'subscriber'=>$subscriber
			));
		}
	}
	
	public static function unsubscribe($post_id, $email_hash)
	{
		$obj = self::create()->where('post_id=?', $post_id);
		$obj = $obj->where('email_hash=?', $email_hash)->find();
		if ($obj)
			$obj->delete();
	}

    public function get_unsubscribe_url($post, $page=null, $add_hostname=false)
    {
		if (!$page) 
			$page = Cms_Page::get_url_from_action('blog:delete_subscriber_comment');

        return root_url($page.'/'.$post->id.'/'.$this->email_hash, $add_hostname);
    }	
}

