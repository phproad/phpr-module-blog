<?php

class Blog_Setup extends Admin_Controller
{
	public $implement = 'Db_Form_Behavior';

	public $form_edit_title = 'Blog Settings';
	public $form_model_class = 'Blog_Config';
	public $form_redirect = null;

	protected $required_permissions = array('blog:manage_settings');

	public function __construct()
	{
		parent::__construct();
		$this->app_menu = 'blog';
		$this->app_module_name = 'Blog';

		$this->app_page = 'settings';
	}
	
	public function index()
	{
		try
		{
			$this->app_page_title = 'Settings';
		
			$obj = new Blog_Config();
			$this->view_data['form_model'] = $obj->load();
		}
		catch (exception $ex)
		{
			$this->_controller->handle_page_error($ex);
		}
	}
	
	protected function index_on_save()
	{
		try
		{
            $settings = Blog_Config::create();
            $settings->save(post($this->form_model_class, array()), $this->form_get_edit_session_key());
            Phpr::$session->flash['success'] = 'Blog configuration has been successfully saved.';
            Phpr::$response->redirect(url('admin/settings/'));
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajax_report_exception($ex, true, true);
		}
	}
}

