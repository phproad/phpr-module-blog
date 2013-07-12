<?php

class Blog_Module extends Core_Module_Base
{

	protected function set_module_info()
	{
		return new Core_Module_Detail(
			"Blog",
			"Blog Module",
			"PHPRoad",
			"http://phproad.com/"
		);
	}

	public function build_admin_menu($menu)
	{
		$top = $menu->add('blog', 'Blog', 'blog/posts', 300)->icon('microphone')->permission(array('notify_blog_comments', 'manage_posts_and_categories', 'manage_settings', 'manage_comments'));
		$top->add_child('posts', 'Posts', 'blog/posts', 100)->permission(array('manage_posts_and_categories', 'manage_comments'));
		$top->add_child('categories', 'Categories', 'blog/categories', 100)->permission(array('manage_posts_and_categories', 'manage_comments'));
	}
	
	public function build_admin_settings($settings)
	{
		$settings->add('/blog/setup', 'Blog Settings', 'Blog set up and configuration', '/modules/blog/assets/images/blog_config.png', 70);
	}

	public function build_admin_permissions($host)
	{
		$host->add_permission_field($this, 'notify_blog_comments', 'Notify user about new blog comments')->display_as(frm_checkbox)->comment('Check this checkbox if you want this user to be notified about new blog post comments.', 'above');
		$host->add_permission_field($this, 'manage_posts_and_categories', 'Manage posts and categories', 'left')->display_as(frm_checkbox)->comment('Create or update blog posts and categories.', 'above');
		$host->add_permission_field($this, 'manage_settings', 'Manage blog settings', 'right')->display_as(frm_checkbox)->comment('Manage commenting rules and notifications settings.', 'above');
		$host->add_permission_field($this, 'manage_comments', 'Manage comments')->display_as(frm_checkbox)->comment('Create or delete blog post comments.', 'above');
	}

}
