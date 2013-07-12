<?php

class Blog_Category_Menu extends Builder_Menu_Base
{
    public function get_info()
    {
        return array(
            'name'=>'Blog Category Link',
            'description'=>'Links to a Blog Category page'
        );
    }

    public function build_config_ui($host)
    {
        $host->add_field('blog_category_id', 'Blog Category', 'full', db_number, 'Link')
            ->comment('Please select the Blog Category to link to', 'above')
            ->display_as(frm_radio)
            ->validation()->required('Please select the Blog Category to link to');
    }

    public function validate_menu_item($host)
    {
        // Page has not changed, leave it alone
        if (isset($host->fetched['page_id']) && $host->page_id == $host->fetched['page_id'])
            return;

        $category = Blog_Category::create()->find($host->blog_category_id);
        
        if (!$category)
            throw new Phpr_ApplicationException('Blog category not found: '. $host->blog_category_id);

        $host->url = $category->url_name;

        // Navigation label has changed, leave it alone
        if (isset($host->fetched['label']) && $host->label != $host->fetched['label'])
            return;

        // Navigation label has been set manually, no touchy
        if (strlen($host->label))
            return;
            
        $host->label = $category->name;
    }

    public function get_blog_category_id_options($key_value= -1)
    {
        $categories = Blog_Category::create()->find_all()->as_array('name', 'id');
        return $categories;
    }

    public function get_blog_category_id_option_state($key_value= -1)
    {
        return false;
    }    

}