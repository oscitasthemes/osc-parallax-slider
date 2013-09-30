<?php
/*
  Plugin Name: Easy Parallax Slider
  Plugin URI: http://www.oscitasthemes.com
  Description: Easy Parallax Slider provides layered slider feature.
  Version: 1.0.0
  Author: Oscitas Themes
  Author URI: http://www.oscitasthemes.com
  License: Under the GPL v2 or later
 */


define('OSC_PARALLAX_SLIDER_VERSION', '1.0');
define('OSC_PARALLAX_SLIDER_BASE_URL', plugins_url('',__FILE__));
define('OSC_PARALLAX_SLIDER_ASSETS_URL', OSC_PARALLAX_SLIDER_BASE_URL . '/assets/');
define('OSC_PARALLAX_SLIDER_BASE_DIR_LONG', dirname(__FILE__));
define('OSC_PARALLAX_SLIDER_INC_DIR', OSC_PARALLAX_SLIDER_BASE_DIR_LONG . '/inc/');
// include slider classes
require_once( OSC_PARALLAX_SLIDER_INC_DIR . 'slider/osc-parallax-slider.class.php' );
require_once( OSC_PARALLAX_SLIDER_INC_DIR . 'slider/osc-parallax-slider-content.class.php' );


// include slide classes
require_once( OSC_PARALLAX_SLIDER_INC_DIR . 'slide/osc-parallax-slider.class.php' );
require_once( OSC_PARALLAX_SLIDER_INC_DIR . 'slide/osc-parallax-slider.image.class.php' );

// include image helper
require_once( OSC_PARALLAX_SLIDER_INC_DIR . 'osc-parallax-slider.imagehelper.class.php' );

// include widget
require_once( OSC_PARALLAX_SLIDER_INC_DIR . 'osc-parallax-slider.widget.class.php' );

/**
 * Register the plugin.
 *
 * Display the administration panel, insert JavaScript etc.
 */
class OscParallaxsliderPlugin {

    /** Current Slider **/
    var $slider = null;

    /**
     * Constructor
     */
    public function __construct() {
        // create the admin menu/page
        add_action('admin_menu', array($this, 'register_admin_menu'), 9553);

        // register slider post type and taxonomy
        add_action('init', array($this, 'register_post_type' ));
        add_action('init', array($this, 'register_taxonomy' ));
        add_action('init', array($this, 'load_plugin_textdomain'));

        // register shortcodes
        add_shortcode('oscparallaxslider', array($this, 'register_shortcode'));
        add_shortcode('ml-slider', array($this, 'register_shortcode')); // backwards compatibility

        add_filter('media_upload_tabs', array($this,'custom_media_upload_tab_name'), 998);
        add_filter('media_view_strings', array($this, 'custom_media_uploader_tabs'), 5);

        $plugin = plugin_basename(__FILE__);

        $this->register_slide_types();
    }

    /**
     * Check our WordPress installation is compatible with Meta Slider
     */
    public function system_check(){
        if (!function_exists('wp_enqueue_media')) {
            echo '<div id="message" class="updated"><p><b>Warning</b> Osc Slider requires WordPress 3.5 or above. Please upgrade your WordPress installation.</p></div>';
        }

        if ((!extension_loaded('gd') || !function_exists('gd_info')) && (!extension_loaded( 'imagick' ) || !class_exists( 'Imagick' ) || !class_exists( 'ImagickPixel' ))) {
            echo '<div id="message" class="updated"><p><b>Warning</b> OSc Slider requires the GD or ImageMagick PHP extension. Please contact your hosting provider.</p></div>';
        }
    }

    /**
     * Add settings link on plugin page
     */
    public function upgrade_to_pro($links) {
        $links[] = '<a href="http://www.oscparallaxslider.com/upgrade" target="_blank">' . __("Go Pro", 'oscparallaxslider') . '</a>';
        return $links;
    }

    /**
     * Return the meta slider pro upgrade iFrame
     */
    public function oscparallaxslider_pro_tab() {
        return wp_iframe( array($this, 'iframe'));
    }

