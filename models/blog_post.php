<?php

class Blog_Post extends Db_ActiveRecord
{
	public $table_name = 'blog_posts';
	
	public $implement = 'Db_AutoFootprints';
	public $auto_footprints_visible = false;
	public $auto_footprints_default_invisible = true;

	public $has_and_belongs_to_many = array(
		'categories'=>array('class_name'=>'Blog_Category', 'join_table'=>'blog_posts_categories', 'order'=>'name'),
	);

	public $has_many = array(
		'comments' => array('class_name'=>'Blog_Comment', 'foreign_key'=>'post_id', 'order'=>'blog_comments.created_at desc', 'conditions'=>"blog_comments.status_id != (select id from blog_comment_statuses where code = 'deleted')"),
		'approved_comments' => array('class_name'=>'Blog_Comment', 'foreign_key'=>'post_id', 'conditions'=>"blog_comments.status_id = (select id from blog_comment_statuses where code='approved')", 'order'=>"blog_comments.created_at asc"),
		'featured_images' => array('class_name'=>'Db_File', 'foreign_key'=>'master_object_id', 'conditions'=>"master_object_class='Blog_Post' and field='featured_images'", 'order'=>'sort_order, id', 'delete'=>true),
		'attachments' => array('class_name'=>'Db_File', 'foreign_key'=>'master_object_id', 'conditions'=>"master_object_class='Blog_Post' and field='attachments'", 'order'=>'sort_order, id', 'delete'=>true),
	);
	
	public $calculated_columns = array(
		'new_comment_num' => array('sql'=>'(select count(*) from blog_comments, blog_comment_statuses where blog_comments.post_id = blog_posts.id and blog_comments.status_id=blog_comment_statuses.id and blog_comment_statuses.code=\'new\')', 'type'=>db_number),
		'comment_num' => array('sql'=>'(select count(*) from blog_comments where blog_comments.post_id = blog_posts.id)', 'type'=>db_number),
		'approved_comment_num' => array('sql'=>'(select count(*) from blog_comments, blog_comment_statuses where blog_comments.post_id = blog_posts.id and blog_comments.status_id=blog_comment_statuses.id and blog_comment_statuses.code=\'approved\')', 'type'=>db_number),
		'email_subscribers' => array('sql'=>'(select count(*) from blog_comment_subscribers where post_id=blog_posts.id)', 'type'=>db_number),
		'author_first_name' => array('sql'=>'author_users.first_name ', 'join'=>array('admin_users as author_users'=>'author_users.id = blog_posts.created_user_id'), 'type'=>db_text),
		'author_last_name' => array('sql'=>'author_users.last_name', 'type'=>db_text)
	);
	
	protected $api_added_columns = array();

	public function define_columns($context = null)
	{
		$this->define_column('title', 'Title')->order('asc')->validation()->fn('trim')->required("Please specify a title for this post");
		$this->define_column('url_title', 'URL Title')->default_invisible()->validation()->fn('trim')->fn('mb_strtolower')->regexp('/^[0-9a-z_-]*$/i', 'URL Title should contain only latin characters, numbers, underscores and the minus sign')->required('Please specify the URL Title')->unique('The URL Title "%s" already in use, please try another URL Title');
		$this->define_multi_relation_column('categories', 'categories', 'Categories', '@name')->default_invisible()->validation()->required('Post must have a category assigned');
		$this->define_column('description', 'Excerpt')->validation()->fn('trim');
		$this->define_column('content', 'Content')->invisible()->validation()->fn('trim')->required('Please provide the post content');
		$this->define_column('keywords', 'Keywords')->validation()->fn('trim');
		$this->define_column('is_published', 'Published');
		$this->define_column('comments_allowed', 'Allow Comments')->default_invisible();
		$this->define_column('published_at', 'Date Published');
		$this->define_column('comment_num', 'Total Comments')->default_invisible();
		$this->define_column('new_comment_num', 'New Comments')->default_invisible();
		$this->define_column('email_subscribers', 'Email Subscribers')->default_invisible();
		$this->define_multi_relation_column('featured_images', 'featured_images', 'Featured Images', '@name')->default_invisible();
		$this->define_multi_relation_column('attachments', 'attachments', 'Attachments', '@name')->default_invisible();
		
		// Extensibility
		$this->defined_column_list = array();
		Phpr::$events->fire_event('blog:on_extend_post_model', $this, $context);
		$this->api_added_columns = array_keys($this->defined_column_list);
	}
	
