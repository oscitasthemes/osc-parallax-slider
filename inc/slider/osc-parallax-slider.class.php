<?php
/**
 * Generic Slider super class. Extended by library specific classes.
 *
 * This class handles all slider related functionality, including saving settings and outputting
 * the slider HTML (front end and back end)
 */
class OscParallaxslider {

    public $id = 0; // slider ID
    public $identifier = 0; // unique identifier
    public $slides = array(); //slides belonging to this slider
    public $settings = array(); // slider settings

    /**
     * Constructor
     */
    public function __construct($id) {
        $this->id = $id;
        $this->settings = $this->get_settings();
        $this->identifier = 'oscparallaxslider_' . $this->id;
        $this->save();
        $this->populate_slides();

        add_filter('oscparallaxslider_css', array($this, 'get_slider_css'), 10, 3);
    }

    /**
     * Return the unique identifier for the slider (used to avoid javascript conflicts)
     *
     * @return string unique identifier for slider
     */
    protected function get_identifier() {
        return $this->identifier;
    }

    /**
     * Get settings for the current slider
     *
     * @return array slider settings
     */
    private function get_settings() {
        $settings = get_post_meta($this->id, 'ml-slider_settings', true);

        if (is_array($settings) &&
            isset($settings['type']) &&
            in_array($settings['type'], array('flex', 'coin', 'nivo', 'responsive')))
        {
            return $settings;
        } else {
            return $this->get_default_parameters();
        }
    }

    /**
     * Return an individual setting
     *
     * @param string $name Name of the setting
     * @return string setting value or 'false'
     */
    public function get_setting($name) {
        if (!isset($this->settings[$name])) {
            $defaults = $this->get_default_parameters();

            if (isset($defaults[$name])) {
                return $defaults[$name] ? $defaults[$name] : 'false';
            }
        } else {
            if (strlen($this->settings[$name]) > 0) {
                return $this->settings[$name];
            }
        }

        return 'false';
    }

    /**
     * Get the slider libary parameters, this lists all possible parameters and their
     * default values. Slider subclasses override this and disable/rename parameters
     * appropriately.
     *
     * @return string javascript options
     */
    public function get_default_parameters() {
        $params = array(
            'cssClass' => '',
            'printCss' => true,
            'printJs' => true,
            'slider_width' => 565,
            'slider_height' => 290,
            'width' => 300,
            'height' => 290,
            'navigation' => true,
            'interval' => 4000,
            'autoplay' => true,
            'bg_position_increment'=>50,
            'content_font_line_height'=>20,
            'content_font_size'=>14,
            'heading_font_size'=>22,
            'readmore_font_size'=>16,
            'content_font_family'=>'Times New Roman',
            'heading_font_family'=>'Arial, Helvetica',
            'readmore_font_family'=>'Arial Black, Gadget',
            'content_font_style'=>'italic',
            'heading_font_style'=>'bold',
            'readmore_font_style'=>'italic',
            'content_font_color'=>'#916C05',
            'heading_font_color'=>'#ffffff',
            'readmore_font_color'=>'#ffffff',
            'topPer'=>12
        );

        return $params;
    }

    /**
     * Save the slider details and initiate the update of all slides associated with slider.
     */
    private function save() {
        if (!is_admin()) {
            return;
        }
        // make changes to slider
        if (isset($_POST['settings'])) {
            $this->update_settings($_POST['settings']);
        }
        if (isset($_POST['title'])) {
            $this->update_title($_POST['title']);
        }
        if (isset($_GET['deleteSlide'])) {
            $this->delete_slide(intval($_GET['deleteSlide']));
        }

        // make changes to slides
        if (isset($_POST['attachment'])) {
            $this->update_slides($_POST['attachment']);
        }
    }

