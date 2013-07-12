<?php

class Blog_Comment extends Db_ActiveRecord
{
	public $table_name = 'blog_comments';

	public $belongs_to = array(
		'status'=>array('class_name'=>'Blog_Comment_Status', 'foreign_key'=>'status_id'),
		'post'=>array('class_name'=>'Blog_Post', 'foreign_key'=>'post_id'),
	);
	
	public $calculated_columns = array(
		'status_code'=>array('sql'=>'status_calculated_join.code', 'type'=>db_varchar)
	);
	
	public $custom_columns = array(
		'subscribe_to_notifications'=>db_bool
	);

	protected $api_added_columns = array();

	public function define_columns($context = null)
	{
		$config_obj = Blog_Config::create();
		
		$this->define_column('created_at', 'Added')->order('desc');
		$this->define_relation_column('status', 'status', 'Status', db_varchar, '@name');
		$this->define_relation_column('post', 'post', 'Post', db_varchar, '@title');
		$this->define_relation_column('post_url', 'post', 'Post URL', db_varchar, '@url_title');
		
		$field = $this->define_column('author_name', 'Author Name')->list_title('Author')->validation()->fn('trim');
		
		if ($config_obj->comment_name_required)
			$field->required("Please enter your name.");
		
		$field = $this->define_column('author_email', 'Email Address')->list_title('Email')->validation()->fn('trim')->fn('mb_strtolower')->email('Please specify a valid email address.');
		
		if ($config_obj->comment_email_required)
			$field->required("Please specify your email address.");

		$this->define_column('author_url', 'Website Address')->validation()->fn('trim');
		$this->define_column('content', 'Comment')->validation()->fn('trim')->required("Please enter a comment.");
		$this->define_column('author_ip', 'Poster IP Address');
		
		// Extensibility
		$this->defined_column_list = array();
		Phpr::$events->fire_event('blog:on_extend_comment_model', $this, $context);
		$this->api_added_columns = array_keys($this->defined_column_list);
	}
	
	public function define_form_fields($context = null)
	{
		if ($context != 'preview')
		{
			$this->add_form_field('status');
		} 
		else
		{
			$this->add_form_field('status', 'left')->preview_no_relation();
			$this->add_form_field('author_ip', 'right');
		}
		
		$this->add_form_field('author_name', 'left');
		$this->add_form_field('author_email', 'right');
		$this->add_form_field('author_url');
		$this->add_form_field('content');

		// Extensibility
		Phpr::$events->fire_event('blog:on_extend_comment_form', $this, $context);
		foreach ($this->api_added_columns as $column_name)
		{
			$form_field = $this->find_form_field($column_name);
			if ($form_field)
				$form_field->options_method('get_added_field_options');
		}
	}
	
	// Extensibility
	// 
	
	public function get_added_field_options($db_name, $current_key_value = -1)
	{
		$result = Phpr::$events->fire_event('blog:on_get_comment_field_options', $db_name, $current_key_value);
		foreach ($result as $options)
		{
			if (is_array($options) || (strlen($options && $current_key_value != -1)))
				return $options;
		}
		
		return false;
	}
	
	// Events
	// 
	
	public function before_create($session_key = null)
	{
		$this->author_ip = Phpr::$request->get_user_ip();
		$this->throttle_post();

	}
	
	public function after_create()
	{		
		Notify::trigger('blog:new_comment', array('comment'=>$this));

		// Subscribe user to blog post
        if ($this->subscribe_to_notifications)
        {
            $obj = Blog_Comment_Subscriber::create();

            if (!$obj->is_subscribed($this->post_id, $this->author_email))
            {
                $obj->email = $this->author_email;
                $obj->post_id = $this->post_id;
                $obj->subscriber_name = $this->author_name;
                $obj->save();
            }
        }		
	}
	
	public function before_save($session_key = null)
	{
		if (strlen($this->content))
			$this->content_html = Phpr_Html::paragraphize($this->content);
	}
	
	public function after_save()
	{
		if ($this->status->code == Blog_Comment_Status::status_approved && (!isset($this->fetched['status_id']) || $this->status_id != $this->fetched['status_id']))
		{
			Blog_Comment_Subscriber::send_notifications($this->post, $this);
		}
	}

    // Filters
    // 

	public function apply_categories($category_ids, $negative = false)
	{
		if ($negative)
			$filter = 'not exists';
		else
			$filter = 'exists';

		$this->where('('.$filter.' (select * from blog_posts, blog_categories, blog_posts_categories 
			where blog_posts.id=blog_comments.post_id 
			and blog_categories.id=blog_posts_categories.blog_category_id 
			and blog_posts_categories.blog_post_id=blog_posts.id 
			and blog_categories.id in (?)
		))', array($category_ids));

		return $this;
	}

	// Service methods
	// 

    public function set_notify_vars(&$template, $prefix='')
    {
        $template->set_vars(array(
            $prefix.'author_name' => $this->author_name,
            $prefix.'author_email' => $this->author_email,
            $prefix.'author_url' => $this->author_url,
            $prefix.'author_ip' => $this->author_ip,
            $prefix.'content' => $this->content,
            $prefix.'content_html' => $this->content_html,
            $prefix.'text' => $this->content,
        ));
    }

	public function throttle_post()
	{
		if (!$this->is_owner_comment)
		{
			$this->set_status(Blog_Comment_Status::status_new);
			$config_obj = Blog_Config::create();
			if ($config_obj->comment_interval)
			{
				$current_time = Phpr_DateTime::now();
				
				$bind = array('ip'=>$this->author_ip, 'current_time'=>$current_time, 'time_interval'=>$config_obj->comment_interval);
				$post_allowed = Db_Helper::scalar("select ifnull(DATE_ADD((select max(created_at) from blog_comments where author_ip=:ip), interval :time_interval minute) <= :current_time, 1)", $bind);
				
				if (!$post_allowed)
				{
					throw new Phpr_ApplicationException("Please allow {$config_obj->comment_interval} minutes between posts");
				}
			}
		}		
	}

	public function set_status($status_code)
	{
		$this->status_id = Blog_Comment_Status::find_id_from_code($status_code);
	}
	
	public static function get_recent_comments($number = 5)
	{
		$obj = self::create();
		$obj->order('created_at desc');
		$obj->limit($number);

		return $obj->find_all();
	}
	
	public function get_url_formatted()
	{
		if (!preg_match(',^(http://)|(https://),', $this->author_url))
			return 'http://'.$this->author_url;
			
		return $this->author_url;
	}
}