    /**
     * Media Manager iframe HTML
     */
    public function iframe() {
        wp_enqueue_style('oscparallaxslider-admin-styles', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/admin.css', false, OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_script('google-font-api', 'http://fonts.googleapis.com/css?family=PT+Sans:400,700');

        $link = apply_filters('oscparallaxslider_hoplink', 'http://www.oscitas.com//');
        $link .= '?utm_source=lite&utm_medium=more-slide-types&utm_campaign=pro';

        echo "<div class='oscparallaxslider'>";
        echo "<p style='text-align: center; font-size: 1.2em; margin-top: 50px;'>Get the Pro Addon pack to add support for: <b>Post Feed</b> Slides, <b>YouTube</b> Slides, <b>HTML</b> Slides & <b>Vimeo</b> Slides</p>";
        echo "<p style='text-align: center; font-size: 1.2em;'><b>NEW:</b> Animated HTML <b>Layer</b> Slides (with an awesome Drag & Drop editor!)</p>";
        echo "<p style='text-align: center; font-size: 1.2em;'><b>NEW:</b> Live Theme Editor!</p>";
        echo "<a class='probutton' href='{$link}' target='_blank'>Get <span class='logo'><strong>Osc</strong>Slider</span><span class='super'>Pro</span></a>";
        echo "<span class='subtext'>Opens in a new window</span>";
        echo "</div>";
    }

    /**
     * Register our slide types
     */
    private function register_slide_types() {
        $image = new OscParallaxImageSlide();
    }

    /**
     * Initialise translations
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('oscparallaxslider', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Update the tab options in the media manager
     */
    public function custom_media_uploader_tabs( $strings ) {
        //update strings
        if ((isset($_GET['page']) && $_GET['page'] == 'oscparallaxslider')) {
            $strings['insertMediaTitle'] = __("Image", 'oscparallaxslider');
            $strings['insertIntoPost'] = __("Add to slider", 'oscparallaxslider');

            // remove options
            if (isset($strings['createGalleryTitle'])) unset($strings['createGalleryTitle']);
            if (isset($strings['insertFromUrlTitle'])) unset($strings['insertFromUrlTitle']);
        }
        return $strings;
    }

    /**
     * Add extra tabs to the default wordpress Media Manager iframe
     *
     * @var array existing media manager tabs
     */
    public function custom_media_upload_tab_name( $tabs ) {
        // restrict our tab changes to the meta slider plugin page
        if ((isset($_GET['page']) && $_GET['page'] == 'oscparallaxslider') || isset($_GET['tab']) == 'oscparallaxslider_pro') {

            $newtabs = array(
                'oscparallaxslider_pro' => __("More Slide Types", 'oscparallaxslider')
            );

            if (isset($tabs['nextgen'])) unset($tabs['nextgen']);

            return array_merge( $tabs, $newtabs );
        }

        return $tabs;
    }

    /**
     * Rehister admin styles
     */
    public function register_admin_styles() {
        wp_enqueue_style('oscparallaxslider-admin-styles', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/admin.css', false, OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_style('oscparallaxslider-colorbox-styles', OSC_PARALLAX_SLIDER_ASSETS_URL . 'colorbox/colorbox.css', false, OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_style('oscparallaxslider-colorpicker', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/css/colorpicker.css', false, OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_style('oscparallaxslider-tipsy-styles', OSC_PARALLAX_SLIDER_ASSETS_URL . 'tipsy/tipsy.css', false, OSC_PARALLAX_SLIDER_VERSION);


        do_action('oscparallaxslider_register_admin_styles');
    }

    /**
     * Register admin JavaScript
     */
    public function register_admin_scripts() {
        if (wp_script_is('wp-auth-check', 'queue')) {
            // meta slider checks for active AJAX requests in order to show the spinner
            // .. but the auth-check runs an AJAX request every 15 seconds
            // deregister the script that displays the login panel if the user becomes logged
            // out at some point
            // todo: implement some more intelligent request checking
            wp_deregister_script('wp-auth-check');
            wp_register_script('wp-auth-check', null); // fix php notice
        }

        // media library dependencies
        wp_enqueue_media();

        // plugin dependencies
        wp_enqueue_script('jquery-ui-core', array('jquery'));
        wp_enqueue_script('jquery-ui-sortable', array('jquery', 'jquery-ui-core'));
        wp_enqueue_script('oscparallaxslider-colorbox', OSC_PARALLAX_SLIDER_ASSETS_URL . 'colorbox/jquery.colorbox-min.js', array('jquery'), OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_script('oscparallaxslider-tipsy', OSC_PARALLAX_SLIDER_ASSETS_URL . 'tipsy/jquery.tipsy.js', array('jquery'), OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_script('oscparallaxslider-colorpicker', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/js/colorpicker.js', array('jquery', 'jquery-ui-core'), OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_script('oscparallaxslider-cslider', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/js/jquery.cslider.js', array('jquery', 'jquery-ui-core'), OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_script('oscparallaxslider-admin-script', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/admin.js', array('jquery', 'oscparallaxslider-tipsy', 'media-upload'), OSC_PARALLAX_SLIDER_VERSION);
        wp_enqueue_script('oscparallaxslider-admin-addslide', OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/images/image.js', array('oscparallaxslider-admin-script'), OSC_PARALLAX_SLIDER_VERSION);

        // localise the JS
        wp_localize_script( 'oscparallaxslider-admin-script', 'oscparallaxslider', array(
            'url' => __("URL", 'oscparallaxslider'),
            'heading' => __("Heading", 'oscparallaxslider'),
            'content' => __("Content", 'oscparallaxslider'),
            'new_window' => __("New Window", 'oscparallaxslider'),
            'confirm' => __("Are you sure?", 'oscparallaxslider'),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'iframeurl' => OSC_PARALLAX_SLIDER_BASE_URL . '/preview.php',
            'useWithCaution' => __("Caution: This setting is for advanced developers only. If you're unsure, leave it checked.", 'oscparallaxslider')
        ));

        do_action('oscparallaxslider_register_admin_scripts');
    }

    /**
     * Add the menu page
     */
    public function register_admin_menu() {
        $title = apply_filters('oscparallaxslider_menu_title', "Osc Parallax Slider");

        $page = add_menu_page($title, $title, 'edit_others_posts', 'oscparallaxslider', array(
            $this, 'render_admin_page'
        ), OSC_PARALLAX_SLIDER_ASSETS_URL . 'oscparallaxslider/osc-icon.png', 9501);

        // ensure our JavaScript is only loaded on the Meta Slider admin page
        add_action('admin_print_scripts-' . $page, array($this, 'register_admin_scripts'));
        add_action('admin_print_styles-' . $page, array($this, 'register_admin_styles'));
    }


    /**
     * Register ML Slider post type
     */
    public function register_post_type() {
        register_post_type('ml-slider', array(
            'query_var' => false,
            'rewrite' => false
        ));
    }

    /**
     * Register taxonomy to store slider => slides relationship
     */
    public function register_taxonomy() {
        register_taxonomy( 'ml-slider', 'attachment', array(
            'hierarchical' => true,
            'public' => false,
            'query_var' => false,
            'rewrite' => false
        ));
    }

    /**
     * Shortcode used to display slideshow
     *
     * @return string HTML output of the shortcode
     */
    public function register_shortcode($atts) {
        extract(shortcode_atts(array('id' => null), $atts));

        if ($id == null) return;

        // we have an ID to work with
        $slider = get_post($id);

        // check the slider is published
        if ($slider->post_status != 'publish') return false;

        // lets go
        $this->set_slider($id);
        $this->slider->enqueue_scripts();

        return $this->slider->render_public_slides();
    }

    /**
     * Set the current slider
     */
    public function set_slider($id) {
        $type = 'flex';
        $this->slider = $this->create_slider($type, $id);
    }

    /**
     * Create a new slider based on the sliders type setting
     */
    private function create_slider($type, $id) {

        return new OscParallaxContentSlider($id);
    }

    /**
     * Handle slide uploads/changes
     */
    public function admin_process() {
        // default to the latest slider
        $slider_id = $this->find_slider('modified', 'DESC');

        // delete a slider
        if (isset($_GET['delete'])) {
            $this->delete_slider(intval($_GET['delete']));
            $slider_id = $this->find_slider('date', 'DESC');
        }

        // create a new slider
        if (isset($_GET['add'])) {
            $this->add_slider();
            $slider_id = $this->find_slider('date', 'DESC');
        }

        if (isset($_REQUEST['id'])) {
            $slider_id = $_REQUEST['id'];
        }

        $this->set_slider($slider_id);
    }

    /**
     * Create a new slider
     */
    private function add_slider() {
        $defaults = array();

        // if possible, take a copy of the last edited slider settings in place of default settings
        if ($last_modified = $this->find_slider('modified', 'DESC')) {
            $defaults = get_post_meta($last_modified, 'ml-slider_settings', true);
        }

        // insert the post
        $id = wp_insert_post(array(
            'post_title' => __("New Slider", 'oscparallaxslider'),
            'post_status' => 'publish',
            'post_type' => 'ml-slider'
        ));

        // use the default settings if we can't find anything more suitable.
        if (empty($defaults)) {
            $slider = new OscParallaxslider($id);
            $defaults = $slider->get_default_parameters();
        }

        // insert the post meta
        add_post_meta($id, 'ml-slider_settings', $defaults, true);

        // create the taxonomy term, the term is the ID of the slider itself
        wp_insert_term($id, 'ml-slider');
    }

    /**
     * Delete a slider (send it to trash)
     */
    private function delete_slider($id) {
        $slide = array(
            'ID' => $id,
            'post_status' => 'trash'
        );

        wp_update_post($slide);
    }

    /**
     * Find a single slider ID. For example, last edited, or first published.
     *
     * @param string $orderby field to order.
     * @param string $order direction (ASC or DESC).
     * @return int slider ID.
     */
    private function find_slider($orderby, $order) {
        $args = array(
            'force_no_custom_order' => true,
            'post_type' => 'ml-slider',
            'num_posts' => 1,
            'post_status' => 'publish',
            'orderby' => $orderby,
            'order' => $order
        );

        $the_query = new WP_Query($args);

        while ($the_query->have_posts()) {
            $the_query->the_post();
            return $the_query->post->ID;
        }

        return false;
    }


    /**
     * Get sliders. Returns a nicely formatted array of currently
     * published sliders.
     *
     * @return array all published sliders
     */
    private function all_meta_sliders() {
        $sliders = false;

        // list the tabs
        $args = array(
            'post_type' => 'ml-slider',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => -1
        );

        $the_query = new WP_Query($args);

        while ($the_query->have_posts()) {
            $the_query->the_post();
            $active = $this->slider->id == $the_query->post->ID ? true : false;

            $sliders[] = array(
                'active' => $active,
                'title' => get_the_title(),
                'id' => $the_query->post->ID
            );
        }

        return $sliders;
    }

    public function get_library_details($version, $responsive, $size, $mobile) {
        $details  = __("Version", 'oscparallaxslider') . ": " . $version . "<br />";
        $details .= __("Responsive", 'oscparallaxslider') . ": ";
        $details .= $responsive ? __("Yes", 'oscparallaxslider') : __("No", 'oscparallaxslider');
        $details .= "<br />";
        $details .= __("Size", 'oscparallaxslider') . ": " . $size . __("kb", 'oscparallaxslider') ."<br />";
        $details .= __("Mobile Friendly", 'oscparallaxslider') . ": ";
        $details .= $mobile ? __("Yes", 'oscparallaxslider') : __("No", 'oscparallaxslider') . "<br />";

        return $details;
    }

    /**
     * Render the admin page (tabs, slides, settings)
     */
    public function render_admin_page() {
        $this->admin_process();
        $font_family_array=array('Georgia, serif','Palatino Linotype, Book Antiqua, Palatino','Times New Roman','Arial, Helvetica','Arial Black, Gadget','Comic Sans MS, cursive','Impact, Charcoal','Lucida Sans Unicode','Tahoma, Geneva','Trebuchet MS','Verdana, Geneva','Courier New, Courier, monospace','Lucida Console, Monaco');
        $font_style_array=array('bold','italic','underline');
        ?>

        <script type='text/javascript'>
            var oscparallaxslider_slider_id = <?php echo $this->slider->id; ?>;
        </script>

        <div class="wrap oscparallaxslider">
        <form accept-charset="UTF-8" action="?page=oscparallaxslider&id=<?php echo $this->slider->id ?>" method="post">

        <h2 class="nav-tab-wrapper">
            <?php
            if ($tabs = $this->all_meta_sliders()) {
                foreach ($tabs as $tab) {
                    if ($tab['active']) {
                        echo "<div class='nav-tab nav-tab-active'><input type='text' name='title'  value='" . $tab['title'] . "' onkeypress='this.style.width = ((this.value.length + 1) * 9) + \"px\"' /></div>";
                    } else {
                        echo "<a href='?page=oscparallaxslider&id={$tab['id']}' class='nav-tab'>" . $tab['title'] . "</a>";
                    }
                }
            }
            ?>

            <a href="?page=oscparallaxslider&add=true" id="create_new_tab" class="nav-tab">+</a>
        </h2>

        <?php
        if (!$this->slider->id) {
            return;
        }
        ?>

        <div class="left">
            <table class="widefat sortable">
                <thead>
                <tr>
                    <th style="width: 100px;">
                        <?php _e("Slides", 'oscparallaxslider') ?>
                    </th>
                    <th>
                        <a href='#' class='button alignright add-slide' data-editor='content' title='<?php _e("Add Slide", 'oscparallaxslider') ?>'>
                            <span class='wp-media-buttons-icon'></span> <?php _e("Add Slide", 'oscparallaxslider') ?>
                        </a>
                    </th>
                </tr>
                </thead>

                <tbody>
                <?php
                $this->slider->render_admin_slides();
                ?>
                </tbody>
            </table>
        </div>

        <div class='right'>
        <table class="widefat settings oscitas-slider-settings-tbl" cellspacing="3" cellpadding="3">
        <thead>
        <tr>
            <th colspan='2'>
                <span class='configuration'><?php _e("Settings", 'oscparallaxslider') ?></span>
                <input class='alignright button button-primary' type='submit' name='save' id='save' value='<?php _e("Save", 'oscparallaxslider') ?>' />
                <input class='alignright button button-primary' type='submit' name='preview' id='preview' value='<?php _e("Save & Preview", 'oscparallaxslider') ?>' id='quickview' data-slider_id='<?php echo $this->slider->id ?>' data-slider_width='<?php echo $this->slider->get_setting('width') ?>' data-slider_height='<?php echo $this->slider->get_setting('height') ?>' />
                <span class="spinner"></span>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td colspan='2' class='highlight'><?php _e("Slider Settings", 'oscparallaxslider') ?></td>
        </tr>

        <tr>
            <td width='50%' class='tipsy-tooltip' title="<?php _e("Set the default background image for slider.", 'oscparallaxslider') ?>">
                <?php _e("Background", 'oscparallaxslider') ?>
                <input class="select-slider" id='flex' rel='flex' type='hidden' name="settings[type]" value='flex' />
            </td>
            <td width='50%'>
                <div id="bg-preview" class="bg-preview" style="width: 130px; float: left;">
                    <?php
                    if($this->slider->id && get_post_meta($this->slider->id, 'ml-slider_bg', true)){
                        $bg_url=get_post_meta($this->slider->id, 'ml-slider_bg', true);
                    }else{
                        $bg_url=OSC_PARALLAX_SLIDER_ASSETS_URL.'oscparallaxslider/images/waves.gif';
                    }
                    ?>
                    <img src="<?php echo $bg_url; ?>" width="100" height="50" />
                </div>
                <a href='#' class='button alignright add-bg' data-editor='content' title='<?php _e("Change", 'oscparallaxslider') ?>' style="margin-top: 14px;">
                    <span class='wp-media-buttons-icon'></span> <?php _e("Change", 'oscparallaxslider') ?>
                </a>
            </td>
        </tr>

        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set the initial size for the slider (width x height)", 'oscparallaxslider') ?>">
                <?php _e("Size", 'oscparallaxslider') ?>
            </td>
            <td>
                Width:<input type='number' min='1' max='100' step='1' size='3' class="width tipsytop" title='<?php _e("Width", 'oscparallaxslider') ?>' name="settings[slider_width]" value='<?php echo ($this->slider->get_setting('slider_width')!= 'false' ? $this->slider->get_setting('slider_width') : 0) ?>' />px<br/>
                Height: <input type='number' min='1' max='100' step='1' size='3' class="height tipsytop" title='<?php _e("Height", 'oscparallaxslider') ?>' name="settings[slider_height]" value='<?php echo ($this->slider->get_setting('slider_height')!= 'false' ? $this->slider->get_setting('slider_height') : 0) ?>' />px
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Show slide navigation row", 'oscparallaxslider') ?>">
                <?php _e("Controls", 'oscparallaxslider') ?>
            </td>
            <td>
                <label class='option' ><input type='checkbox' name="settings[links]" <?php if ($this->slider->get_setting('links') == 'true') echo 'checked=checked' ?> /><?php _e("Pager", 'oscparallaxslider') ?></label>
                <label class='option coin' ><input type='checkbox' name="settings[navigation]" <?php if ($this->slider->get_setting('navigation') == 'true') echo 'checked=checked' ?> /><?php _e("Navigation", 'oscparallaxslider') ?></label>
            </td>
        </tr>

        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Start the slideshow on page load", 'oscparallaxslider') ?>">
                <?php _e("Auto play", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option' type='checkbox' name="settings[autoplay]" <?php if ($this->slider->get_setting('autoplay') == 'true') echo 'checked=checked' ?> />
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("(parallax effect) when sliding", 'oscparallaxslider') ?>">
                <?php _e("Bg position increment", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option' type='number' min='1' max='100' step='1' name="settings[bg_position_increment]" value="<?php echo $this->slider->get_setting('bg_position_increment'); ?>" size="3" />px
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Time between transitions", 'oscparallaxslider') ?>">
                <?php _e("Interval", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option' type='number' min='0.5' max='100' step='0.5' name="settings[interval]" value="<?php echo $this->slider->get_setting('interval'); ?>" size="3" />Sec
            </td>
        </tr>
        <!-- slide heading -->
        <tr>
            <td colspan='2' class='highlight'><?php _e("Slide Heading Settings", 'oscparallaxslider') ?></td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide heading font size", 'oscparallaxslider') ?>">
                <?php _e("Font Size", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option' type='number' min='1' max='100' step='1' name="settings[heading_font_size]" value="<?php echo $this->slider->get_setting('heading_font_size'); ?>" size="3" />px
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide heading font Family", 'oscparallaxslider') ?>">
                <?php _e("Font Family", 'oscparallaxslider') ?>
            </td>
            <td>
                <select class='option' name="settings[heading_font_family]" >
                    <?php foreach($font_family_array as $heading_font_family_value){
                        echo '<option value="'.$heading_font_family_value.'" '.($this->slider->get_setting('heading_font_family')==$heading_font_family_value?'selected="selected"':'').'>'.$heading_font_family_value.'</option>';
                    }?>

                </select>
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide heading font Style", 'oscparallaxslider') ?>">
                <?php _e("Font Style", 'oscparallaxslider') ?>
            </td>
            <td>
                <select class='option' name="settings[heading_font_style]" >
                    <option value=''></option>
                    <?php foreach($font_style_array as $heading_font_style_value){
                        echo '<option value="'.$heading_font_style_value.'" '.($this->slider->get_setting('heading_font_style')==$heading_font_style_value?'selected="selected"':'').'>'.$heading_font_style_value.'</option>';
                    }?>
                </select>
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide heading font color", 'oscparallaxslider') ?>">
                <?php _e("Font Color", 'oscparallaxslider') ?>
            </td>
            <td>
                <div id="heading_font_color" class="colorSelector">
                    <div class="select_color" id="heading-color-select" style="background-color: <?php echo $this->slider->get_setting('heading_font_color'); ?>"></div>
                    <input  class='option' type='hidden' name="settings[heading_font_color]" value="<?php echo $this->slider->get_setting('heading_font_color'); ?>" size="3" css-prop="color" />
                </div>
            </td>
        </tr>
        <!--slide content -->

        <tr>
            <td colspan='2' class='highlight'><?php _e("Slide Content Settings", 'oscparallaxslider') ?></td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide content font size", 'oscparallaxslider') ?>">
                <?php _e("Font Size", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option' type='number' min='1' max='100' step='1' name="settings[content_font_size]" value="<?php echo $this->slider->get_setting('content_font_size'); ?>" size="3" />px
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide content font line height", 'oscparallaxslider') ?>">
                <?php _e("Line Height", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option' type='number' min='1' max='100' step='1' name="settings[content_font_line_height]" value="<?php echo $this->slider->get_setting('content_font_line_height'); ?>" size="3" />px
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide content font Family", 'oscparallaxslider') ?>">
                <?php _e("Font Family", 'oscparallaxslider') ?>
            </td>
            <td>
                <select class='option' name="settings[content_font_family]" >
                    <?php foreach($font_family_array as $content_font_family_value){
                        echo '<option value="'.$content_font_family_value.'" '.($this->slider->get_setting('content_font_family')==$content_font_family_value?'selected="selected"':'').'>'.$content_font_family_value.'</option>';
                    }?>
                </select>

            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide content font style", 'oscparallaxslider') ?>">
                <?php _e("Font style", 'oscparallaxslider') ?>
            </td>
            <td>
                <select class='option' name="settings[content_font_style]" >
                    <option value=''></option>
                    <?php foreach($font_style_array as $content_font_style_value){
                        echo '<option value="'.$content_font_style_value.'" '.($this->slider->get_setting('content_font_style')==$content_font_style_value?'selected="selected"':'').'>'.$content_font_style_value.'</option>';
                    }?>
                </select>
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide content font color", 'oscparallaxslider') ?>">
                <?php _e("Font Color", 'oscparallaxslider') ?>
            </td>
            <td>
                <div id="content_font_color" class="colorSelector">
                    <div class="select_color" id="content-color-select" style="background-color: <?php echo $this->slider->get_setting('content_font_color'); ?>"></div>
                <input  class='option color' type='hidden' name="settings[content_font_color]" value="<?php echo $this->slider->get_setting('content_font_color'); ?>" size="3" css-prop="color"   />
                </div>
            </td>
        </tr>
        <!--slide read more url -->
        <tr>
            <td colspan='2' class='highlight'><?php _e("Slide Read More Url Settings", 'oscparallaxslider') ?></td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide read more font size", 'oscparallaxslider') ?>">
                <?php _e("Font Size", 'oscparallaxslider') ?>
            </td>
            <td>
                <input class='option'  type='number' min='1' max='100' step='1' name="settings[readmore_font_size]" value="<?php echo $this->slider->get_setting('readmore_font_size'); ?>" size="3" />px
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide read more font Family", 'oscparallaxslider') ?>">
                <?php _e("Font Family", 'oscparallaxslider') ?>
            </td>
            <td>
                <select class='option' name="settings[readmore_font_family]" >
                    <?php foreach($font_family_array as $readmore_font_family_value){
                        echo '<option value="'.$readmore_font_family_value.'" '.($this->slider->get_setting('readmore_font_family')==$readmore_font_family_value?'selected="selected"':'').'>'.$readmore_font_family_value.'</option>';
                    }?>
                </select>
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide read more font style", 'oscparallaxslider') ?>">
                <?php _e("Font style", 'oscparallaxslider') ?>
            </td>
            <td>
                <select class='option' name="settings[readmore_font_style]" >
                    <option value=''></option>
                    <?php foreach($font_style_array as $readmore_font_style_value){
                        echo '<option value="'.$readmore_font_style_value.'" '.($this->slider->get_setting('readmore_font_style')==$readmore_font_style_value?'selected="selected"':'').'>'.$readmore_font_style_value.'</option>';
                    }?>
                </select>
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set slide read more font color", 'oscparallaxslider') ?>">
                <?php _e("Font Color", 'oscparallaxslider') ?>
            </td>
            <td>
                <div id="readmore_font_color" class="colorSelector">
                <div class="select_color" id="readmore-color-select" style="background-color: <?php echo $this->slider->get_setting('readmore_font_color'); ?>"></div>
                <input  class='option color' type='hidden' name="settings[readmore_font_color]" value="<?php echo $this->slider->get_setting('readmore_font_color'); ?>" size="3" css-prop="color"  />
                </div>
            </td>
        </tr>
        <tr>
            <td colspan='2' class='highlight'><?php _e("Slide Image Settings", 'oscparallaxslider') ?></td>
        </tr>
        <!--developer tools -->
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Specify the top position of the slides images (in %age)", 'oscparallaxslider') ?>">
                <?php _e("Top Position", 'oscparallaxslider') ?>
            </td>
            <td>
                <input type='number' min='1' max='100' step='1' name="settings[topPer]" value='<?php if ($this->slider->get_setting('topPer') != 'false') echo $this->slider->get_setting('topPer') ?>' />%
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Set the initial size for the slides images (width x height)", 'oscparallaxslider') ?>">
                <?php _e("Size", 'oscparallaxslider') ?>
            </td>
            <td>
                Width: <input type='number' min='1' max='100' step='1' size='3' class="width tipsytop" title='<?php _e("Width", 'oscparallaxslider') ?>' name="settings[width]" value='<?php echo ($this->slider->get_setting('width') != 'false' ? $this->slider->get_setting('width') : '') ?>' />px <br />
                Height: <input type='number' min='1' max='100' step='1' size='3' class="height tipsytop" title='<?php _e("Height", 'oscparallaxslider') ?>' name="settings[height]" value='<?php echo ($this->slider->get_setting('height') != 'false' ? $this->slider->get_setting('height') : '') ?>' />px
            </td>
        </tr>
        <tr>
            <td colspan='2' class='highlight'><?php _e("Developer Options", 'oscparallaxslider') ?></td>
        </tr>
        <!--developer tools -->
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Specify any custom CSS Classes you would like to be added to the slider wrapper", 'oscparallaxslider') ?>">
                <?php _e("CSS classes", 'oscparallaxslider') ?>
            </td>
            <td>
                <input type='text' name="settings[cssClass]" value='<?php if ($this->slider->get_setting('cssClass') != 'false') echo $this->slider->get_setting('cssClass') ?>' />
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Uncheck this is you would like to include your own CSS", 'oscparallaxslider') ?>">
                <?php _e("Print CSS", 'oscparallaxslider') ?>
            </td>
            <td>
                <input type='checkbox' class='useWithCaution' name="settings[printCss]" <?php if ($this->slider->get_setting('printCss') == 'true') echo 'checked=checked' ?> />
            </td>
        </tr>
        <tr>
            <td class='tipsy-tooltip' title="<?php _e("Uncheck this is you would like to include your own Javascript", 'oscparallaxslider') ?>">
                <?php _e("Print JS", 'oscparallaxslider') ?>
            </td>
            <td>
                <input type='checkbox' class='useWithCaution' name="settings[printJs]" <?php if ($this->slider->get_setting('printJs') == 'true') echo 'checked=checked' ?> />
            </td>
        </tr>
        <tr>
            <td colspan='2'>
                <a class='alignright delete-slider button-secondary confirm' href="?page=oscparallaxslider&delete=<?php echo $this->slider->id ?>"><?php _e("Delete Slider", 'oscparallaxslider') ?></a>
            </td>
        </tr>
        </tbody>
        </table>

        <table class="widefat shortcode">
            <thead>
            <tr>
                <th><?php _e("Usage", 'oscparallaxslider') ?></th>
            </tr>
            </thead>

            <tbody>
            <tr>
                <td class='highlight'><?php _e("Shortcode", 'oscparallaxslider') ?></td>
            </tr>
            <tr>
                <td><input readonly='readonly' type='text' value='[oscparallaxslider id=<?php echo $this->slider->id ?>]' /></td>
            </tr>
            <tr>
                <td class='highlight'><?php _e("Template Include", 'oscparallaxslider') ?></td>
            </tr>
            <tr>
                <td><input readonly='readonly' type='text' value='&lt;?php echo do_shortcode("[oscparallaxslider id=<?php echo $this->slider->id ?>]"); ?>' /></td>
            </tr>
            </tbody>

        </table>
        </div>
        </form>
        </div>
    <?php
    }
}

$oscparallaxslider = new OscParallaxsliderPlugin();