	public function define_form_fields($context = null)
	{
		if ($context != 'preview')
		{
			$this->add_form_field('title', 'left')->comment('The post title will be shown in the post lists and on the post page.', 'above')->collapsible();
			$this->add_form_field('url_title', 'right')->comment('Post URL title, to reference the post in URLs, for example: my_first_post', 'above')->collapsible();
			$this->add_form_field('is_published', 'left')->collapsible();
			$this->add_form_field('published_at', 'right')->collapsible();
			$this->add_form_field('comments_allowed','left')->collapsible();

			$content_field = $this->add_form_field('content')->display_as(frm_html)->size('huge');
			$content_field->html_plugins .= ',save,fullscreen,inlinepopups';
			$content_field->html_buttons1 = 'save,separator,'.$content_field->html_buttons1.',separator,fullscreen';
			$content_field->save_callback('save_code');
			$content_field->html_full_width = true;

			$this->add_form_field('keywords')->display_as(frm_tags, array('available_tags'=>array('hello', 'world')));
			$this->add_form_field('description')->display_as(frm_textarea)->size('small');
			$this->add_form_field('categories')->collapsible();

			$this->add_form_field('featured_images', 'left')->display_as(frm_file_attachments)
				->display_files_as('image_list')
				->add_document_label('Add image(s)')
				->no_attachments_label('There are no images uploaded')
				->image_thumb_size(170)
				->file_download_base_url(url('admin/files/get/'));

			$this->add_form_field('attachments', 'right')->display_as(frm_file_attachments)
				->display_files_as('file_list')
				->add_document_label('Add file(s)')
				->no_attachments_label('There are no files uploaded')
				->file_download_base_url(url('admin/files/get/'));
		} 
		else 
		{
			$this->add_form_field('title');
			$this->add_form_field('created_user_name', 'left');
			$this->add_form_field('url_title', 'right');
			$this->add_form_field('description');
			$this->add_form_field('is_published', 'left');
			$this->add_form_field('comments_allowed', 'right');
			$this->add_form_field('categories');
			$this->add_form_field('published_at', 'left');
		}
		
		// Extensibility
		Phpr::$events->fire_event('blog:on_extend_post_form', $this, $context);
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
		$result = Phpr::$events->fire_event('blog:on_get_post_field_options', $db_name, $current_key_value);
		foreach ($result as $options)
		{
			if (is_array($options) || (strlen($options && $current_key_value != -1)))
				return $options;
		}
		
		return false;
	}
	
	// Events
	// 

	public function before_save($session_key = null)
	{
		if ($this->is_published && !$this->published_at)
			$this->published_at = Phpr_DateTime::now();
	}
	
	// Filters
	// 

	public function apply_visibility()
	{
		$this->where('is_published is not null and is_published=1');
		return $this;
	}

	public function apply_categories($category_ids, $negative = false)
	{
		if ($negative)
			$filter = 'not exists';
		else
			$filter = 'exists';

		$this->where('('.$filter.' (select * from blog_categories, blog_posts_categories 
			where blog_categories.id=blog_posts_categories.blog_category_id 
			and blog_posts_categories.blog_post_id=blog_posts.id 
			and blog_categories.id in (?)
		))', array($category_ids));

