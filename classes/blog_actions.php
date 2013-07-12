<?php

class Blog_Actions extends Cms_Action_Base
{
	public function archive()
	{
		$posts = Blog_Post::create();
		$posts->where('is_published is not null and is_published=1');
		$posts->order('blog_posts.published_at desc');
		
		$this->data['posts'] = $posts;
	}
	
	public function category()
	{
		$this->data['category'] = null;

		$url_name = $this->request_param(0);
		if (!strlen($url_name))
			return;

		$category = Blog_Category::create()->find_by_url_name($url_name);
		if (!$category)
			return;

		$this->page->title_name = $category->name;
		$this->data['category'] = $category;
		$this->data['posts'] = $category->posts_list;
	}
	
	public function post()
	{
		$this->data['post'] = null;
		$this->data['comment_success'] = false;
		
		$url_title = $this->request_param(0);
		if (!strlen($url_title))
			return;

		$post = Blog_Post::create()->find_by_url_title($url_title);
		if (!$post)
			return;

		$this->page->title_name = $post->title;
		$this->data['post'] = $post;
		
		if (post('send_comment_action'))
			$this->on_create_comment();
	}
	
	public function on_create_comment()
	{
		$this->data['comment_success'] = false;

		$url_title = $this->request_param(0);
		if (!strlen($url_title))
			return;

		$post = Blog_Post::create()->find_by_url_title($url_title);
		if (!$post)
			return;

		$comment = Blog_Comment::create();
		$comment->init_columns('front_end');
		$comment->validation->focus_prefix = null;
		$comment->validation->get_rule('content')->focus_id('comment_content');
		$comment->post_id = $post->id;
		$comment->save($_POST);
		
		$this->data['comment_success'] = true;
		
		$redirect = post('redirect');
		if ($redirect)
			Phpr::$response->redirect($redirect);
	}
	
	public function rss()
	{
	}
	
	public function delete_subscriber_comment()
	{
		$post_id = $this->request_param(0);
		if (!strlen($post_id))
			return;

		$email_hash = $this->request_param(1);
		if (!strlen($email_hash))
			return;
			
		Blog_Comment_Subscriber::unsubscribe($post_id, $email_hash);
	}
}
