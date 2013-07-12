<?php

class Blog_New_Comment_Template extends Notify_Template_Base
{
    public $required_params = array('comment');

    public function get_info()
    {
        return array(
            'name'=> 'New Blog Comment',
            'description' => 'Sent to visitors who subscribe to a blog post comments.',
            'code' => 'blog:new_comment'
        );
    }

    public function get_internal_subject()
    {
        return "New Comment in Blog";
    }

    public function get_internal_content()
    {
        return file_get_contents($this->get_partial_path('internal_content.htm'));
    }

    public function prepare_template($template, $params=array())
    {
        extract($params);

        $post = Blog_Post::create()->find($comment->post_id);
        if (!$post)
            return;

        $post->set_notify_vars($template, 'post_');
        $comment->set_notify_vars($template, 'comment_');

        $template->set_vars(array(
            'admin_approve_link' => Phpr::$request->get_root_url().url('blog/posts/preview/'.$post->id.'?'.uniqid()),
        ), false);

        // Add recipients
        $config_obj = Blog_Config::create();
        if (!$comment->is_owner_comment && $config_obj->comment_notifications_rule != Blog_Config::notify_nobody)
        {
            if ($config_obj->comment_notifications_rule == Blog_Config::notify_authors)
            {
                $user = Admin_User::create()->find($comment->created_user_id);
                if ($user && $user->get_permission('blog', 'notify_blog_comments'))
                    $template->add_recipient($user, true);
            } 
            else if ($config_obj->comment_notifications_rule == Blog_Config::notify_all)
            {
                $users = Admin_User::list_users_having_permission('blog', 'notify_blog_comments');
                $template->add_recipients($users, true);
            }
        }
    }
}
