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

    public $message;

    function import_export_init() {
        add_menu_page('MPAT ImportExport', 'ImportExport', 'edit_pages', 'MPAT_ImportExport', array(&$this, 'display'), 'dashicons-download');
    }

    function import_export_onload() {

        if ( isset($_GET['page']) && strtolower($_GET['page']) === "mpat_importexport" )
        {

            $action = isset($_GET['action']) ? $_GET['action'] : "";

            switch ($action) {
                case 'export':

                    handle_export();

                    exit();
                    break;



                case 'import':

                    $this->message = handle_import();

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

        <div id="toolbarzone">

            <div id="importzone" class="iexport-toolbar">

                <div style="display: inline-block;">
                    <form action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=import'; ?>"
                          id="page-form" method="post" enctype="multipart/form-data" target="_top" >

                        <input id="page-fileinput" type="file" name="page" style="display: none;"  />
                        <button type="button" id="page-btn" style="display:inline;vertical-align:center;" title="Import new pages">Import<span class='dashicons
    dashicons-plus'></span></button>

                    </form>
                </div>
                <div style="display: inline-block;">
                    <form action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=import'; ?>"
                          id="layout-form" method="post" enctype="multipart/form-data" target="_top" >

                        <input id="layout-fileinput" type="file" name="layout" style="display: none;"  />
                        <button type="button" id="layout-btn" style="display:inline;vertical-align:center;" title="Import new layouts">Import Layouts<span class='dashicons dashicons-welcome-add-page'></span></button>

                    </form>
                </div>
            </div>

            <span>&nbsp;&nbsp;</span>

            <div id="exporttb" class="iexport-toolbar">
                <button onClick="fake_submit(this.id);" id="btn-exportall" title="Export all pages">Export ALL<span class='dashicons dashicons-media-archive'></span></button>
                <button onClick="fake_submit(this.id);" disabled id="btn-addmedia" title="Export selected pages and media">Exp. Selected w. Media<span class='dashicons dashicons-media-video'></span></button>
                <button onClick="fake_submit(this.id);" disabled id="btn-exportpages" title="Export selected pages">Exp. Selected<span class='dashicons dashicons-media-document'></span></button>
                <button onClick="fake_submit(this.id);" disabled id="btn-exportlayouts" title="Export layouts of selected pages">Exp. Layouts<span class='dashicons dashicons-media-default'></span></button>
            </div>

            <?php
            if ($this->message && !empty($this->message)) {
                echo "<div id='msgzone' >\n";
                echo $this->message ;
                echo "\n</div>\n";
            }
            ?>
        </div>

        <div id="exportzone">

            <form style="display: inline; margin: 0; padding: 0;" method="post" action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=export'; ?>" id="exportform" >

                <div id="exporttb" class="iexport-toolbar">
                    <button type="submit" hidden="1"  id="frm-btn-exportall" name="exportall" value="1" ></span></button>
                    <button type="submit" hidden="1"  id="frm-btn-addmedia" name="addmedia" value="1" ></button>
                    <button type="submit" hidden="1"  id="frm-btn-exportpages" name="exportpages" value="1" ></button>
                    <button type="submit" hidden="1"  id="frm-btn-exportlayouts" name="exportlayouts" value="1"></button>
                </div>

                <table class="iexport-table">
                    <thead>
                    <tr>
                        <td><input type="checkbox" onchange="checkAll(this);"></td>
                        <td>Page</td>
                        <td>Id</td>
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

                                echo "<td><input type='checkbox' name='pageid[]' value='$id'></td>\n";

                                echo "<td>" . $o['page']['post_title'] . "</td>";
                                echo "<td>$id</td>";

                                $lname = "";
                                if (isset($o['page_layout'])) {
                                    $lid = $o['page_layout']['ID'];
                                    $lname = $o['page_layout']['post_title'];

                                    echo "<td>" . $lname . "</td>\n";
                                    echo "<td><canvas id='canvas-$id' width='128' height='72' /></td>\n";

                                    $zones = $o['page_layout']['meta']['mpat_content']['layout'];
                                    $content = $o['page']['meta']['mpat_content']['content'];

                                    echo "<script>\n";
                                    echo "var ctx = window.getCtx('$id');\n";

                                    foreach ($zones as $z) {

                                        $type = reset($content[ $z['i'] ])['type'];
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

            </form>

        </div>



<?php
    }


    static function getLayoutFromObject($layout) {

        $layout = $layout->to_array();

        if ($layout['post_status'] == 'publish' || $layout['post_status'] == 'draft') {
            $meta = get_post_meta($layout['ID'], 'mpat_content', true);
            $layout['meta'] = array('mpat_content' => $meta);
            return array( "page_layout" => $layout );
        }

        return null;

    }


    static function getAll($ids=null) {
        $main_pages = array();

        if ($ids)
            $pages = get_pages( array('include' => $ids, 'post_status' => array('publish', 'draft')) );
        else
            $pages = get_pages( array('post_status' => array('publish', 'draft')) );

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
