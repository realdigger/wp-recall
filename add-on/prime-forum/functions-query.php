<?php

function pfm_is_search(){
    global $PrimeQuery;
    return ($PrimeQuery->is_search)? true: false;
}

function pfm_is_author(){
    global $PrimeQuery;
    return ($PrimeQuery->is_author)? true: false;
}

function pfm_is_home(){
    global $PrimeQuery;
    return ($PrimeQuery->is_frontpage)? true: false;
}

function pfm_is_forum(){
    global $PrimeQuery;
    return ($PrimeQuery->is_forum)? true: false;
}

function pfm_is_group(){
    global $PrimeQuery;
    return ($PrimeQuery->is_group)? true: false;
}

function pfm_is_topic(){
    global $PrimeQuery;
    return ($PrimeQuery->is_topic)? true: false;
}

function pfm_have_groups(){
    global $PrimeQuery;    
    return (!$PrimeQuery->groups || $PrimeQuery->errors)? false: true;
}

function pfm_have_forums(){
    global $PrimeQuery;
    return (!$PrimeQuery->forums || $PrimeQuery->errors)? false: true;
}

function pfm_have_topics(){
    global $PrimeQuery;
    return (!$PrimeQuery->topics || $PrimeQuery->errors)? false: true;
}

function pfm_have_posts(){
    global $PrimeQuery;
    return (!$PrimeQuery->posts || $PrimeQuery->errors)? false: true;
}

function pfm_reset_forumdata(){
    global $PrimeQuery,$PrimeForum;
    $PrimeForum = $PrimeQuery->object;
}

function pfm_have_errors($errors = false){
    global $PrimeQuery;
    
    if(!$errors){
        $errors = $PrimeQuery->errors;
    }
    
    if(!$errors || !is_array($errors)) return false;
    
    return true;
    
}

function pfm_get_next($type){
    global $PrimeQuery,$PrimeGroup,$PrimeForum,$PrimeTopic,$PrimePost;
    
    $nextID = $PrimeQuery->next[$type];
    
    switch($type){
        case 'group':

            if(isset($PrimeQuery->groups[$nextID])){
                
                $PrimeGroup = $PrimeQuery->groups[$nextID];
                
                $PrimeQuery->next[$type] += 1;
                
                return $PrimeGroup;
                
            }
            
        break;
        case 'forum':
            
            if(isset($PrimeQuery->forums[$nextID])){
                
                if(isset($PrimeQuery->object->group_id))
                    $groupID = $PrimeQuery->object->group_id;
                else if(isset($PrimeGroup->group_id))
                    $groupID = $PrimeGroup->group_id;
                else
                    return;
                
                if($PrimeQuery->forums[$nextID]->group_id == $groupID){
                
                    $PrimeForum = $PrimeQuery->forums[$nextID];

                    $PrimeQuery->next[$type] += 1;

                    return $PrimeForum;
                
                }
                
            }
            
        break;
        case 'topic':
            
            if(isset($PrimeQuery->topics[$nextID])){
                
                $PrimeTopic = $PrimeQuery->topics[$nextID];
                
                $PrimeQuery->next[$type] += 1;
                
                return $PrimeTopic;
                
            }
            
        break;
        case 'post':
            
            if(isset($PrimeQuery->posts[$nextID])){
                
                $PrimePost = $PrimeQuery->posts[$nextID];
                
                $PrimeQuery->next[$type] += 1;
                
                return $PrimePost;
                
            }

        break;
        
    }

    return false;
    
}

function pfm_the_last_topic(){
    global $PrimeForum,$PrimeQuery;
    
    $lastTopic = $PrimeQuery->search_forum_last_topic($PrimeForum->forum_id);
    
    if(!$lastTopic){
        echo __('Topics yet','wp-recall'); return;
    }
    
    echo '<a href="'.pfm_get_topic_permalink($lastTopic->topic_id).'">'
            .$lastTopic->topic_name
        .'</a>';
    
}

function pfm_the_last_post(){
    global $PrimeForum,$PrimeTopic,$PrimeQuery;
    
    if(pfm_is_home() || pfm_is_group() || $PrimeForum->parent_id && !$PrimeTopic){
        $lastPost = $PrimeQuery->search_forum_last_post($PrimeForum->forum_id);
    }else{
        $lastPost = $PrimeQuery->search_topic_last_post($PrimeTopic->topic_id);
    }

    if(!$lastPost){
        echo __('not found','wp-recall'); return;
    }
    
    $name = $lastPost->user_id? get_the_author_meta('display_name',$lastPost->user_id): __('Guest','wp-recall');
    
    echo __('from','wp-recall').' '.$name.': <a href="'.pfm_get_post_permalink($lastPost->post_id).'">'
            . human_time_diff( strtotime($lastPost->post_date), current_time('timestamp') ). ' '.__('ago','wp-recall')
        .'</a>';

    
}