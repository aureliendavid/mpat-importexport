<?php
/*
 * Plugin Name: MPAT ImportExport
 * Plugin URI: https://github.com/aureliendavid/mpat-importexport/
 * Description: Import and export MPAT pages and layout
 * Version: 1.0.beta
 * Author: Aurelien David
 * Author URI: https://github.com/aureliendavid/
 * License: GPL2
 */

namespace MPAT\ImportExport;

require "mpatie_export.php";
require "mpatie_import.php";

class ImportExport {

    function import_export_init() {
        add_menu_page('MPAT ImportExport', 'ImportExport', 'manage_options', 'MPAT_ImportExport', array(&$this, 'display'), 'dashicons-download');
    }

    static function import_export_onload() {

        if ( isset($_GET['page']) && strtolower($_GET['page']) === "mpat_importexport" )
        {
            $action = isset($_GET['action']) ? $_GET['action'] : "";

            switch ($action) {
                case 'export':

                    handle_export();

                    exit();
                    break;



                case 'import':

                    handle_import();

                    exit();
                    break;
                default:
                    break;
            }
        }

    }



    function display() {

        wp_enqueue_style('mpat-importexport', plugin_dir_url(__FILE__) . 'mpat_import_export.css');

?>

        <h2>MPAT Importer/Exporter for <?php echo bloginfo('name'); ?></h2>

        <script src="<?php echo plugin_dir_url(__FILE__); ?>mpat_import_export.js" > </script>

        <div id="importzone">

            <div style="display: inline-block;">
                <form action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=import'; ?>"
                      id="page-form" method="post" enctype="multipart/form-data" target="_top" >

                    <input id="page-fileinput" type="file" name="page" style="display: none;"  />
                    <button type="button" id="page-btn" style="display:inline;vertical-align:center;">Import new page</button>

                </form>
            </div>
            <div style="display: inline-block;">
                <form action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=import'; ?>"
                      id="layout-form" method="post" enctype="multipart/form-data" target="import-target" >

                    <input id="layout-fileinput" type="file" name="layout" style="display: none;"  />
                    <button type="button" id="layout-btn" style="display:inline;vertical-align:center;">Import new layout</button>

                </form>
            </div>
            <div style="display: inline-block;">
                <iframe id="import-target" name="import-target" scrolling="no" style="width:80%;height:25px;border:0;display:inline;"></iframe>
            </div>
        </div>

        <div id="exportzone">

            <table class="iexport-table">
                <thead>
                <tr>
                    <td>Page</td>
                    <td>Layout</td>
                    <td>Map</td>
                    <td>Export</td>
                </tr>
                </thead>
                <tbody>
<?php
                $all = ImportExport::getAll();

                foreach ($all as $o)  {
                    if (isset($o['page'])) {

                        $id = $o['page']['ID'];

                        echo "<tr>";

                        echo "<td>" . $o['page']['post_title'] . "</td>";

                        $lname = "";
                        if (isset($o['page_layout'])) {
                            $lid = $o['page_layout']['ID'];
                            $lname = $o['page_layout']['post_title'];

                            echo "<td>" . $lname . "</td>\n";
                            echo "<td><canvas id='canvas-$id' width='128' height='72' /></td>\n";

                            $zones = $o['page_layout']['meta']['mpat_content']['layout'];
                            $content = $o['page']['meta']['mpat_content']['content'];

                            echo "<script>\nconsole.log('calling ctx');";
                            echo "var ctx = window.getCtx('$id');\n";

                            foreach ($zones as $z) {

                                $type = $content[ $z['i'] ]['_0']['type'];
                                $text = strtoupper($type[0]);

                                echo "window.drawComponent(ctx, $z[x], $z[y], $z[w], $z[h], '$text');\n";

                            }
                            echo "</script>\n";


                            echo "<td><div id='exportbtns'>";
                            echo "<a href='".str_replace("//","/", $_SERVER['REQUEST_URI'])."&action=export&pageid=$id&addmedia=1' title='Page + Media'><span class='dashicons dashicons-media-video'></span></a>&nbsp;";
                            echo "<a href='".str_replace("//","/", $_SERVER['REQUEST_URI'])."&action=export&pageid=$id' title='Page only' ><span class='dashicons dashicons-media-document'></span></a>&nbsp;";
                            echo "<a href='".str_replace("//","/", $_SERVER['REQUEST_URI'])."&action=export&layoutid=$lid' title='Layout only'><span class='dashicons dashicons-media-default'></span></a>";
                            echo "</div></td>";

                        }

                        echo "</tr>";
                    }
                }
?>
                </tbody>
            </table>
        </div>


<?php
    }


    static function getLayoutFromObject($layout) {

        $layout = $layout->to_array();

        if ($layout['post_status'] == 'publish') {
            $meta = get_post_meta($layout['ID'], 'mpat_content', true);
            $layout['meta'] = array('mpat_content' => $meta);
            return array( "page_layout" => $layout );
        }

        return null;

    }


    static function getAll() {
        $main_pages = array();

        $pages = get_pages();
        foreach ($pages as $page) {
            $page = $page->to_array();
            $meta = get_post_meta($page['ID'], 'mpat_content', true);

            $page['meta'] = array('mpat_content' => $meta);

            $o = array(
                "page" => $page,
            );

            //attach layout
            if (isset($meta["layoutId"]))
            {
                $layout = get_post( $meta["layoutId"] );
                if ($layout && $layout->post_type == "page_layout") {

                    if ($l = ImportExport::getLayoutFromObject($layout))
                        $o += $l;
                }
            }

            array_push($main_pages, $o);

        }

        return $main_pages;
    }

}

$ie = new ImportExport();
add_action("admin_menu", array(&$ie, "import_export_init"));
add_action('wp_loaded', array(&$ie,"import_export_onload"));

?>