		return $this;
	}

	public function apply_filters()
	{
		return $this->apply_visibility();
	}

	public function order_by_date($direction = 'desc')
	{
		$this->order('blog_posts.created_at desc');
		return $this;
	}

	// Service methods
	// 

	public function set_notify_vars(&$template, $prefix='')
	{
		$post_url = $this->get_url(null, true);

		$template->set_vars(array(
			$prefix.'name_and_url' => '<a href="'.$post_url.'">'.h($this->title).'</a>',
			$prefix.'url' => '<a href="'.$post_url.'">'.h($this->title).'</a>',
			$prefix.'link' => '<a href="'.$post_url.'">'.h($this->title).'</a>',
		), false);

		$template->set_vars(array(
			$prefix.'title' => $this->title,
			$prefix.'name' => $this->title,
			$prefix.'excerpt' => $this->description,
			$prefix.'content' => $this->content,
		));
	}
	
	public function get_url($page=null, $add_hostname=false)
	{
		if (!$page) 
			$page = Cms_Page::get_url_from_action('blog:post');

		return root_url($page.'/'.$this->url_title, $add_hostname);
	}

	public static function get_rss($feed_name, $feed_description, $feed_url, $post_url, $category_url, $blog_url, $post_number = 20, $exclude_category_ids = array())
	{
		$posts = Blog_Post::create();
		$posts->apply_visibility()->order_by_date();
		
		if ($exclude_category_ids)
			$posts->apply_categories($exclude_category_ids, true);
		
		$posts = $posts->limit($post_number)->find_all();
		
		$rss = new Core_Rss($feed_name, $blog_url, $feed_description, $feed_url);
		foreach ($posts as $post)
		{
			$link = $post_url.$post->url_title;

			$category_links = array();
			foreach ($post->categories as $category)
			{
				$cat_url = $category_url.$category->url_name;
				$category_links[] = '<a href="'.$cat_url.'">'.h($category->name).'</a>';
			}

			$category_str = '<p>Posted in: '.implode(', ', $category_links).'</p>';

			$rss->add_entry($post->title,
				$link,
				$post->id,
				$post->published_at,
				strlen($post->description) ? '<p>'.$post->description.'</p>'.$category_str : $post->content.$category_str,
				$post->published_at,
				$post->created_user_name,
				$post->content.$category_str);
		}

		return $rss->to_xml();
	}
	
	public static function get_comments_rss($feed_name, $feed_description, $feed_url, $post_url, $category_url, $blog_url, $comment_number = 20, $exclude_category_ids = array())
	{
		$status = Blog_Comment_Status::create()->find_by_code(Blog_Comment_Status::status_approved);
		$comments = Blog_Comment::create()->where('status_id=?', $status->id)->order_by_date()->limit($comment_number);

		if ($exclude_category_ids)
			$posts->apply_categories($exclude_category_ids, true);

		if ($exclude_category_ids)
			$comments->apply_categories($exclude_category_ids, true);
		
		$comments = $comments->find_all();
		
		$rss = new Core_Rss($feed_name, $blog_url, $feed_description, $feed_url);
		foreach ($comments as $comment) {
			$link = $post_url . $comment->display_field('post_url') . '#comment'.$comment->id;

			$rss->add_entry(
				$comment->display_field('post'),
				$link,
				'comment_'.$comment->id,
				$comment->created_at,
				'<p>Comment by '.h($comment->author_name).': <blockquote>'.$comment->content_html.'</blockquote>',
				$comment->created_at,
				$comment->author_name,
				'<p>Comment by '.h($comment->author_name).': <blockquote>'.$comment->content_html.'</blockquote>'
			);
		}

		return $rss->to_xml();
	}
	
	public static function list_recent_posts($number = 10)
	{
		$posts = Blog_Post::create();
		$posts->apply_filters()->order_by_date();
		$posts->limit($number);
		return $posts->find_all();
	}

	// Getters
	//

	public function get_excerpt($word_limit = 100)
	{
		$excerpt = ($this->description) ? $this->description : $this->content;
		$excerpt = Phpr_String::limit_words($excerpt, $word_limit);
		$excerpt = Phpr_Html::plain_text($excerpt);
		return $excerpt;
	}

	// Custom columns
	// 

	public static function eval_posts_statistics()
	{
		return Db_Helper::object("select
			(select count(*) from blog_posts) as post_num,
			(select count(*) from blog_posts where published_at is not null) as published_num"
		);
	}
}

