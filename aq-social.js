/**
 * Gets & updates counts
 * 
 * @package Aqua Social
 * @author Syamil MJ
 * @uri http://aquagraphite.com
 */

jQuery(document).ready(function($) {

	$(window).load(function() {

	    //only fire if aq-social exists
		if($('.aq-social-buttons').length) {

			$('.aq-social-buttons').each(function() {

				var id = '#' + $(this).attr('id');

				// get all counts
				var $tw = $(id + ' .social-button-twitter .social-count'),
					$fb = $(id + ' .social-button-facebook .social-count'),
					$li = $(id + ' .social-button-linkedin .social-count'),
					$gp = $(id + ' .social-button-googleplus .social-count');

				var data = {
					action: 'aq_social_get_counts',
					security: $(id + ' .aq-social-nonce').val(),
					post_id: $(id).data('post_id')
				};
				
				$.post(aqvars.ajaxurl, data, function(response) {

					if(response == '-1') return false;

					var resp = $.parseJSON(response);

					if(parseInt(resp.twitter) > 0)
						$tw.html(parseInt(resp.twitter));

					if(parseInt(resp.facebook) > 0)
						$fb.html(parseInt(resp.facebook));

					if(parseInt(resp.linkedin) > 0)
						$li.html(parseInt(resp.linkedin));

					if(parseInt(resp.googleplus) > 0)
						$gp.html(parseInt(resp.googleplus));
								
				});

			});

		}

	});


});