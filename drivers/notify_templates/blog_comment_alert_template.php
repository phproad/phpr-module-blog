<?php

class Blog_Comment_Alert_Template extends Notify_Template_Base
{
    public $required_params = array('post', 'comment', 'subscriber');

    public function get_info()
    {
        return array(
            'name'=> 'Blog Comment Alert',
            'description' => 'Sent to visitors who subscribe to a blog post comments.',
            'code' => 'blog:comment_alert'
        );
    }

    public function get_subject()
    {
        return 'New Blog Comment';
    }

    public function get_content()
    {
        return file_get_contents($this->get_partial_path('content.htm'));
    }

    public function prepare_template($template, $params=array())
    {
        extract($params);

        $user = $subscriber;

        $unsubscribe_url = $subscriber->get_unsubscribe_url($post, null, true);

        $post->set_notify_vars($template, 'post_');
        $comment->set_notify_vars($template, 'comment_');
        $subscriber->set_notify_vars($template, 'subscriber_');
        $template->set_vars(array(
            'unsubscribe_link' => '<a href="'.$unsubscribe_url.'">'.h($post->title).'</a>',
        ), false);

        $template->add_recipient($user);
    }
}
