/**
 * Osc Parallax Slider
 */
(function ($) {
	$(function () {
		jQuery('.oscparallaxslider .add-slide').live('click', function(event){
			event.preventDefault();

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				multiple: 'add',
				frame: 'post',
				library: {type: 'image'}
			});

			// When an image is selected, run a callback.
			file_frame.on('insert', function() {
				var selection = file_frame.state().get('selection');

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					var data = {
						action: 'create_image_slide',
						slide_id: attachment.id,
						slider_id: window.parent.oscparallaxslider_slider_id
					};

					jQuery.post(ajaxurl, data, function(response) {
                        console.log(response);
						jQuery(".oscparallaxslider .left table").append(response);
					});
				});
			});

			file_frame.open();
		});
        jQuery('.oscparallaxslider .add-bg').live('click', function(event){
			event.preventDefault();

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				frame: 'post',
				library: {type: 'image'}
			});

			// When an image is selected, run a callback.
			file_frame.on('insert', function() {
				var selection = file_frame.state().get('selection');

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					var data = {
						action: 'create_bg',
						bg_id: attachment.id,
						slider_id: window.parent.oscparallaxslider_slider_id
					};

					jQuery.post(ajaxurl, data, function(response) {
                       // console.log(response);
                        if(response!==1){
                            jQuery("#bg-preview img").attr('src',response);
                        }
					});
				});
			});

			file_frame.open();
		});
	});

}(jQuery));