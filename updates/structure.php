<?php

$table = Db_Structure::table('blog_posts');
    $table->primary_key('id');
    $table->column('title', db_varchar);
    $table->column('url_title', db_varchar)->index();
    $table->column('description', db_text);
    $table->column('content', db_text);
    $table->column('keywords', db_text);
    $table->column('published_at', db_date);
    $table->column('is_published', db_bool);
    $table->column('category_id', db_number)->index();
    $table->column('comments_allowed', db_bool);
    $table->footprints();

$table = Db_Structure::table('blog_categories');
    $table->primary_key('id');
    $table->column('name', db_varchar);
    $table->column('url_name', db_varchar)->index();
    $table->column('code', db_varchar, 50);
    $table->column('description', db_text);
    $table->footprints();

$table = Db_Structure::table('blog_comments');
    $table->primary_key('id');
    $table->column('author_name', db_varchar);
    $table->column('author_email', db_varchar, 50);
    $table->column('author_url', db_varchar);
    $table->column('author_ip', db_varchar, 15)->index();
    $table->column('content', db_text);
    $table->column('content_html', db_text);
    $table->column('is_owner_comment', db_bool);
    $table->column('post_id', db_number)->index();
    $table->column('status_id', db_number)->index();
    $table->column('created_at', db_datetime);

$table = Db_Structure::table('blog_comment_statuses');
    $table->primary_key('id');
    $table->column('code', db_varchar, 30)->index();
    $table->column('name', db_varchar, 50);

$table = Db_Structure::table('blog_comment_subscribers');
    $table->primary_key('id');
    $table->column('post_id', db_number)->index();
    $table->column('email', db_varchar, 100)->index();
    $table->column('subscriber_name', db_varchar);
    $table->column('email_hash', db_varchar, 100)->index();

$table = Db_Structure::table('blog_posts_categories');
    $table->primary_keys('blog_post_id', 'blog_category_id');
    $table->column('code', db_varchar, 50);
