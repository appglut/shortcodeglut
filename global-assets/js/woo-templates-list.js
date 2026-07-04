/**
 * Woo Templates List Page JavaScript
 *
 * @package Shortcodeglut
 */

(function($) {
	'use strict';

	// Copy template ID to clipboard
	function copyTemplateId(templateId, button) {
		const $tempInput = $('<input>');
		$('body').append($tempInput);
		$tempInput.val(templateId).select();

		try {
			document.execCommand('copy');
			$tempInput.remove();

			button.addClass('copied');
			button.find('.dashicons').removeClass('dashicons-admin-page').addClass('dashicons-yes');

			setTimeout(function() {
				button.removeClass('copied');
				button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-admin-page');
			}, 2000);
		} catch (err) {
			$tempInput.remove();
		}
	}

	// Open preview modal
	function openPreviewModal(templateId, templateName) {
		console.log('Opening preview modal for template:', templateId);

		const $modal = $('#shortcodeglut-preview-modal');
		const $title = $('#shortcodeglut-preview-modal-title');
		const $content = $('#shortcodeglut-preview-content');

		// Set title
		$title.text(templateName || 'Template Preview');

		// Show loading state
		$content.html('<p class="loading">Loading preview...</p>');

		// Remove previous preview styles
		$('style[id^="shortcodeglut-preview-style-"]').remove();

		// Show modal
		if (!$('body').data('original-overflow')) {
			$('body').data('original-overflow', $('body').css('overflow'));
		}
		$('body').css('overflow', 'hidden');
		$modal.fadeIn(200);

		// Add Font Awesome CDN link to page (use FA6 for better compatibility)
		console.log('Adding Font Awesome CDN...');
		if (!$('#shortcodeglut-fa6').length) {
			const faLink = $('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" id="shortcodeglut-fa6">');
			$('head').append(faLink);
			// Wait for FA to load before loading preview
			faLink.on('load', function() {
				console.log('Font Awesome CDN loaded');
				loadPreviewContent(templateId, $content);
			});
		} else {
			console.log('Font Awesome CDN already exists');
			loadPreviewContent(templateId, $content);
		}
	}

	// Load preview content via AJAX
	function loadPreviewContent(templateId, $content) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'shortcodeglut_load_preview',
				nonce: shortcodeglutTemplatesList.previewNonce,
				template_id: templateId
			},
			success: function(response) {
				console.log('Preview response:', response);
				if (response.success) {
					// Add CSS to page with unique ID
					if (response.data.css) {
						const previewStyleId = 'shortcodeglut-preview-style-' + Date.now();
						const styleElement = document.createElement('style');
						styleElement.id = previewStyleId;
						styleElement.textContent = response.data.css;
						document.head.appendChild(styleElement);
						console.log('CSS added with ID:', previewStyleId);

						// Clean up style when modal closes
						$('#shortcodeglut-preview-modal').off('modal:closed').on('modal:closed', function() {
							$('#' + previewStyleId).remove();
						});
					}

					// Add HTML content
					$content.html(response.data.html);
					console.log('HTML content added');
				} else {
					$content.html('<p class="error">Error loading preview: ' + (response.data.message || 'Unknown error') + '</p>');
				}
			},
			error: function(xhr, status, error) {
				console.error('Preview AJAX error:', error);
				$content.html('<p class="error">Error loading preview. Please try again.</p>');
			}
		});
	}

	// Close preview modal
	function closePreviewModal() {
		// Trigger modal:closed event
		$('#shortcodeglut-preview-modal').trigger('modal:closed');

		// Remove all preview styles
		$('style[id^="shortcodeglut-preview-style-"]').remove();

		// Remove Font Awesome CDN when closing (optional - keeps page cleaner)
		$('#shortcodeglut-fa6').remove();

		$('#shortcodeglut-preview-modal').fadeOut(200, function() {
			// Clear content
			$('#shortcodeglut-preview-content').html('');

			const originalOverflow = $('body').data('original-overflow');
			if (originalOverflow !== undefined) {
				$('body').css('overflow', originalOverflow);
			} else {
				$('body').css('overflow', '');
			}
		});
	}

	// Document ready
	$(document).ready(function() {
		// Copy template ID button click
		$(document).on('click', '.shortcodeglut-copy-id-btn', function(e) {
			e.preventDefault();
			const templateId = $(this).data('template-id');
			copyTemplateId(templateId, $(this));
		});

		// Preview button click
		$(document).on('click', '.shortcodeglut-preview-btn', function(e) {
			e.preventDefault();
			const templateId = $(this).data('template-id');
			const templateName = $(this).data('template-name');
			console.log('Preview button clicked, templateId:', templateId);
			openPreviewModal(templateId, templateName);
		});

		// Close modal on overlay click
		$(document).on('click', '#shortcodeglut-preview-modal-overlay', function() {
			closePreviewModal();
		});

		// Close modal on close button click
		$(document).on('click', '#shortcodeglut-preview-modal-close', function(e) {
			e.preventDefault();
			closePreviewModal();
		});

		// Close modal on ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#shortcodeglut-preview-modal').is(':visible')) {
				closePreviewModal();
			}
		});
	});

	// Global function for closing modal
	window.closeTemplatePreview = closePreviewModal;

})(jQuery);
