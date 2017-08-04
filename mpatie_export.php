<?php
use MPAT\ImportExport\ImportExport;
namespace MPAT\ImportExport;


function urlIsLocal($url) {

    $wp_upload_dir = wp_upload_dir();

    return (substr($url, 0, strlen($wp_upload_dir['baseurl'])) === $wp_upload_dir['baseurl']);
}

function dfs_links($content, &$links, $path) {

    if (!is_array($content)) {

        if (preg_match("/page:\/\/(\d+)/", $content, $m)) {
            array_push($links, array(
                                "path" => $path,
                                "text" => $content,
                                "id"   => $m[1]
            ));
        }

    }
    else {

        foreach ($content as $k => $v) {

            array_push($path, $k);

            dfs_links($v, $links, $path);

            array_pop($path);
        }
    }

}

function addPageLinks(&$page) {

    if (    !isset($page['page'])
        ||  !isset($page['page']['meta'])
        ||  !isset($page['page']['meta']['mpat_content'])
        ||  !isset($page['page']['meta']['mpat_content']['content'])
        )
        return;

    $content = $page['page']['meta']['mpat_content']['content'];

    $links = array();
    $path = array();

    dfs_links($content, $links, $path);

    $page["page_links"] = $links;


}

function handle_export() {

	header("Content-Type: application/json");


	if ( isset($_GET['layoutid']) ) {

	    $layoutId = $_GET['layoutid'];

        $layout = get_post( $layoutId );
        if ($layout && $layout->post_type == "page_layout") {
            if ($l = ImportExport::getLayoutFromObject($layout)) {
                $filename = $layout->post_title;
                header("Content-disposition: attachment; filename=$filename.mpat-layout");
                echo json_encode($l);
            }
        }

    }
    else if ( isset($_POST['exportall']) ) {

        $pages = ImportExport::getAll();
        foreach ($pages as &$p) {
            addPageLinks($p);
        }
        unset($p);
        header("Content-disposition: attachment; filename=all-pages.mpat-page");
        echo json_encode($pages);

    }
    else if ( isset($_POST['pageid']) ) {

        $pages = ImportExport::getAll( $_POST['pageid'] );

        $mediadata = array("mediadata" => array());

        if ( isset($_POST['addmedia']) && $_POST['addmedia'] == "1" ) {
            foreach ($pages as &$o) {
                addMedia($o, $mediadata["mediadata"]);
            }
            unset($o);
            $pages[] = $mediadata;
            foreach ($pages as &$p) {
                addPageLinks($p);
            }
            unset($p);
            header("Content-disposition: attachment; filename=selected-pages.mpat-page");
            echo json_encode($pages);
        }

        else if ( isset($_POST['exportpages']) && $_POST['exportpages'] == "1" ) {

            foreach ($pages as &$p) {
                addPageLinks($p);
            }
            unset($p);
            header("Content-disposition: attachment; filename=selected-pages.mpat-page");
            echo json_encode($pages);

        }
        else if ( isset($_POST['exportlayouts']) && $_POST['exportlayouts'] == "1" ) {

            $layouts = array();
            $done = array();

            foreach ($pages as $o) {
                if (isset($o["page_layout"]) && isset($o["page_layout"]["ID"]) && !in_array($o["page_layout"]["ID"], $done)) {
                    array_push($layouts, array( "page_layout" => $o["page_layout"]));
                    array_push($done, $o["page_layout"]["ID"]);
                }
            }

            header("Content-disposition: attachment; filename=selected-layouts.mpat-layout");
            echo json_encode($layouts);
        }

    }
	else if ( isset($_GET['pageid']) ) {

	    $pageId = $_GET['pageid'];

        $pages = ImportExport::getAll( array($pageId) );

	    foreach ($pages as $o) {
	        if (isset($o['page']) && isset($o['page']['ID']) &&  $o['page']['ID'] == $pageId) {

	            if ( isset($_GET['addmedia']) && $_GET['addmedia'] == "1" ) {
	                addMedia($o);
	            }


	            $filename = ( isset($o["page"]["post_title"]) ? $o["page"]["post_title"] : "$pageId" );

	            header("Content-disposition: attachment; filename=$filename.mpat-page");
	            echo json_encode($o);
	            break;

	        }
	    }

	}

}
function dumpMedia($path, &$mediadata) {

    if (!isset($mediadata[$path]) || empty($mediadata[$path])) {

        $url = ImportExport::getFullUrl($path);

        // only export media from the media library
        if (urlIsLocal($url)) {
            $mediadata[$path] = base64_encode(file_get_contents($url));
        }

    }

}

