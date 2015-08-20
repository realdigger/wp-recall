<?php

add_action('wp', 'rcl_activation_daily_addon_update');
function rcl_activation_daily_addon_update() {
	//wp_clear_scheduled_hook('rcl_daily_addon_update');
	if ( !wp_next_scheduled( 'rcl_daily_addon_update' ) ) {
		$start_date = strtotime(current_time('mysql'));
		wp_schedule_event( $start_date, 'twicedaily', 'rcl_daily_addon_update');
	}
}

add_action('rcl_daily_addon_update','rcl_daily_addon_update');
function rcl_daily_addon_update(){
    $paths = array(RCL_PATH.'add-on',RCL_TAKEPATH.'add-on') ;

    $rcl_addons = new Rcl_Addons();

    foreach($paths as $path){
        if(file_exists($path)){
            $addons = scandir($path,1);
            $a=0;
            foreach((array)$addons as $namedir){
                    $addon_dir = $path.'/'.$namedir;
                    $index_src = $addon_dir.'/index.php';
                    if(!file_exists($index_src)) continue;
                    $info_src = $addon_dir.'/info.txt';
                    if(file_exists($info_src)){
                            $info = file($info_src);
                            $addons_data[$namedir] = $rcl_addons->get_parse_addon_info($info);
                            $addons_data[$namedir]['src'] = $index_src;
                            $a++;
                            flush();
                    }
            }
        }
    }

    $need_update = array();
    foreach((array)$addons_data as $key=>$addon){
        $ver = $rcl_addons->get_actual_version($key,$addon['version']);
        if($ver){
            $addon['new-version'] = $ver;
            $need_update[$key] = $addon;
        }
    }

    update_option('rcl_addons_need_update',$need_update);

}

add_action('wp_ajax_rcl_update_addon','rcl_update_addon');
function rcl_update_addon(){
    $addon = $_POST['addon'];
    $need_update = get_option('rcl_addons_need_update');
    if(!isset($need_update[$addon])) return false;

    $url = 'http://wppost.ru/?remote-request=update-addon';
    $data = array('addon' => $addon, 'action' => 'rcl_get_addon');

    $pathdir = RCL_TAKEPATH.'update/';
    $new_addon = $pathdir.$addon.'.zip';

    if(!file_exists($pathdir)){
        mkdir($pathdir);
        chmod($pathdir, 0755);
    }

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $context  = stream_context_create($options);
    $archive = file_get_contents($url, false, $context);
    file_put_contents($new_addon, $archive);

    $zip = new ZipArchive;

    $res = $zip->open($new_addon);

    if($res === TRUE){

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if($i==0) $dirzip = $zip->getNameIndex($i);
            if($zip->getNameIndex($i)==$dirzip.'info.txt'){
                    $info = true; break;
            }
        }

        if(!$info){
            $zip->close();
            $log['error'] = 'Дополнение не имеет корректного заголовка!';
            echo json_encode($log);
            exit;
        }

        $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

        if(file_exists(RCL_TAKEPATH.'add-on'.'/')){
            $rs = $zip->extractTo(RCL_TAKEPATH.'add-on'.'/');
        }

        $zip->close();
        unlink($new_addon);

        $log['success'] = $addon;
        echo json_encode($log);
        exit;

    }else{
        $log['error'] = 'Не удалось открыть архив!';
        echo json_encode($log);
        exit;
    }
}

