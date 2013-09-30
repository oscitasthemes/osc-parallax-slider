<?php
/**
 * Flex Slider specific markup, javascript, css and settings.
 */
class OscParallaxContentSlider extends OscParallaxslider {

    protected $js_function = 'cslider';
    protected $js_path = 'oscparallaxslider/js/jquery.cslider.js';
    protected $js_path_extra = 'oscparallaxslider/js/modernizr.custom.28468.js';
    //protected $css_path_demo = 'oscparallaxslider/css/demo.css';
    protected $css_path_extra = 'oscparallaxslider/css/nojs.css';
    protected $css_path = 'oscparallaxslider/css/style.css';
    protected $font_path = 'oscparallaxslider/fonts';

    public function __construct($id) {
        parent::__construct($id);

    }


    /**
     * Enable the parameters that are accepted by the slider
     * 
     * @return array enabled parameters
     */
    protected function get_param($param) {
        $params = array(
            'autoplay' => true
        );

        if (isset($params[$param])) {
            return $params[$param];
        }

        return false;
    }

    /**
     * Include slider assets
     */
    public function enqueue_scripts() {
        parent::enqueue_scripts();
        if ($this->get_setting('printJs') == 'true') {
            //wp_enqueue_script('oscparallaxslider-easing', OSC_PARALLAX_SLIDER_ASSETS_URL . 'easing/jQuery.easing.min.js', array('jquery'), OSC_PARALLAX_SLIDER_VERSION);
        }
    }
    
    /**
     * Build the HTML for a slider.
     *
     * @return string slider markup.
     */
    protected function get_html() {
        $return_value = "";
        foreach ($this->slides as $slide) {
            $return_value .= "\n " . $slide . "\n";
        }
        return $return_value;
    }
}
?>