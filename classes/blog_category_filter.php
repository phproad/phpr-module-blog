<?php

class Blog_Category_Filter extends Db_Data_Filter
{
	public $model_class_name = 'Blog_Category';
	public $list_columns = array('name');

	public function apply_to_model($model, $keys, $context = null)
	{
		$model->where('(
			select count(*) 
			from 
				blog_posts_categories 
			where 
				blog_category_id in (?) 
				and blog_post_id = blog_posts.id
		) > 0', array($keys));
	}
}