function addMediaByKey(&$media, &$mediadata, &$state, $key, $zone, $stateName, $type = "image") {

    if (isset($state['data']) && isset($state['data'][$key]) && !empty($state['data'][$key]) ) {
        $path = $state['data'][$key];

        dumpMedia($path, $mediadata);

        $media[] = array(
            'zone'  => $zone,
            'state' => $stateName,
            'key'   => $key,
            'type'  => $type,
            'url'   => $path
        );
    }
}

function addMediaByPath(&$media, &$mediadata, $path, $url, $type = "image") {

    // if is a url
    if (!empty($url) && ($url[0] == '/' || strpos($url, "://"))) {

        dumpMedia($url, $mediadata);

        $media[] = array(
            'path'  => $path,
            'type'  => $type,
            'url'   => $url
        );


    }

}


function addMediaByList(&$media, &$mediadata, &$list, $key, $path, $type = "image") {

    for ($i = 0; $i < count($list); $i++) {

        if (isset($list[$i][$key])) {

            $full_path = $path;
            $full_path[] = $i;
            $full_path[] = $key;

            addMediaByPath($media, $mediadata, $full_path, $list[$i][$key]);
        }

    }


}

function addMedia(&$o, &$mediadata) {

    if (    !isset($o['page'])
        ||  !isset($o['page']['meta'])
        ||  !isset($o['page']['meta']['mpat_content'])
        ||  !isset($o['page']['meta']['mpat_content']['content'])
        )
        return;

    $mpat_content = $o['page']['meta']['mpat_content'] ;
    $content = $mpat_content['content'];
    $media = array();

    if (    isset($mpat_content['styles'])
        &&  isset($mpat_content['styles']['container'])
        &&  isset($mpat_content['styles']['container']['backgroundImage'])

        ) {

        $bg = $mpat_content['styles']['container']['backgroundImage'];

        addMediaByPath($media, $mediadata, array('styles', 'container', 'backgroundImage'), $bg);

    }

    foreach($content as $key => $value ) {

        foreach($value as $stateName => $state) {
            switch ($state['type']) {
                case 'link':
                    addMediaByKey($media, $mediadata, $state, 'thumbnail', $key, $stateName);
                    break;
                case 'image':
                    addMediaByKey($media, $mediadata, $state, 'imgUrl', $key, $stateName);
                    break;
                case 'video':
                case 'video360pre':
                    addMediaByKey($media, $mediadata, $state, 'thumbnail', $key, $stateName);
                    addMediaByKey($media, $mediadata, $state, 'url', $key, $stateName, "video");
                    break;
                case 'audio':
                    addMediaByKey($media, $mediadata, $state, 'url', $key, $stateName, "audio");
                    break;
                case 'redbutton':
                    addMediaByKey($media, $mediadata, $state, 'img', $key, $stateName);
                    break;
                case 'launcher':
                    if (isset($state['data']) && isset($state['data']['listArray'])) {

                        addMediaByList($media, $mediadata, $state['data']['listArray'], 'thumbnail', array('content', $key, $stateName, 'data', 'listArray') );
                    }
                    break;
                case 'gallery':
                    if (isset($state['data']) && isset($state['data']['images'])) {

                        addMediaByList($media, $mediadata, $state['data']['images'], 'attachmentUrl', array('content', $key, $stateName, 'data', 'images') );
                    }
                    break;
            }
        }
    }
    if (!empty($media))
        $o['media'] = $media;

}

?>