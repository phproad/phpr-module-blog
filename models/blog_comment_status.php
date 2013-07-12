<?php

class Blog_Comment_Status extends Db_ActiveRecord
{
	const status_new = 'new';
	const status_approved = 'approved';
	const status_deleted = 'deleted';
	
	public $table_name = 'blog_comment_statuses';
	
    public static function find_id_from_code($code)
    {
        $status = self::create()->find_by_code($code);
        return ($status) ? $status->id : null;
    }	
}

