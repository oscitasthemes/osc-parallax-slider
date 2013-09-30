jQuery(document).ready(function($) {

    // Enable the correct options for this slider type
    var switchType = function(slider) {
        jQuery('.oscparallaxslider .option:not(.' + slider + ')').attr('disabled', 'disabled').parents('tr').hide();
        jQuery('.oscparallaxslider .option.' + slider).removeAttr('disabled').parents('tr').show();

        // slides - set red background on incompatible slides
        jQuery('.oscparallaxslider .slide:not(.' + slider + ')').css('background', '#FFD9D9');
        jQuery('.oscparallaxslider .slide.' + slider).css('background', '');
    };

    // return a helper with preserved width of cells
    var helper = function(e, ui) {
        ui.children().each(function() {
            jQuery(this).width(jQuery(this).width());
        });
        return ui;
    };

    // drag and drop slides, update the slide order on drop
    jQuery(".oscparallaxslider .left table tbody").sortable({
        helper: helper,
        handle: 'td.col-1',
        placeholder: "ui-state-highlight",
        stop: function() {
            jQuery(".oscparallaxslider .left table").trigger('updateSlideOrder');
        }
    });

    // bind an event to the slides table to update the menu order of each slide
    jQuery('.oscparallaxslider .left table').bind('updateSlideOrder', function(event) {
        jQuery('tr', this).each(function() {
            jQuery('input.menu_order', jQuery(this)).val(jQuery(this).index());
        });
    });

    // show the confirm dialogue
    jQuery(".confirm").live('click', function() {
        return confirm(oscparallaxslider.confirm);
    });

    $('.useWithCaution').change(function(){
        if(!this.checked) {
            return alert(oscparallaxslider.useWithCaution);
        }
    });

    // show the confirm dialogue
    jQuery(".toggle").live('click', function(e) {
        e.preventDefault();
        jQuery(this).next('.message').toggle();
    });

    // helptext tooltips
    jQuery(".oscparallaxslider .tipsy-tooltip").tipsy({className: 'msTipsy', live: true, delayIn: 500, html: true, fade: true, gravity: 'e'});
    jQuery(".oscparallaxslider .tipsy-tooltip-top").tipsy({live: true, delayIn: 500, html: true, fade: true, gravity: 'se'});

    // Select input field contents when clicked
    jQuery(".oscparallaxslider .shortcode input").click(function() {
        this.select();
    });

    // show the spinner while slides are being added
    function checkPendingRequest() {
        if (jQuery.active > 0) {
            jQuery(".oscparallaxslider .spinner").show();
            jQuery(".oscparallaxslider input[type=submit]").attr('disabled', 'disabled');
        } else {
            jQuery(".oscparallaxslider .spinner").hide();
            jQuery(".oscparallaxslider input[type=submit]").removeAttr('disabled');
        }

        setTimeout(checkPendingRequest, 1000); 
    }

    checkPendingRequest();

    // return lightbox width
    var getLightboxWidth = function() {
        var width = parseInt(jQuery('input.width').val(), 10) + 'px';

        if (jQuery('#carouselMode').is(':checked')) {
            width = '75%';
        }
        
        return width;
    };

    // return lightbox height
    var getLightboxHeight = function() {
        var height = parseInt(jQuery('input.height').val(), 10);

        if (!isNaN(height)) {
            height = height + 30 + 'px'
        } else {
            height = '70%';
        }

        return height;
    };

    // AJAX save & preview
    jQuery(".oscparallaxslider form").find("input[type=submit]").click(function(e) {
        e.preventDefault();

        // update slide order
        jQuery(".oscparallaxslider .left table").trigger('updateSlideOrder');

        // get some values from elements on the page:
        var the_form = jQuery(this).parents("form");
        var data = the_form.serialize();
        var url = the_form.attr( 'action' );
        var button = e.target;

        jQuery(".oscparallaxslider .spinner").show();
        jQuery(".oscparallaxslider input[type=submit]").attr('disabled', 'disabled');

        jQuery.ajax({   
            type: "POST",
            data : data,
            cache: false,
            url: url,
            success: function(data) {
                // update the slides with the response html
                $(".oscparallaxslider .left tbody").html($(".oscparallaxslider .left tbody", data).html());
                if (button.id === 'preview') {
                    jQuery.colorbox({
                        iframe: true,
                        href: oscparallaxslider.iframeurl + "?slider_id=" + jQuery(button).data("slider_id"),
                        transition: "elastic",
                        innerHeight: getLightboxHeight(),
                        innerWidth: getLightboxWidth(),
                        scrolling: false,
                        fastIframe: false
                    });
                }
            }   
        });
    });

    jQuery('#heading_font_color').ColorPicker({
        color: '#0000ff',
        onShow: function (colpkr) {
            jQuery(colpkr).fadeIn(500);
            return false;
        },
        onHide: function (colpkr) {
            jQuery(colpkr).fadeOut(500);
            return false;
        },
        onChange: function (hsb, hex, rgb) {
            jQuery('#heading_font_color div').css('backgroundColor', '#' + hex);
            jQuery('#heading_font_color input[type="hidden"]').val('#' + hex);
        }
    });
    jQuery('#content_font_color').ColorPicker({
        color: '#0000ff',
        onShow: function (colpkr) {
            jQuery(colpkr).fadeIn(500);
            return false;
        },
        onHide: function (colpkr) {
            jQuery(colpkr).fadeOut(500);
            return false;
        },
        onChange: function (hsb, hex, rgb) {
            jQuery('#content_font_color div').css('backgroundColor', '#' + hex);
            jQuery('#content_font_color input[type="hidden"]').val('#' + hex);
        }
    });
    jQuery('#readmore_font_color').ColorPicker({
        color: '#0000ff',
        onShow: function (colpkr) {
            jQuery(colpkr).fadeIn(500);
            return false;
        },
        onHide: function (colpkr) {
            jQuery(colpkr).fadeOut(500);
            return false;
        },
        onChange: function (hsb, hex, rgb) {
            jQuery('#readmore_font_color div').css('backgroundColor', '#' + hex);
            jQuery('#readmore_font_color input[type="hidden"]').val('#' + hex);
        }
    });
    jQuery('.oscitas-slider-settings-tbl tr:visible:even').css('background-color', '#FCFCFC');
});