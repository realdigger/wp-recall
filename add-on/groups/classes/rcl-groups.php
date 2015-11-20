<?php

class Rcl_Groups {

    public $number = false;
    public $inpage = 10;
    public $offset = 0;
    public $paged = 0;
    public $orderby = 'term_id';
    public $order = 'DESC';
    public $template = 'list';
    public $include = '';
    public $exclude = '';
    public $filters = 0;
    public $search_form = 1;
    public $query_count = false;
    public $users_count = 0;
    public $data;
    public $user_id;
    public $add_uri;
    public $relation = 'AND';

    function __construct($args){

        $this->init_properties($args);

        if($this->include){
            $this->number = count(explode(',',$this->include));
        }

        if(isset($_GET['groups-filter'])&&$this->filters) $this->orderby = $_GET['groups-filter'];

        $this->add_uri['groups-filter'] = $this->orderby;

        if($this->user_id)
            add_filter('rcl_groups_query',array($this,'add_query_user_id'));

        if($this->orderby=='count')
            add_filter('rcl_groups_query',array($this,'add_query_posts_count'));

        if($this->orderby=='group_users')
            add_filter('rcl_groups_query',array($this,'add_query_group_users'));

    }

    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }

    function remove_data(){
        remove_all_filters('rcl_groups_query');
        remove_all_filters('rcl_groups');
    }

    function setup_groupdata($data){
        global $rcl_group;
        $rcl_group = (object)$data;
        return $rcl_group;
    }

    function get_groups($args = false){
        global $wpdb;

        if($args) $this->init_properties($args);

        $groups = $wpdb->get_results( $this->query() );

        $groups = apply_filters('rcl_groups',$groups);

        //print_r($groups);

        return $groups;
    }

    function count_groups(){
        global $wpdb;
        if($this->number){
            $groups = $this->get_groups();
            return count($groups);
        }else{
            return $wpdb->get_var( $this->query('count') );
        }
    }

    function query($count=false){
        global $wpdb,$rcl_options;

        if($count) $this->query_count = true;

        $query = array(
            'select'    => array(),
            'join'      => array(),
            'where'     => array(),
            'relation'     => $this->relation,
            'group'     => '',
            'orderby'   => ''
        );

        $query['where'][] = "term_taxonomy.taxonomy = 'groups' AND term_taxonomy.parent = '0'";
        $query['join'][] = "INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON terms.term_id=term_taxonomy.term_id";


        if($count){

            $query['select'] = array(
                "COUNT(terms.term_id)"
            );

        }else{

            $query['select'] = array(
                "terms.term_id"
              , "terms.name"
              , "groups.admin_id"
              , "groups.group_users"
              , "groups.group_status"
            );

            $query['join'][] = "INNER JOIN ".RCL_PREF."groups AS groups ON terms.term_id=groups.ID";

        }

        if($this->include) $query['where'][] = "terms.term_id IN ($this->include)";
        if($this->exclude) $query['where'][] = "terms.term_id NOT IN ($this->exclude)";

        $query = apply_filters('rcl_groups_query',$query);

        $query_string = "SELECT "
            . implode(", ",$query['select'])." "
            . "FROM $wpdb->terms AS terms "
            . implode(" ",$query['join'])." ";

        if($query['where']) $query_string .= "WHERE ".implode(' '.$query['relation'].' ',$query['where'])." ";
        if($query['group']) $query_string .= "GROUP BY ".$query['group']." ";

        if(!$this->query_count){
            if(!$query['orderby']) $query['orderby'] = "terms.".$this->orderby;
            $query_string .= "ORDER BY ".$query['orderby']." $this->order ";
            $query_string .= "LIMIT $this->offset,$this->number";
        }
        //if(!$count) echo $query_string;

        if($this->query_count)
            $this->query_count = false;

        return $query_string;

    }

    function add_query_user_id($query){

        if($this->query_count){
            $query['select'] = array("COUNT(DISTINCT terms.term_id)");
            $query['join'] = array("INNER JOIN ".RCL_PREF."groups AS groups ON terms.term_id=groups.ID");
        }else{
            $query['group'] = "terms.term_id";
        }

        $query['join'][] = "LEFT JOIN ".RCL_PREF."groups_users AS groups_users ON terms.term_id=groups_users.group_id";
        $query['where'] = array("groups.admin_id='$this->user_id' OR groups_users.user_id='$this->user_id'");

        return $query;
    }

    //добавляем выборку данных постов в основной запрос
    function add_query_posts_count($query){
        global $wpdb;

        if(!$this->query_count){
            $query['select'][] = "term_taxonomy.count";
            $query['orderby'] = "term_taxonomy.count";
        }

        return $query;
    }

    function add_query_group_users($query){
        global $wpdb;

        if(!$this->query_count){
            $query['orderby'] = "groups.group_users";
        }

        return $query;
    }

    function search_request(){
        global $user_LK;

        $rqst = '';

        if(isset($_GET['search-groups'])||$user_LK){
            $rqst = array();
            foreach($_GET as $k=>$v){
                if($k=='navi'||$k=='groups-filter') continue;
                $rqst[$k] = $k.'='.$v;
            }

        }

        if($this->add_uri){
            foreach($this->add_uri as $k=>$v){
                $rqst[$k] = $k.'='.$v;
            }
        }

        $rqst = apply_filters('rcl_groups_uri',$rqst);

        return $rqst;
    }

    function get_filters($count_groups = false){
        global $post,$active_addons,$user_LK;

        if(!$this->filters) return false;

        $content = '';

        if($this->search_form) $content = apply_filters('rcl_groups_search_form',$content);

        $count_groups = (false!==$count_groups)? $count_groups: $this->count_groups();

        $content .='<h3>'.__('Total groups','rcl').': '.$count_groups.'</h3>';

        if(isset($this->add_uri['groups-filter'])) unset($this->add_uri['groups-filter']);

        $s_array = $this->search_request();

        $rqst = ($s_array)? implode('&',$s_array).'&' :'';

        $url = ($user_LK)? get_author_posts_url($user_LK): get_permalink($post->ID);

        $perm = rcl_format_url($url).$rqst;

        $filters = array(
            'name'       => __('Name','rcl'),
            'term_id'    => __('Date','rcl'),
            'count'      => __('Publications','rcl'),
            'group_users'      => __('Users','rcl'),
        );

        $filters = apply_filters('rcl_groups_filter',$filters);

        $content .= '<div class="rcl-data-filters">'.__('Filter by','rcl').': ';

        foreach($filters as $key=>$name){
            $content .= $this->get_filter($key,$name,$perm);
        }

        $content .= '</div>';

        return $content;

    }

    function get_filter($key,$name,$perm){
        return '<a class="data-filter '.rcl_a_active($this->orderby,$key).'" href="'.$perm.'groups-filter='.$key.'">'.$name.'</a> ';
    }
}
