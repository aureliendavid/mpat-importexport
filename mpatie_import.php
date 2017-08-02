<?php
namespace MPAT\ImportExport;

use MPAT\ImportExport\ImportExport;

$nullGuard = null;

function handle_import() {

	if ( isset($_FILES['layout']) ) {
		$json = file_get_contents( $_FILES['layout']['tmp_name'] );
		$layout = json_decode($json, true);

		if (isset($page["page_layout"])) {

			$title = $layout["page_layout"]['post_title'];
			$id = importSingleLayout($layout);

			if ($id == -1) {
				return "Layout $title already exists.\n";
			}
			else {
				return "Imported page $title with id=$id.\n";
			}

		}
		else {

			$ok = 0;
			$ko = 0;

			foreach($layout as $l) {

				$id=-1;
				if (isset($l["page_layout"])) {
					$id = importSingleLayout($l);
				}

				if ($id == -1) {
					$ko++;
				}
				else {
					$ok++;
				}

			}

			return "Added $ok layouts and $ko already existed.\n";

		}

		//echo '<script type="text/javascript">window.top.location.reload();</script>';


	}
	else if ( isset($_FILES['page']) ) {

		$json = file_get_contents( $_FILES['page']['tmp_name'] );
		$page = json_decode($json, true);

		if (isset($page["page"])) {

			$title = $page["page"]['post_title'];
			$id = importSinglePage($page);

			if ($id == -1) {
				return "Page $title already exists\n";
			}
			else {
				return "Imported page $title with id=$id\n";
			}

		}
		else {

			$ok = 0;
			$ko = 0;
			$message = "";
			$dict = array();

			$mediadata = findMediaData($page);

			foreach($page as $p) {

				$id=-1;
				if (isset($p["page"])) {
					$old_id = $p["page"]["ID"];
					$id = importSinglePage($p, $mediadata);
				}

				if ($id == -1) {
					$ko++;
				}
				else {
					$ok++;
					$dict[ $old_id ] = $id;
				}

			}

			// second pass to update page links
			foreach($page as $p) {

				$old_id = $p["page"]["ID"];

				if (isset($dict[ $old_id ])) {

					$new_id = $dict[ $old_id ];

					///////// update parent ////////////

					$old_parent = $p["page"]["post_parent"];

					if ($old_parent) {

						if ( isset($dict[$old_parent]) ) {

							$new_parent = $dict[$old_parent];

							$updated_page = array(
							  'ID'          => $new_id,
							  'post_parent'	=> $new_parent,
							);

							wp_update_post( $updated_page );

						}
						else {
							$message .= "Page $new_id has parent $old_parent which wasn't imported.<br />\n";
						}
					}



					///////// update page links ////////////

					$meta = get_post_meta($new_id, 'mpat_content', true);

					$needs_update = 0;

					foreach ($p["page_links"] as $l) {

						if (isset($dict[ $l["id"] ])) {

							$new_link_id = $dict[ $l["id"] ] ;
							$new_value = "page://" . $new_link_id;

							updateMetaFromPath($meta, $l["path"], $new_value);

							$needs_update = 1;

						}
						else {
							$message .= "Page $new_id points to page ".$l["id"]." which wasn't imported.<br />\n";
						}
					}

					if ($needs_update) {

						update_post_meta($new_id, 'mpat_content', $meta);

					}


				}

			}

			$message .= "Added $ok pages and $ko already existed.\n";

			return $message ;
		}


		//echo '<script type="text/javascript">window.top.location = window.top.location.href;</script>';

	}

}

function updateMetaFromPath(&$meta, $path, $new_value) {

	$sub = &$meta["content"];

	while (!empty($path)) {
		$p = array_shift($path);
		$sub = &$sub[$p];
	}

	$sub = $new_value;

}

function &findMediaData(&$page) {

	$last = array_pop($page);

	if (isset($last['mediadata'])) {
		return $last['mediadata'];
	}
	else {
		array_push($page, $last);
		// can't directly return null by reference
		return $nullGuard;
	}


}


function importSingleLayout(&$layout) {

	$meta = $layout["page_layout"]["meta"]["mpat_content"];
	$title = $layout["page_layout"]['post_title'];

	if (($old = get_page_by_title($title, ARRAY_A, "page_layout")) && $old["post_status"] == "publish") {
		return -1;
	}


	$page = array(
		'post_type' => 'page_layout',
		'post_status' => 'publish',
		'post_slug' => 'page_layout',
		'post_title' => $title,
		'meta_input' => $layout["page_layout"]["meta"]
	);
	$page_id = @wp_insert_post($page);

	return $page_id;

}

function importSinglePage(&$page, &$mediadata = null) {

	$meta = $page["page"]["meta"]["mpat_content"];
	$title = $page["page"]['post_title'];

	if (($old = get_page_by_title($title, ARRAY_A, "page")) && $old["post_status"] == "publish")
	{
		return -1;
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
				$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND ( meta_value LIKE '%/$filename' OR meta_value = '$filename' )" ;
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
					$stop = false;
					if (isset($media['data']) && !empty($media['data'])) {

						fwrite( $fh, base64_decode($media['data']) );
					}
					else if ($mediadata && isset($media['url']) && isset($mediadata[ $media['url'] ]) ) {

						fwrite( $fh, base64_decode( $mediadata[ $media['url'] ] ) );
					}
					else {
						$stop = true;
					}


					if (!$stop) {

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

	}


	$new_page = array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_slug' => 'page',
		'post_title' => $title,
		'meta_input' => array( "mpat_content" => $meta )
	);
	$page_id = wp_insert_post($new_page);

	return $page_id;

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