<?php

global $PrimeQuery,$PrimeGroup,$PrimeForum,$PrimeTopic,$PrimePost;

require_once 'classes/class-prime-roles.php';
require_once 'classes/class-prime-user.php';
require_once 'classes/class-prime-visits.php';
require_once 'classes/class-prime-groups.php';
require_once 'classes/class-prime-forums.php';
require_once 'classes/class-prime-topics.php';
require_once 'classes/class-prime-posts.php';
require_once 'classes/class-prime-meta.php';
require_once 'classes/class-prime-query.php';
require_once 'classes/class-prime-form.php';
require_once 'classes/class-prime-page-navi.php';

require_once 'functions-actions.php';
require_once 'functions-capabilities.php';
require_once 'functions-compatibility.php';
require_once 'functions-database.php';
require_once 'functions-forms.php';
require_once 'functions-groups.php';
require_once 'functions-forums.php';
require_once 'functions-topics.php';
require_once 'functions-posts.php';
require_once 'functions-post-content.php';
require_once 'functions-query.php';
require_once 'functions-templates.php';
require_once 'functions-permalink.php';


if (is_admin())
    require_once 'admin/index.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','pfm_scripts',10);
endif;

function pfm_scripts(){
    rcl_enqueue_style('pfm-style', rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script('pfm-scripts', rcl_addon_url('js/scripts.js', __FILE__));
}

add_action('init','pfm_init_tab',10);
function pfm_init_tab(){

    rcl_tab(
        array(
            'id'=>'prime-forum',
            'supports' => array('ajax'),
            'name'=>__('Форум'),
            'public'=>0,
            'icon'=>'fa-sitemap',
            'output'=>'menu',
            'content'=>array(
                array(
                    'id'=>'my-topics',
                    'icon' => 'fa-folder',
                    'name'=>__('Начатые темы'),
                    'callback' => array(
                        'name'=>'pfm_user_topics_start'
                    )
                ),
                array(
                    'id'=>'my-posts',
                    'icon' => 'fa-folder',
                    'name'=>__('Сообщения в чужих темах'),
                    'callback' => array(
                        'name'=>'pfm_user_posts_other_topics'
                    )
                )
            )
        )
    );
    
}

function pfm_user_posts_other_topics($master_id){
    global $PrimeTopic;
    
    $TopicsQuery = new PrimeTopics();
    $PostsQuery = new PrimePosts();
    
    $args = array(
        'user_id__not_in' => array($master_id),
        'join_query' => array(
            array(
                'table' => $PostsQuery->query['table'],
                'on_topic_id' => 'topic_id',
                'fields' => false,
                'user_id' => $master_id
            )
        )
    );
    
    $countTopics = $TopicsQuery->count($args);
    
    if(!$countTopics)
        return pfm_get_notice(__('Сообщений в чужих темах пока нет.'));
    
    $pageNavi = new Rcl_PageNavi('forum',$countTopics,array('in_page'=>20));
    
    $args['groupby'] = $TopicsQuery->query['table']['as'].'.topic_id';
    $args['offset'] = $pageNavi->offset;
    $args['number'] = $pageNavi->in_page;
    
    $TopicsQuery->reset_query();
    $TopicsQuery->set_query($args);
            
    $TopicsQuery->query['select'] = array(
        "pfm_topics.*",
        "MAX(pfm_posts.post_date) AS last_post_date"
    );

    $TopicsQuery->query['orderby'] = "MAX(pfm_posts.post_date)";
    
    $topics = $TopicsQuery->get_data('get_results');
    
    $ThemeID = get_option('rcl_pforum_template');
    
    $theme = rcl_get_addon($ThemeID);
    
    $content = '<h3>'.__('Сообщения в чужих темах на форуме').'</h3>';

    $content .= '<div id="prime-forum">';
    
    $content .= $pageNavi->pagenavi();
    
    foreach(wp_unslash($topics) as $PrimeTopic){
        
        $content .= rcl_get_include_template('pfm-single-topic.php',$theme['path']);
    }
    
    $content .= $pageNavi->pagenavi();
    
    $content .= '</div>';
    
    return $content;
}

function pfm_user_topics_start($master_id){
    global $PrimeTopic;
    
    $TopicsQuery = new PrimeTopics();
    $PostsQuery = new PrimePosts();
    
    $countTopics = $TopicsQuery->count(array(
        'user_id' => $master_id
    ));
    
    if(!$countTopics)
        return pfm_get_notice(__('Начатых тем на форуме пока нет.'));
    
    $pageNavi = new Rcl_PageNavi('forum',$countTopics,array('in_page'=>20));
    
    $args = array(
        'user_id' => $master_id,
        'offset' => $pageNavi->offset,
        'number' => $pageNavi->in_page,
        'join_query' => array(
            array(
                'table' => $PostsQuery->query['table'],
                'on_topic_id' => 'topic_id',
                'fields' => false
            )
        ),
        'groupby' => $TopicsQuery->query['table']['as'].'.topic_id'
    );
    
    $TopicsQuery->set_query($args);
            
    $TopicsQuery->query['select'] = array(
        "pfm_topics.*",
        "MAX(pfm_posts.post_date) AS last_post_date"
    );

    $TopicsQuery->query['orderby'] = "MAX(pfm_posts.post_date)";

    $topics = $TopicsQuery->get_data('get_results');
    
    $ThemeID = get_option('rcl_pforum_template');
    
    $theme = rcl_get_addon($ThemeID);
    
    $content = '<h3>'.__('Начатые темы на форуме').'</h3>';

    $content .= '<div id="prime-forum">';
    
    $content .= $pageNavi->pagenavi();
    
    foreach(wp_unslash($topics) as $PrimeTopic){
        
        $content .= rcl_get_include_template('pfm-single-topic.php',$theme['path']);
    }
    
    $content .= $pageNavi->pagenavi();
    
    $content .= '</div>';
    
    return $content;
    
}

add_filter('page_rewrite_rules', 'pfm_set_rewrite_rules');
function pfm_set_rewrite_rules($rules) {
    global $wp_rewrite;

    if(!pfm_get_option('home-page')) return $rules;
    
    $page = get_post(pfm_get_option('home-page'));

    $rules[$page->post_name.'/forum-group/([^/]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-group=$matches[1]';
    $rules[$page->post_name.'/forum-group/([^/]+)/page/([0-9]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-group=$matches[1]&pfm-page=$matches[2]';
    
    $rules[$page->post_name.'/([^/]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]';
    $rules[$page->post_name.'/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]&pfm-topic=$matches[2]';

    $rules[$page->post_name.'/([^/]+)/page/([0-9]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]&pfm-page=$matches[2]';
    $rules[$page->post_name.'/([^/]+)/([^/]+)/page/([0-9]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]&pfm-topic=$matches[2]&pfm-page=$matches[3]';

    return $rules;
}

add_filter('query_vars', 'pfm_set_query_vars');
function pfm_set_query_vars($vars) {
	
    $vars[] = 'pfm-group';
    $vars[] = 'pfm-forum';
    $vars[] = 'pfm-topic';
    $vars[] = 'pfm-page';

    $vars = apply_filters('pfm_query_vars', $vars);

    return $vars;
}

add_action('wp','pfm_init_query',10);
function pfm_init_query(){
    global $post,$PrimeQuery,$PrimeGroup,$PrimeForum,$PrimeTopic,$PrimePost,$PrimeUser;

    if($post->ID != pfm_get_option('home-page')) return;
    
    $PrimeUser = new PrimeUser();
    
    $PrimeQuery = new PrimeQuery();
    
    do_action('pfm_init');

}

add_action('pfm_init','pfm_update_current_visitor',10);
function pfm_update_current_visitor(){
    global $user_ID,$PrimeQuery;
    
    if(!$user_ID) return false;
    
    $args = array(
        'user_id' => $user_ID
    );
    
    if($PrimeQuery->is_group){
        $args['group_id'] = $PrimeQuery->object->group_id;
    }else if($PrimeQuery->is_forum){
        $args['group_id'] = $PrimeQuery->object->group_id;
        $args['forum_id'] = $PrimeQuery->object->forum_id;
    }else if($PrimeQuery->is_topic){
        $args['group_id'] = $PrimeQuery->object->group_id;
        $args['forum_id'] = $PrimeQuery->object->forum_id;
        $args['topic_id'] = $PrimeQuery->object->topic_id;
    }
    
    pfm_update_visit($args);
    
}

add_filter('wp_title','pfm_setup_title',10);
function pfm_setup_title($title){
    global $PrimeQuery;
    
    if($PrimeQuery){
        
        $object = $PrimeQuery->object;
        
        if(!$object) return $title;
        
        if($PrimeQuery->is_topic){
            $title = $object->topic_name.' | '.__('Форум').' '.$object->forum_name;
        }else if($PrimeQuery->is_forum){
            $title = __('Форум').' '.$object->forum_name;
        }else if($PrimeQuery->is_group){
            $title = __('Группа форумов').' '.$object->group_name;
        }
        
        if($PrimeQuery->is_page){
            $title .= ' | '.__('Страница').' '.$PrimeQuery->current_page;
        }
        
    }
    
    return $title;
}

add_filter('the_title','pfm_setup_page_title',10);
function pfm_setup_page_title($title){
    global $PrimeQuery;
    
    if($PrimeQuery && in_the_loop()){
        
        $object = $PrimeQuery->object;
        
        if(!$object) return $title;
        
        if($PrimeQuery->is_topic){
            $title = $object->topic_name;
        }else if($PrimeQuery->is_forum){
            $title = __('Форум').' '.$object->forum_name;
        }else if($PrimeQuery->is_group){
            $title = __('Группа форумов').' '.$object->group_name;
        }
        
        if($PrimeQuery->is_page){
            $title .= ' | '.__('Страница').' '.$PrimeQuery->current_page;
        }
        
    }
    
    return $title;
}

function pfm_get_option($name){
    $PfmOptions = get_option('rcl_pforum_options');
    if(!isset($PfmOptions[$name])) return false;
    return $PfmOptions[$name];
}