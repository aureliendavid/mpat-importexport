<?php
use MPAT\ImportExport\ImportExport;
namespace MPAT\ImportExport;


function handle_export() {

	header("Content-Type: application/json");

	$all = ImportExport::getAll();

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
	else if ( isset($_GET['pageid']) ) {

	    $pageId = $_GET['pageid'];

	    foreach ($all as $o) {
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


function addMediaByKey(&$media, &$state, $key, $zone, $stateName, $type = "image") {
    if ($state['data'] && $state['data'][$key]) {
        $data = base64_encode(file_get_contents($state['data'][$key]));
        $media[] = array(
            'zone'  => $zone,
            'state' => $stateName,
            'key'   => $key,
            'type'  => $type,
            'url'   => $state['data'][$key],
            'data'  => $data
        );
    }
}

function addMedia(&$o) {
    $content = $o['page']['meta']['mpat_content']['content'];
    $media = array();

    foreach($content as $key => $value ) {

        //TODO: export background if url

        foreach($value as $stateName => $state) {
            switch ($state['type']) {
                case 'link':
                    addMediaByKey($media, $state, 'thumbnail', $key, $stateName);
                    break;
                case 'image':
                    addMediaByKey($media, $state, 'imgUrl', $key, $stateName);
                    break;
                case 'video':
                    addMediaByKey($media, $state, 'thumbnail', $key, $stateName);
                    addMediaByKey($media, $state, 'url', $key, $stateName, "video");
                    break;
                case 'launcher':
                    //TODO
                    break;
                case 'gallery':
                    //TODO
                    break;
            }
        }
    }
    if (!empty($media))
        $o['media'] = $media;

}

?>