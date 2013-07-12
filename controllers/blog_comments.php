<?php

class Blog_Comments extends Admin_Controller
{
	public $implement = 'Db_Form_Behavior';

	public $form_preview_title = 'Comment';
	public $form_create_title = 'New Comment';
	public $form_edit_title = 'Edit Comment';
	public $form_model_class = 'Blog_Comment';
	public $form_not_found_message = 'Comment not found';
	public $form_redirect = null;
	public $form_create_save_redirect = null;
	
	public $form_edit_save_flash = 'Comment has been successfully saved';
	public $form_create_save_flash = 'Comment has been successfully added';
	public $form_edit_delete_flash = 'Comment has been successfully deleted';
	public $form_edit_save_auto_timestamp = true;

	protected $required_permissions = array('blog:manage_posts_and_categories', 'blog:manage_comments');

	public function __construct()
	{
		parent::__construct();
		$this->app_menu = 'blog';
		$this->app_module_name = 'Blog';
		$this->app_page = 'posts';
		$this->form_redirect = url('blog/comments/preview/%s');
		
		if (post('create_mode'))
		{
			$this->form_create_save_redirect = url('blog/posts/preview/'.Phpr::$router->param('param1')).'#comments';
		}
	}
	
	private function check_user_permissions()
	{
		$this->view_data['can_manage_comments'] = $this->active_user->get_permission('blog', 'manage_comments');		
	}

	public function index()
	{
		$this->check_user_permissions();
	}

	public function preview_form_before_display()
	{
		$this->check_user_permissions();
	}

	protected function preview_on_set_comment_status($id)
	{
		try
		{
			$comment = $this->form_find_model_object($id);
			$comment->set_status(post('status'));
			$comment->save();

			Phpr::$response->redirect(url('blog/posts/preview/'.$comment->post_id.'?'.uniqid()).'#comment_'.$comment->id);
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajax_report_exception($ex, true, true);
		}
	}
	
	public function create_form_before_display($model)
	{
		if (!$this->active_user->get_permission('blog', 'manage_comments'))
			Phpr::$response->redirect(url('/'));
		
		$post_id = Phpr::$router->param('param1');
		if (!strlen($post_id))
			throw new Phpr_ApplicationException('Post not found');
			
		$post = Blog_Post::create()->find($post_id);
		if (!$post)
			throw new Phpr_ApplicationException('Post not found');
		
		$model->set_status(Blog_Comment_Status::status_approved);
		$model->author_name = $this->active_user->first_name.' '.$this->active_user->last_name;
	}
	
	public function edit_form_before_display()
	{
		if (!$this->active_user->get_permission('blog', 'manage_comments'))
			Phpr::$response->redirect(url('/'));
	}
	
	public function form_before_create_save($model)
	{
		$model->is_owner_comment = 1;
		$model->post_id = Phpr::$router->param('param1');
	}
}

