/**
 * SideOne Layout Shortcode JavaScript
 */
jQuery(document).ready(function($) {
	'use strict';

	// Handle pagination clicks
	$(document).on('click', '.shortcodeglut-sideone-pagination .page-numbers a', function(e) {
		e.preventDefault();

		var $wrapper = $(this).closest('.shortcodeglut-sideone-wrapper');
		var page = $(this).data('page');

		if (!page) {
			var href = $(this).attr('href');
			var match = href.match(/shortcodeglut_paged=(\d+)/);
			if (match) {
				page = match[1];
			}
		}

		if (!page) return;

		$.ajax({
			url: shortcodeglutSideone.ajax_url,
			type: 'POST',
			data: {
				action: 'shortcodeglut_sideone_load',
				nonce: shortcodeglutSideone.nonce,
				paged: page,
				columns: $wrapper.data('atts').columns,
				order_by: $wrapper.data('atts').order_by,
				items_per_page: $wrapper.data('atts').items_per_page,
				paging: $wrapper.data('atts').paging ? 1 : 0,
				category: $wrapper.data('atts').category,
				exclude: $wrapper.data('atts').exclude,
				image_position: $wrapper.data('atts').image_position,
				image_width: $wrapper.data('atts').image_width,
				show_excerpt: $wrapper.data('atts').show_excerpt ? 1 : 0,
				show_price: $wrapper.data('atts').show_price ? 1 : 0,
				show_button: $wrapper.data('atts').show_button ? 1 : 0,
				gap_size: $wrapper.data('atts').gap_size
			},
			success: function(response) {
				if (response.success) {
					$wrapper.find('.shortcodeglut-sideone-content').html(response.data.html);
					$('html, body').animate({
						scrollTop: $wrapper.offset().top - 100
					}, 500);
				}
			}
		});
	});
});
