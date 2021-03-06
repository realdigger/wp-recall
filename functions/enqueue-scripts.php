<?php

function rcl_sortable_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-sortable');
}

function rcl_resizable_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-resizable');
}

function rcl_slider_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-slider');
}

function rcl_datepicker_scripts(){
    wp_enqueue_style('jquery-ui-datepicker', RCL_URL.'assets/js/datepicker/style.css' );
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
}

function rcl_bxslider_scripts(){
    wp_enqueue_style( 'bx-slider', RCL_URL.'assets/js/jquery.bxslider/jquery.bxslider.min.css' );
    wp_enqueue_script( 'jquery' );
    rcl_enqueue_script( 'bx-slider', RCL_URL.'assets/js/jquery.bxslider/jquery.bxslider.min.js' );
}

function rcl_dialog_scripts(){  
    rcl_enqueue_style( 'ssi-modal', RCL_URL.'assets/js/ssi-modal/ssi-modal.min.css' );
    rcl_enqueue_script( 'ssi-modal', RCL_URL.'assets/js/ssi-modal/ssi-modal.min.js' );
}

function rcl_webcam_scripts(){
    wp_enqueue_script( 'say-cheese', RCL_URL.'assets/js/say-cheese/say-cheese.js', array(),VER_RCL,true );
}

function rcl_fileupload_scripts(){
    rcl_enqueue_script( 'jquery-ui-widget', RCL_URL.'assets/js/fileupload/js/vendor/jquery.ui.widget.js', array(),true );

    rcl_enqueue_script( 'load-image', RCL_URL.'assets/js/fileupload/js/load-image.all.min.js', array(),true );
    rcl_enqueue_script( 'canvas-to-blob', RCL_URL.'assets/js/fileupload/js/canvas-to-blob.min.js', array(),true );

    rcl_enqueue_script( 'jquery-iframe-transport', RCL_URL.'assets/js/fileupload/js/jquery.iframe-transport.js', array(),true );
    rcl_enqueue_script( 'jquery-fileupload', RCL_URL.'assets/js/fileupload/js/jquery.fileupload.js', array(),true );
    rcl_enqueue_script( 'jquery-fileupload-process', RCL_URL.'assets/js/fileupload/js/jquery.fileupload-process.js', array(),true );
    rcl_enqueue_script( 'jquery-fileupload-image', RCL_URL.'assets/js/fileupload/js/jquery.fileupload-image.js', array(),true );
}

function rcl_crop_scripts(){
    wp_enqueue_style( 'jcrop' );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jcrop' );
}

function rcl_rangyinputs_scripts(){
    rcl_enqueue_script( 'rangyinputs', RCL_URL.'assets/js/rangyinputs.js' );
}

function rcl_font_awesome_style(){
    
    if( wp_style_is( 'font-awesome' ) ) 
        wp_deregister_style('font-awesome');
    
    wp_enqueue_style( 'font-awesome', RCL_URL.'assets/css/font-awesome/css/font-awesome.min.css' );
}

function rcl_animate_css(){   
    rcl_enqueue_style( 'animate-css', RCL_URL.'assets/css/animate-css/animate.min.css' );
}

add_action('login_enqueue_scripts','rcl_enqueue_wp_form_scripts',1);
function rcl_enqueue_wp_form_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl-core-scripts', RCL_URL.'assets/js/core.js', array('jquery') );
    wp_enqueue_script( 'rcl-primary-scripts', RCL_URL.'assets/js/scripts.js', array('jquery') );
    wp_enqueue_style( 'rcl-regform-style', RCL_URL.'assets/css/regform.css' );
}

function rcl_frontend_scripts(){
    global $rcl_options,$user_LK,$user_ID,$post;
    
    if(!isset($rcl_options['font_icons']))  $rcl_options['font_icons']=1;
    
    wp_enqueue_script( 'jquery' );

    if(rcl_is_office()){
        rcl_dialog_scripts();
        if(rcl_is_office($user_ID)){
            rcl_fileupload_scripts();
        }
    }

    rcl_font_awesome_style();
    rcl_animate_css();

    rcl_enqueue_script( 'rcl-core-scripts', RCL_URL.'assets/js/core.js' );
    rcl_enqueue_script( 'rcl-primary-scripts', RCL_URL.'assets/js/scripts.js' );

    $local = array(
        'save' => __('Save','wp-recall'),
        'close' =>__('Close','wp-recall'),
        'wait' => __('Please wait','wp-recall'),
        'preview' => __('Preview','wp-recall'),
        'error' => __('Error','wp-recall'),
        'loading' => __('Loading','wp-recall'),
        'upload' => __('Upload','wp-recall')
    );
    
    if(isset($rcl_options['difficulty_parole'])){
        if($rcl_options['difficulty_parole']&&(!$user_ID||$user_LK==$user_ID)){
            $local['pass0'] = __('Very weak','wp-recall');
            $local['pass1'] = __('Weak','wp-recall');
            $local['pass2'] = __('Worse than average','wp-recall');
            $local['pass3'] = __('Average','wp-recall');
            $local['pass4'] = __('Reliable','wp-recall');
            $local['pass5'] = __('Strong','wp-recall');
        }
    }

    $data = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'wpurl' => get_bloginfo('wpurl'),
        'rcl_url' => RCL_URL,
        'user_ID' => (int)$user_ID,       
        'nonce' => wp_create_nonce( 'rcl-post-nonce' ),
        'local' => apply_filters('rcl_js_localize',$local)
    );

    $data['post_ID'] = (isset($post->ID)&&$post->ID)? (int)$post->ID: (int)0;
    $data['office_ID'] = ($user_LK)? (int)$user_LK: (int)0;
    $data['mobile'] = (wp_is_mobile())? (int)1: (int)0;
    $data['https'] = @( !isset($_SERVER["HTTPS"])||$_SERVER["HTTPS"] != 'on' ) ? (int)0:  (int)1;
    $data['slider'] = (isset($rcl_options['slide-pause'])&&$rcl_options['slide-pause'])? "{auto:true,pause:".($rcl_options['slide-pause']*1000)."}": "''";
    $data['local']['requared_fields_empty'] = __('Fill in all required fields','wp-recall');
    
    $data = apply_filters('rcl_init_js_variables',$data);

    wp_localize_script( 'jquery', 'Rcl', $data);

}

function rcl_admin_scrips(){
    wp_enqueue_style( 'rcl-admin-style', RCL_URL.'admin/assets/style.css' );
    wp_enqueue_style( 'wp-color-picker' ); 
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl-core-scripts', RCL_URL.'assets/js/core.js', array('jquery') );
    wp_enqueue_script( 'rcl-admin-scripts', RCL_URL.'admin/assets/scripts.js', array('wp-color-picker'), VER_RCL );
}