    /**
     * Return slides for the current slider
     *
     * @return array collection of slides belonging to the current slider
     */
    private function populate_slides() {
        $slides = array();

        $args = array(
            'force_no_custom_order' => true,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'ml-slider',
                    'field' => 'slug',
                    'terms' => $this->id
                )
            )
        );

        $query = new WP_Query($args);

        $slides = array();

        while ($query->have_posts()) {
            $query->next_post();

            $type = get_post_meta($query->post->ID, 'ml-slider_type', true);
            $type = $type ? $type : 'image'; // backwards compatibility, fall back to 'image'

            if (has_filter("oscparallaxslider_get_{$type}_slide")) {
                $return = apply_filters("oscparallaxslider_get_{$type}_slide", $query->post->ID, $this->id);

                if (is_array($return)) {
                    $slides = array_merge($slides, $return);
                } else {
                    $slides[] = $return;
                }
            }
        }

        // apply random setting
        if ($this->get_setting('random') == 'true' && !is_admin()) {
            shuffle($slides);
        }

        $this->slides = $slides;

        return $this->slides;
    }

    /**
     * Render each slide belonging to the slider out to the screen
     */
    public function render_admin_slides() {
        foreach($this->slides as $slide) {
            echo $slide;
        }
    }

    /**
     * Output the HTML and Javascript for this slider
     *
     * @return string HTML & Javascrpt
     */
    public function render_public_slides() {
        $class = "oscparallaxslider oscparallaxslider-{$this->get_setting('type')} oscparallaxslider-{$this->id} ml-slider";

        // apply the css class setting
        if ($this->get_setting('cssClass') != 'false') {
            $class .= " " . $this->get_setting('cssClass');
        }

        // handle any custom classes
        $class = apply_filters('oscparallaxslider_css_classes', $class, $this->id, $this->settings);

        // carousels are always 100% wide
        if ($this->get_setting('carouselMode') != 'true' && $this->get_setting('slider_width') != 'false' && $this->get_setting('slider_width') != 0) {
            $style = "max-width: {$this->get_setting('slider_width')}px; max-height:{$this->get_setting('slider_height')}px;";
        } else {
            $style = "width: 100%;";
        }

        // center align the slideshow
        if ($this->get_setting('center') != 'false') {
            $style .= " margin: 0 auto;";
        }

        // build the HTML
        $html  = "\n<!--meta slider-->";
        $html .= "\n<div style='{$style}' class='{$class}'>";
        $html .= "\n    " . $this->get_inline_css();
        $html .= "\n    <div id='da-slider-oscparallaxslider_{$this->id}' class='da-slider'>";
        $html .= "\n        " . $this->get_html();
        if($this->get_setting('navigation')=='true'){
            $html .= "\n   <nav class='da-arrows'>
					<span class='da-arrows-prev'></span>
					<span class='da-arrows-next'></span>
				</nav> \n";
        }
        $html .= "\n  </div>";
        $html .= $this->get_inline_javascript();
        $html .= "\n</div>";
        $html .= "\n<!--//meta slider-->";

        return $html;
    }

    /**
     * Return the Javascript to kick off the slider. Code is wrapped in a timer
     * to allow for themes that load jQuery at the bottom of the page.
     *
     * Delay execution of slider code until jQuery is ready (supports themes where
     * jQuery is loaded at the bottom of the page)
     *
     * @return string javascript
     */
    private function get_inline_javascript() {
        $identifier = $this->identifier;
        $type = $this->get_setting('type');
        $options='';
        if($this->get_setting('autoplay')=='true' || $this->get_setting('autoplay')=='on'){
            $options .='autoplay:true,';
        }
        if($this->get_setting('interval')){
            $options .='interval:'.($this->get_setting('interval')*1000);
        }
        if($this->get_setting('bg_position_increment')){
            $options .=',bgincrement:'.$this->get_setting('bg_position_increment');
        }
        $custom_js = apply_filters("oscparallaxslider_{$type}_slider_javascript", "", $this->id);

        $script  = "\n    <script type='text/javascript'>";
        $script .= "\n        var da_slider_" . $identifier . " = function($) {";
        $script .= "\n            jQuery('#da-slider-" . $identifier . "')." . $this->js_function . "({ $options";
        //$script .= "\n                " . $this->get_javascript_parameters();
        $script .= "\n            });";
        if (strlen ($custom_js)) {
            $script .= "\n            {$custom_js}";
        }
        $script .= "\n        };";
        $script .= "\n        var timer_" . $identifier . " = function() {";
        $script .= "\n            var slider = !window.jQuery ? window.setTimeout(timer_{$identifier}, 100) : !jQuery.isReady ? window.setTimeout(timer_{$identifier}, 100) : da_slider_{$identifier}(window.jQuery);";
        $script .= "\n        };";
        $script .= "\n        timer_" . $identifier . "();";
        $script .= "\n    </script>";

        return $script;
    }

    /**
     * Build the javascript parameter arguments for the slider.
     *
     * @return string parameters
     */
    private function get_javascript_parameters() {
        $options = array();

        // construct an array of all parameters
        foreach ($this->get_default_parameters() as $name => $default) {
            if ($param = $this->get_param($name)) {
                $val = $this->get_setting($name);

                if (gettype($default) == 'string') {
                    $options[$param] = '"' . $val . '"';
                } else {
                    $options[$param] = $val;
                }
            }
        }

        // deal with any customised parameters
        $type = $this->get_setting('type');

        if (has_filter("oscparallaxslider_{$type}_slider_parameters")) {
            $options = apply_filters("oscparallaxslider_{$type}_slider_parameters", $options, $this->id);
        }

        // create key:value strings
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $pairs[] = "{$key}: function() {\n                "
                    . implode("\n                ", $value)
                    . "\n            }";
            } else {
                $pairs[] = "{$key}:{$value}";
            }
        }

        return implode(",\n                ", $pairs);
    }

    /**
     * Apply any custom inline styling
     *
     * @return string
     */
    private function get_inline_css() {
        if (has_filter("oscparallaxslider_css")) {
            $css = apply_filters("oscparallaxslider_css", "", $this->settings, $this->id);
            $scoped = ' scoped';

            if (isset($_SERVER['HTTP_USER_AGENT'])){
                $agent = $_SERVER['HTTP_USER_AGENT'];
                if (strlen(strstr($agent,"Firefox")) > 0 ){
                    $scoped = '';
                }
            }
            $bg_img=OSC_PARALLAX_SLIDER_ASSETS_URL.'oscparallaxslider/images/waves.gif';
            $border_top='border-top: 8px solid #efc34a';
            $border_bottom='border-bottom: 8px solid #efc34a;';
            $url= get_post_meta($this->id, 'ml-slider_bg', true);
            $height='height:390px;';
            //echo $this->get_setting('slider_height');
            if($this->get_setting('slider_height')){
                $height='height:'.$this->get_setting('slider_height').'px;';
            }
            if($url){
                $bg_img=$url;
                $border_top='';
                $border_bottom='';
            }
            $bottom_navigations='';
            if ($this->get_setting('links') != 'true') {
                $bottom_navigations='display:none;';
            }
            $heading_font='text-decoration:none;';
            $content_font='text-decoration:none;';
            $readmore_font='text-decoration:none;';

            $heading_size='font-size:'.$this->get_setting('heading_font_size').'px;';
            $heading_family='font-family:'.$this->get_setting('heading_font_family').';';
            $heading_color='color:'.$this->get_setting('heading_font_color').';';
            if($this->get_setting('heading_font_style')=='italic'){
                $heading_font='font-style:'.$this->get_setting('heading_font_style').';';
            }
            if($this->get_setting('heading_font_style')=='underline'){
                $heading_font='text-decoration:'.$this->get_setting('heading_font_style').';';
            }
            if($this->get_setting('heading_font_style')=='bold'){
                $heading_font='font-weight:'.$this->get_setting('heading_font_style').';';
            }

            $content_size='font-size:'.$this->get_setting('content_font_size').'px;';
            $content_family='font-family:'.$this->get_setting('content_font_family').';';
            $content_color='color:'.$this->get_setting('content_font_color').';';
            if($this->get_setting('content_font_style')=='italic'){
            $content_font='font-style:'.$this->get_setting('content_font_style').';';
            }
            if($this->get_setting('content_font_style')=='bold'){
            $content_font='font-weight:'.$this->get_setting('content_font_style').';';
            }
            if($this->get_setting('content_font_style')=='underline'){
            $content_font='text-decoration:'.$this->get_setting('content_font_style').';';
            }

            $readmore_size='font-size:'.$this->get_setting('readmore_font_size').'px;';
            $readmore_family='font-family:'.$this->get_setting('readmore_font_family').';';
            $readmore_color='color:'.$this->get_setting('readmore_font_color').';';
            if($this->get_setting('readmore_font_style')=='italic'){
            $readmore_font='font-style:'.$this->get_setting('readmore_font_style').'; text-decoration:none;';
            }
            if($this->get_setting('readmore_font_style')=='bold'){
            $readmore_font='font-weight:'.$this->get_setting('readmore_font_style').'; text-decoration:none;';
            }
            if($this->get_setting('readmore_font_style')=='underline'){
            $readmore_font='text-decoration:'.$this->get_setting('readmore_font_style').';';
            }
            $line_height='line-height:20px';
            if($this->get_setting('content_font_line_height')!=='false'){
                $line_height='line-height:'.$this->get_setting('content_font_line_height').'px;';
            }
            $css .=<<<EOF
                    .da-slider{
                    background: transparent url({$bg_img}) repeat 0% 0%;
                    {$border_top}
                    {$border_bottom}
                    {$height}
                    }
                    .da-dots{
                    {$bottom_navigations}
                    }
                    .da-slide h2{
                       {$heading_size}
                       {$heading_family}
                       {$heading_color}
                       {$heading_font}
                    }
                    .da-slide p{
                     {$content_size}
                       {$content_family}
                       {$content_color}
                       {$content_font}
                       {$line_height}
                    }
                    .da-slide .da-link{
                       {$readmore_size}
                       {$readmore_family}
                       {$readmore_color}
                       {$readmore_font}
                    }
                    .da-img img{
                    max-width:100%;
                    }
EOF;

            if (strlen($css)) {
                return "<style type='text/css'{$scoped}>{$css}\n    </style>";
            }
        }

        return "";
    }

    /**
     *
     */
    public function get_slider_css($css, $settings, $slider_id) {
        if ($slider_id != $this->id) {
            return $css;
        }

        $imports = "";

        if ($this->get_setting('printCss') == 'true') {
            $stylesheets[] = "@import url('" . OSC_PARALLAX_SLIDER_ASSETS_URL . "oscparallaxslider/public.css?ver=" . OSC_PARALLAX_SLIDER_VERSION . "');";
            $stylesheets[] = "@import url(http://fonts.googleapis.com/css?family=Economica:700,400italic);";
            $stylesheets[] = "@import url('" . OSC_PARALLAX_SLIDER_ASSETS_URL . $this->css_path . "?ver=" . OSC_PARALLAX_SLIDER_VERSION . "');";
            // $stylesheets[] = "@import url('" . OSC_PARALLAX_SLIDER_ASSETS_URL . $this->css_path_extra . "?ver=" . OSC_PARALLAX_SLIDER_VERSION . "');";
            $imports = "\n        " . implode("\n        ", $stylesheets);
        }

        return $css . $imports;
    }


    /**
     * Include slider assets, JS and CSS paths are specified by child classes.
     */
    public function enqueue_scripts() {
        if ($this->get_setting('printJs') == 'true') {
            wp_enqueue_script('oscparallaxslider-' . $this->get_setting('type') . '-slider-extra', OSC_PARALLAX_SLIDER_ASSETS_URL . $this->js_path_extra, array('jquery'), OSC_PARALLAX_SLIDER_VERSION);
            wp_enqueue_script('oscparallaxslider-' . $this->get_setting('type') . '-slider', OSC_PARALLAX_SLIDER_ASSETS_URL . $this->js_path, array('jquery'), OSC_PARALLAX_SLIDER_VERSION);
        }

        do_action('oscparallaxslider_register_public_styles');
    }

    /**
     * Update the slider settings, converting checkbox values (on/off) to true or false.
     */
    public function update_settings($new_settings) {
        $old_settings = $this->get_settings();
//print_r($new_settings);
        // convert submitted checkbox values from 'on' or 'off' to boolean values
        $checkboxes = array('links', 'navigation','printCss', 'printJs','autoplay');

        foreach ($checkboxes as $checkbox) {
            if (isset($new_settings[$checkbox]) && $new_settings[$checkbox] == 'on') {
                $new_settings[$checkbox] = "true";
            } else {
                $new_settings[$checkbox] = "false";
            }
        }

        // update the slider settings
        update_post_meta($this->id, 'ml-slider_settings', array_merge((array)$old_settings, $new_settings));

        $this->settings = $this->get_settings();
    }

    /**
     * Update the title of the slider
     */
    private function update_title($title) {
        $slide = array(
            'ID' => $this->id,
            'post_title' => $title
        );

        wp_update_post($slide);
    }

    /**
     * Delete a slide. This doesn't actually remove the slide from WordPress, simply untags
     * it from the slide taxonomy
     */
    private function delete_slide($slide_id) {
        // Get the existing terms and only keep the ones we don't want removed
        $new_terms = array();
        $current_terms = wp_get_object_terms($slide_id, 'ml-slider', array('fields' => 'ids'));
        $term = get_term_by('name', $this->id, 'ml-slider');

        foreach ($current_terms as $current_term) {
            if ($current_term != $term->term_id) {
                $new_terms[] = intval($current_term);
            }
        }

        return wp_set_object_terms($slide_id, $new_terms, 'ml-slider');
    }

    /**
     * Loop over each slide and call the save action on each
     */
    private function update_slides($data) {
        foreach ($data as $slide_id => $fields) {
            do_action("oscparallaxslider_save_{$fields['type']}_slide", $slide_id, $this->id, $fields);
        }
    }
}
?>
