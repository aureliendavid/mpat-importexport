<?php
/*
 * Plugin Name: MPAT ImportExport
 * Plugin URI: https://github.com/aureliendavid/mpat-importexport/
 * Description: Import and export MPAT pages and layout
 * Version: 1.0.beta
 * Author: Aurelien David
 * Author URI: https://github.com/aureliendavid/
 * License: GPL2
 * Text Domain: mpat-importexport
 * Domain Path: /languages
 */

namespace MPAT\ImportExport;

require "mpatie_export.php";
require "mpatie_import.php";

class ImportExport {

    public $message;

    function import_export_init() {
        load_plugin_textdomain('mpat-importexport', false, basename( dirname( __FILE__ ) ) . '/languages' );
        add_menu_page('MPAT ImportExport', __('ImportExport', 'mpat-importexport'), 'edit_pages', 'MPAT_ImportExport', array(&$this, 'display'), 'dashicons-download');
        
        // added option for gz compression
        add_option('mpatImportExport', '');
        register_setting('mpatImportExport-settings-group', 'mpatImportExport' );
    }

    function import_export_onload() {
        $zipped = get_option('mpatImportExport') == 'on';
        if ( isset($_GET['page']) && strtolower($_GET['page']) === "mpat_importexport" )
        {

            $action = isset($_GET['action']) ? $_GET['action'] : "";

            switch ($action) {
                case 'export':

                    handle_export($zipped);

                    exit();
                    break;



                case 'import':

                    $this->message = handle_import($zipped);

                    break;
                default:
                    break;
            }
        }

    }



