<?php

class Blog_Posts extends Admin_Controller
{
	public $implement = 'Db_List_Behavior, Db_Form_Behavior, Db_Filter_Behavior';
	public $list_model_class = 'Blog_Post';
	public $list_record_url = null;
	public $list_custom_body_cells;
	public $list_custom_head_cells;
	public $list_no_pagination;	
	public $list_cell_partial = false;
	public $list_options = array();
	public $list_custom_prepare_func = null;
	public $list_handle_row_click = false;
	public $list_columns;

	public $form_preview_title = 'Post';
	public $form_create_title = 'New Post';
	public $form_edit_title = 'Edit Post';
	public $form_model_class = 'Blog_Post';
	public $form_not_found_message = 'Post not found';
	public $form_redirect = null;
	public $form_create_save_redirect;
	public $form_edit_save_redirect;
	public $form_delete_redirect;
	public $form_flash_id = 'form-flash';
	
	public $form_edit_save_flash = 'The post has been successfully saved';
	public $form_create_save_flash = 'The post has been successfully added';
	public $form_edit_delete_flash = 'The post has been successfully deleted';
	public $form_edit_save_auto_timestamp = true;

	public $list_search_enabled = true;
	public $list_search_fields = array('@title', '@description');
	public $list_search_prompt = 'find posts by title or description';
	public $list_items_per_page = 20;

	public $filter_list_title = 'Filter posts';
	public $filter_on_apply = 'listReload();';
	public $filter_on_remove = 'listReload();';
	public $filter_filters = array(
		'category' => array(
			'name' => 'Category',
			'class_name' => 'Blog_Category_Filter',
			'prompt' => 'Please choose post categories you want to include to the list.',
			'added_list_title' => 'Added Categories'
		)
	);

	protected $required_permissions = array('blog:manage_posts_and_categories', 'blog:manage_comments');

	protected $global_handlers = array('on_save');

	public function __construct()
	{
		parent::__construct();
		$this->app_menu = 'blog';
		$this->app_module_name = 'Blog';

		if (Phpr::$router->action == 'edit')
		{
			$referer = Phpr::$router->param('param2');
			if ($referer != 'list')
				$this->form_edit_save_redirect = url('blog/posts/preview/%s').'?'.uniqid();
			else
				$this->form_edit_save_redirect = url('blog/posts/').'?'.uniqid();
		}

		$this->list_record_url = url('blog/posts/preview/');
		$this->form_redirect = url('blog/posts');
		$this->form_create_save_redirect = url('blog/posts/edit/%s/list').'?'.uniqid();
		$this->form_delete_redirect = url('blog/posts');
		$this->app_page = 'posts';
		
		if (post('comment_list_mode'))
		{
			$this->list_model_class = 'Blog_Comment';
			$this->list_columns = array('created_at', 'status', 'author_name', 'author_email', 'content');
			$this->list_search_enabled = false;
			
			$this->list_custom_prepare_func = 'prepare_comment_list';
			$this->list_record_url = null;
			$this->list_no_setup_link = true;
			$this->list_items_per_page = 10000;
			$this->list_no_data_message = 'This post is not commented';
			$this->list_record_url = url('blog/comments/preview/');
			$this->list_custom_body_cells = false;
			$this->list_custom_head_cells = false;
			$this->list_no_pagination = true;
			$this->list_cell_partial = PATH_APP.'/modules/blog/controllers/blog_posts/_comment_row_controls.htm';
		}
	}

	public function index()
	{
		$this->check_user_permissions();
		$this->app_page_title = 'Posts';
	}

	public function list_prepare_data()
	{
		$obj = new Blog_Post();
		$this->filter_apply_to_model($obj);
		return $obj;
	}

	public function list_get_row_class($model)
	{
		if ($model instanceof Blog_Comment)
		{
			$class = null;
			if ($model->is_owner_comment)
				$class = 'safe ';
			
			if ($model->status_code == Blog_Comment_Status::status_deleted)
				return $class.'deleted ';
			if ($model->status_code == Blog_Comment_Status::status_new)
				return $class.'new ';
				
			return $class;
		}
	}

	private function check_user_permissions()
	{
		$this->view_data['can_manage_posts'] = $this->active_user->get_permission('blog', 'manage_posts_and_categories');
		$this->view_data['can_manage_comments'] = $this->active_user->get_permission('blog', 'manage_comments');		
	}

	public function preview_form_before_display()
	{
		$this->check_user_permissions();
	}

	protected function eval_posts_statistics()
	{
		return Blog_Post::eval_posts_statistics();
	}

	protected function on_save($id)
	{		
		Phpr::$router->action == 'create' ? $this->create_on_save() : $this->edit_on_save($id);
	}
	
	public function prepare_comment_list()
	{
		$id = Phpr::$router->param('param1');
		return Blog_Comment::create()->where('post_id=?', $id);
	}
	
	protected function preview_on_set_comment_status($post_id)
	{
		try
		{
			$comment = $this->find_comment(post('id'));
			$comment->set_status(post('status'));
			$comment->save();
			
			$this->view_data['form_model'] = $this->form_find_model_object($post_id);
			$this->display_partial('comment_list');
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajax_report_exception($ex, true, true);
		}
	}
	
	protected function preview_on_delete_comment($post_id)
	{
		$this->set_comment_status($post_id, Blog_Comment_Status::status_deleted);
	}
	
	private function find_comment($id)
	{
		if (!strlen($id))
			throw new Phpr_ApplicationException('Comment not found');
			
		$obj = Blog_Comment::create()->where('id=?', $id)->find();
		if (!$obj)
			throw new Phpr_ApplicationException('Comment not found');
			
		return $obj;
	}
	
	public function edit_form_before_display()
	{
		if (!$this->active_user->get_permission('blog', 'manage_posts_and_categories'))
			Phpr::$response->redirect(url('/'));
	}
	
	public function create_form_before_display()
	{
		if (!$this->active_user->get_permission('blog', 'manage_posts_and_categories'))
			Phpr::$response->redirect(url('/'));
	}
	
	public function form_after_create_save($page, $session_key)
	{
		if (post('create_close'))
		{
			$this->form_create_save_redirect = url('blog/posts').'?'.uniqid();
		}
	}
	
	public function form_after_edit_save($model, $session_key)
	{
		$model = $this->view_data['form_model'] = Blog_Post::create()->find($model->id);
		$model->updated_user_name = $this->active_user->name;
		
		$this->display_partials(array(
			'form-flash'=>flash(),
			'object-summary'=>'@_post_summary'
		));
		
		return true;
	}
}

