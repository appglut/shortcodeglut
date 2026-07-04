/**
 * Shortcode Showcase Admin JavaScript - Additional Admin Features
 * Main functionality is in shortcode-showcase.js
 */
jQuery(document).ready(function($) {
	'use strict';

	// Tab switching functionality
	$('.shopg-tab').on('click', function() {
		var tabId = $(this).data('tab');

		// Remove active class from all tabs and contents
		$('.shopg-tab').removeClass('active');
		$('.shopg-tab-content').removeClass('active');

		// Add active class to clicked tab and corresponding content
		$(this).addClass('active');
		$('#' + tabId).addClass('active');
	});

	// Additional admin-specific features can be added here
});