    function display() {

        $zipped = "";
        if(get_option('mpatImportExport') == 'on'){
            $zipped="checked";
        }

        wp_enqueue_style('mpat-importexport', plugin_dir_url(__FILE__) . 'mpat_import_export.css');

?>

        <h2><?php _e('MPAT Importer/Exporter for','mpat-importexport'); ?> <?php echo bloginfo('name'); ?></h2>

        <script src="<?php echo plugin_dir_url(__FILE__); ?>mpat_import_export.js" > </script>

        <form method="post" action="./options.php">
        <?php settings_fields( 'mpatImportExport-settings-group' ); ?>
        <?php do_settings_sections( 'mpatImportExport-settings-group' ); ?>
        <table class="form-table" style="width: 300px; border-width: 1px;">
            <tr>
            <td>
                <td><?php _e('Use compression while import and export') ?></td>
                <td><input type="checkbox" name="mpatImportExport" <?php echo $zipped ?> /></td>
            </tr>
            <tr>
                <td colspan="2">
                    <?php submit_button(); ?>
                </td>
            </tr>     
            
        </table>
        
        </form>   
        <div id="toolbarzone">
        
            <div id="importzone" class="iexport-toolbar">

                <div style="display: inline-block;">
                    <form action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=import'; ?>"
                          id="page-form" method="post" enctype="multipart/form-data" target="_top" >

                        <input id="page-fileinput" type="file" name="page" style="display: none;"  />
                        <button type="button" id="page-btn"
                            style="display:inline;vertical-align:center;"
                            title="<?php _e('Import new pages', 'mpat-importexport'); ?>">
                            <?php _e('Import', 'mpat-importexport'); ?><span class='dashicons dashicons-plus'></span>
                        </button>

                    </form>
                </div>
                <div style="display: inline-block;">
                    <form action="<?php echo str_replace("//","/", $_SERVER['REQUEST_URI']) . '&action=import'; ?>"
                          id="layout-form" method="post" enctype="multipart/form-data" target="_top" >

                        <input id="layout-fileinput" type="file" name="layout" style="display: none;"  />
                        <button type="button" id="layout-btn" style="display:inline;vertical-align:center;"
                            title="<?php _e('Import new layouts', 'mpat-importexport'); ?>">
                            <?php _e('Import Layouts', 'mpat-importexport'); ?>
                            <span class='dashicons dashicons-welcome-add-page'></span>
                        </button>

                    </form>
                </div>
            </div>

            <span>&nbsp;&nbsp;</span>

            <div id="exporttb" class="iexport-toolbar">
                <button onClick="fake_submit(this.id);" id="btn-exportall"
                    title="<?php _e('Export all pages', 'mpat-importexport'); ?>">
                    <?php _e('Export ALL', 'mpat-importexport'); ?>
                    <span class='dashicons dashicons-media-archive'></span>
                </button>

                <!-- <button onClick="fake_submit(this.id);" disabled id="btn-addmedia"
                    title="<?php _e('Export selected pages and media', 'mpat-importexport'); ?>">
                    <?php _e('Exp. Selected w. Media', 'mpat-importexport'); ?>
                    <span class='dashicons dashicons-media-video'></span>
                </button> -->

                <button onClick="fake_submit(this.id);" disabled id="btn-exportpages"
                    title="<?php _e('Export selected pages', 'mpat-importexport'); ?>">
                    <?php _e('Exp. Selected', 'mpat-importexport'); ?>
                    <span class='dashicons dashicons-media-document'></span>
                </button>

                <button onClick="fake_submit(this.id);" disabled id="btn-exportlayouts"
                    title="<?php _e('Export layouts of selected pages', 'mpat-importexport'); ?>">
                    <?php _e('Exp. Layouts', 'mpat-importexport'); ?>
                    <span class='dashicons dashicons-media-default'></span>
                </button>
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
                    <!-- <button type="submit" hidden="1"  id="frm-btn-addmedia" name="addmedia" value="1" ></button> -->
                    <button type="submit" hidden="1"  id="frm-btn-exportpages" name="exportpages" value="1" ></button>
                    <button type="submit" hidden="1"  id="frm-btn-exportlayouts" name="exportlayouts" value="1"></button>
                </div>

                <details open>
                    <summary><?php _e('Page export settings', 'mpat-importexport'); ?></summary>
                    <br />
                    <input type='checkbox' name='chk_addmedia' id='chk_addmedia' checked>
                    <label for='chk_addmedia'><?php _e('Include local media', 'mpat-importexport'); ?></label>
                    <br />
                    <input type='checkbox' name='tl_options' id='tl_options'>
                    <label for='tl_options'><?php _e('Include timeline options (dsmcc, timeline_scenario)', 'mpat-importexport'); ?></label>
                    <br />
                    <input type='checkbox' name='custom_css' id='custom_css'>
                    <label for='custom_css'><?php _e('Include custom styles', 'mpat-importexport'); ?></label>
                </details>
                <br />

                <table class="iexport-table">
                    <thead>
                    <tr>
                        <td><input type="checkbox" onchange="checkAll(this);"></td>
                        <td><?php _e('Title', 'mpat-importexport'); ?></td>
                        <td><?php _e('Id', 'mpat-importexport'); ?></td>
                        <td><?php _e('Layout', 'mpat-importexport'); ?></td>
                        <td><?php _e('Map', 'mpat-importexport'); ?></td>
                        <!-- <td><?php _e('Export', 'mpat-importexport'); ?></td> -->
                    </tr>
                    </thead>
                    <tbody>
<?php
                        $all = ImportExport::getAll();
                        $first_model = false;

                        echo "<tr><td colspan='100'><b><?php e('Pages', 'mpat-importexport'); ?></b></td></tr>";

                        foreach ($all as $o)  {
                            if (isset($o['page'])) {

                                $id = $o['page']['ID'];

                                if (!$first_model) {
                                    if ($o['page']['post_type'] == 'page_model') {
                                        echo "<tr><td colspan='100'><b><?php _e('Page Models', 'mpat-importexport'); ?></b></td></tr>";
                                        $first_model = true;
                                    }
                                }


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


/*                                    echo "<td><div id='exportbtns'>";
                                    echo "<a href='".str_replace("//","/", $_SERVER['REQUEST_URI'])."&action=export&pageid=$id&addmedia=1' title='".__("Page + Media", "mpat-importexport") ."'><span class='dashicons dashicons-media-video'></span></a>&nbsp;";
                                    echo "<a href='".str_replace("//","/", $_SERVER['REQUEST_URI'])."&action=export&pageid=$id' title='".__("Page only","mpat-importexport")."'><span class='dashicons dashicons-media-document'></span></a>&nbsp;";
                                    echo "<a href='".str_replace("//","/", $_SERVER['REQUEST_URI'])."&action=export&layoutid=$lid' title='".__("Layout only","mpat-importexport").'><span class='dashicons dashicons-media-default'></span></a>";
                                    echo "</div></td>";*/

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

    static function getFullUrl($url) {

        // absolute url with same protocol
        if (substr($url, 0, 2) == "//") {
            $url = $_SERVER['REQUEST_SCHEME'] . $url;
        }
        // absolute url with same protocol/domain/port
        // maybe use SERVER_NAME / SERVER_PORT instead ? or WP_DOMAIN ?
        else if ( $url[0] == '/' ) {
            $url = $_SERVER['WP_HOME'] . $url;
        }
        // relative url from current site
        else if (!strpos($url, "://")) {
            $url = $_SERVER['WP_SITEURL'] . $url;
        }

        return $url;
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

    static function addPages(&$main_pages, &$pages) {

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

    }

    static function getAll($ids=null) {
        $main_pages = array();

        if ($ids) {
            $pages = get_pages( array('include' => $ids, 'post_status' => array('publish', 'draft')) );
            $models = get_posts( array('post_type' => 'page_model', 'include' => $ids, 'post_status' => array('publish', 'draft')) );
        }
        else {
            $pages = get_pages( array('post_status' => array('publish', 'draft')) );
            $models = get_posts( array('post_type' => 'page_model', 'post_status' => array('publish', 'draft')) );
        }


        ImportExport::addPages($main_pages, $pages);
        ImportExport::addPages($main_pages, $models);


        return $main_pages;
    }

}

$ie = new ImportExport();
add_action("admin_menu", array(&$ie, "import_export_init"));
add_action('wp_loaded', array(&$ie,"import_export_onload"));

?>
