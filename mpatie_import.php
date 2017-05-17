<?php
use MPAT\ImportExport\ImportExport;
namespace MPAT\ImportExport;

function handle_import() {

	if ( isset($_FILES['layout']) ) {
	    $json = file_get_contents( $_FILES['layout']['tmp_name'] );
	    $layout = json_decode($json, true);
	    $meta = $layout["page_layout"]["meta"]["mpat_content"];
	    $title = $layout["page_layout"]['post_title'];

	    if (($old = get_page_by_title($title, ARRAY_A, "page_layout")) && $old["post_status"] == "publish")
	    {
	        echo "Layout $title already exists\n";
	        exit();
	    }

	    $page = array(
	        'post_type' => 'page_layout',
	        'post_status' => 'publish',
	        'post_slug' => 'page_layout',
	        'post_title' => $title,
	        'meta_input' => $layout["page_layout"]["meta"]
	    );
	    $page_id = @wp_insert_post($page);
	    echo "Imported layout $title with id=$page_id\n";

	    echo '<script type="text/javascript">window.top.location.reload();</script>';


	}
	else if ( isset($_FILES['page']) ) {

	    $json = file_get_contents( $_FILES['page']['tmp_name'] );
	    $page = json_decode($json, true);
	    $meta = $page["page"]["meta"]["mpat_content"];
	    $title = $page["page"]['post_title'];

	    if (($old = get_page_by_title($title, ARRAY_A, "page")) && $old["post_status"] == "publish")
	    {
	        echo "Page $title already exists\n";
	        exit();
	    }

	    // affect the new page to a corresponding layout with the same name as the original if possible
	    // or import the one contained in the exported page
	    if (isset($page["page_layout"]) && isset($page["page_layout"]["post_title"])) {

	        $layout_name = $page["page_layout"]["post_title"];

	        if (($layout = get_page_by_title($layout_name, ARRAY_A, "page_layout")) && $layout["post_status"] == "publish") {
	            $meta["layoutId"] = $layout["ID"];
	        }
	        else {
	            $l = array(
	                'post_type' => 'page_layout',
	                'post_status' => 'publish',
	                'post_slug' => 'page_layout',
	                'post_title' => $layout_name,
	                'meta_input' => $page["page_layout"]["meta"]
	            );

	            $meta["layoutId"] = wp_insert_post($l);
	        }
	    }


	    // import media
	    // 1. test fopen is url is openable, if yes do nothing
	    // 2. if not
	    //     a. extract name from url (last path - ext - resolution-if-image)
	    //     b. check local media by that name
	    //         i. if exist, use this url instead
	    //        ii. if not, create new attachment and use it
	    if (isset($page["media"])) {

	        foreach ($page["media"] as $media) {

	            $url = $media['url'];

	            if (!isUrlReachable($url)) {

	                $fileinfo = pathinfo( parse_url($url)['path'] );
	                $filename = $fileinfo['filename'];

	                if ($media['type'] == "image") {
	                    $filename = preg_replace('/-\d+x\d+$/', '', $filename);
	                }

	                $filename .= '.' . $fileinfo['extension'];

	                global $wpdb;
	                $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%$filename'";
	                $results = $wpdb->get_results($query);


	                if ( !empty($results) ) {

	                    $attachment = get_post($results[0]->post_id);
	                    $new_url = $attachment->guid;

	                    if ($media['type'] == "image") {

	                        if ($img = image_get_intermediate_size($results[0]->post_id, 'large')) {
	                            $new_url = $img['url'];
	                        }

	                    }

	                    $meta['content'][ $media['zone'] ][ $media['state'] ]['data'][ $media['key'] ] = $new_url;
	                }
	                else {

	                    if ( !function_exists('media_handle_sideload') ) {
	                        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	                        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	                        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	                    }

	                    $fpath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename;
	                    $fh = fopen( $fpath, 'wb' );
	                    chmod($fpath, 0666);
	                    fwrite( $fh, base64_decode($media['data']) );

	                    $file = array(
	                        'name'     => basename($filename),
	                        'tmp_name' => $fpath,
	                    );

	                    $id = @media_handle_sideload( $file, 0 );

	                    fclose($fh);
	                    if (file_exists($fpath))
	                        @unlink($fpath);

	                    $new_url = wp_get_attachment_url( $id );

	                    if ($media['type'] == "image") {

	                        if ($img = image_get_intermediate_size($id, 'large')) {
	                            $new_url = $img['url'];
	                        }

	                    }

	                    $meta['content'][ $media['zone'] ][ $media['state'] ]['data'][ $media['key'] ] = $new_url;
	                }


	            }



	        }

	    }


	    $new_page = array(
	        'post_type' => 'page',
	        'post_status' => 'publish',
	        'post_slug' => 'page',
	        'post_title' => $title,
	        'meta_input' => array( "mpat_content" => $meta )
	    );
	    $page_id = wp_insert_post($new_page);

	    echo "Imported page $title with id=$page_id\n";

	    echo '<script type="text/javascript">window.top.location.reload();</script>';

	}

}

function isUrlReachable($url){

    if(! $url || ! is_string($url)){
        return false;
    }

    $ch = @curl_init($url);
    if($ch === false){
        return false;
    }

    @curl_setopt($ch, CURLOPT_HEADER         ,true);
    @curl_setopt($ch, CURLOPT_NOBODY         ,true);
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER ,true);
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,true);
    @curl_setopt($ch, CURLOPT_MAXREDIRS      ,10);
    @curl_setopt($ch, CURLOPT_TIMEOUT        ,5);

    @curl_exec($ch);

    if (@curl_errno($ch)) {
        @curl_close($ch);
        return false;
    }

    $code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);

    return ($code >= 200 && $code < 300);
}


?>