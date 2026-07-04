/**
 * Woo Templates Admin JavaScript
 */
(function($) {
	'use strict';

	// Make sure jQuery is loaded
	if (typeof $ === 'undefined') {
		return;
	}

	// Copy template ID functionality
	$(document).on('click', '.shortcodeglut-copy-id-btn', function(e) {
		e.preventDefault();
		var templateId = $(this).data('template-id');
		var $button = $(this);
		var $wrapper = $button.closest('.shortcodeglut-template-id-wrapper');

		// Create temporary input for copying
		var tempInput = $('<input>');
		$('body').append(tempInput);
		tempInput.val(templateId).select();
		document.execCommand('copy');
		tempInput.remove();

		// Visual feedback
		$wrapper.addClass('shortcodeglut-copied');
		$button.find('.dashicons').addClass('dashicons-yes').removeClass('dashicons-admin-page');

		// Reset after 2 seconds
		setTimeout(function() {
			$wrapper.removeClass('shortcodeglut-copied');
			$button.find('.dashicons').addClass('dashicons-admin-page').removeClass('dashicons-yes');
		}, 2000);
	});

	// Sample product data for preview
	var imageBaseUrl = shortcodeglutWooTemplates.pluginUrl || '/wp-content/plugins/shortcodeglut/';


	var sampleProduct = {
		title: 'Premium Wireless Headphones',
		permalink: '#',
		image: imageBaseUrl + 'global-assets/images/sample-image.png',
		price: '$149.00',
		regularPrice: '$199.00',
		salePrice: '$149.00',
		onSale: true,
		rating: 4.5,
		ratingCount: 128,
		shortDesc: 'Experience premium sound quality with our wireless headphones. Features active noise cancellation, 30-hour battery life, and comfortable over-ear design.',
		categories: '<a href="#">Electronics</a>, <a href="#">Audio</a>',
		inStock: true,
		stockStatus: 'In Stock',
		addToCartUrl: '#',
		sku: 'WH-PREMIUM-001'
	};

	// Template tag replacement function
	function replaceTemplateTags(html, product) {
		var replaced = html
			.replace(/\[product_title\]/g, product.title)
			.replace(/\[product_permalink\]/g, product.permalink)
			.replace(/\[product_price\]/g, product.onSale ? '<del>' + product.regularPrice + '</del> <ins>' + product.salePrice + '</ins>' : product.regularPrice)
			.replace(/\[product_regular_price\]/g, product.regularPrice)
			.replace(/\[product_sale_price\]/g, product.salePrice)
			.replace(/\[product_short_description\]/g, product.shortDesc)
			.replace(/\[product_categories\]/g, product.categories)
			.replace(/\[product_stock\]/g, product.stockStatus)
			.replace(/\[product_sku\]/g, product.sku)
			.replace(/\[product_rating\]/g, generateStarRating(product.rating))
			.replace(/\[product_badge_sale\]/g, product.onSale ? '<span class="scg-tmpl-modern-badge">Sale</span>' : '')
			.replace(/\[btn_cart\]/g, '<a href="' + product.addToCartUrl + '" class="button add_to_cart_button">Add to Cart</a>')
			.replace(/\[btn_view\]/g, '<a href="' + product.permalink + '" class="button">View Product</a>')
			.replace(/\[product_image\]/g, generateProductImage(product));

		return replaced;
	}

	// Generate star rating HTML
	function generateStarRating(rating) {
		var fullStars = Math.floor(rating);
		var halfStar = rating % 1 >= 0.5 ? true : false;
		var emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
		var html = '<div class="shortcodeglut-star-rating">';

		for (var i = 0; i < fullStars; i++) {
			html += '<span class="dashicons dashicons-star-filled"></span>';
		}
		if (halfStar) {
			html += '<span class="dashicons dashicons-star-half"></span>';
		}
		for (var i = 0; i < emptyStars; i++) {
			html += '<span class="dashicons dashicons-star-empty"></span>';
		}
		html += '</div>';
		return html;
	}

	// Generate product image HTML
	function generateProductImage(product) {
		var imageSrc = product.image || 'https://placehold.co/600x600/f3f4f6/111827?text=Product+Image&font=playfair-display';
		return '<img src="' + imageSrc + '" alt="' + product.title + '" style="max-width:100%;height:100%;display:block;" />';
	}

	// Preview template functionality
	$(document).on('click', '.shortcodeglut-preview-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var $button = $(this);
		var templateName = $button.data('template-name');
		var templateHtml = $button.data('template-html');
		var templateCss = $button.data('template-css');


		// Show loader
		$('#shortcodeglut-preview-modal').show();
		$('#shortcodeglut-preview-content').html('<div class="shortcodeglut-preview-loader"><span class="spinner is-active"></span></div>');
		// Store original overflow value
		if (!$('body').data('original-overflow')) {
			$('body').data('original-overflow', $('body').css('overflow'));
		}
		$('body').css('overflow', 'hidden');

		// Simulate loading and render preview
		setTimeout(function() {
			// Replace template tags with sample product data
			var renderedHtml = replaceTemplateTags(templateHtml, sampleProduct);

			// Update modal content
			$('#shortcodeglut-preview-modal-title').text(templateName);
			$('#shortcodeglut-preview-content').html('<div class="shortcodeglut-preview-content-inner">' + renderedHtml + '</div>');

			// Create and append style element properly using jQuery
			// Remove any existing preview style element
			$('#shortcodeglut-preview-styles').empty();
			$('<style>').attr('id', 'shortcodeglut-preview-style').text(templateCss).appendTo('#shortcodeglut-preview-styles');
		}, 300);
	});

	// Close modal when clicking overlay (outside content)
	$(document).on('click', '#shortcodeglut-preview-modal-overlay', function() {
		closePreviewModal();
	});

	// Prevent closing when clicking inside modal container
	$(document).on('click', '#shortcodeglut-preview-modal-container', function(e) {
		e.stopPropagation();
	});

	// Close modal on close button (using event delegation)
	$(document).on('click', '#shortcodeglut-preview-modal-close', function(e) {
		e.preventDefault();
		e.stopPropagation();
		closePreviewModal();
	});

	// Close modal on ESC key
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $('#shortcodeglut-preview-modal').is(':visible')) {
			closePreviewModal();
		}
	});

	// Helper function to close modal and restore scroll
	function closePreviewModal() {
		$('#shortcodeglut-preview-modal').hide();
		// Restore original overflow value
		var originalOverflow = $('body').data('original-overflow');
		if (originalOverflow !== undefined) {
			$('body').css('overflow', originalOverflow);
		} else {
			$('body').css('overflow', '');
		}
	}

	// Duplicate template functionality
	$(document).on('click', '.shortcodeglut-duplicate-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var templateId = $(this).data('template-id');
		var $button = $(this);


		if (!confirm(shortcodeglutWooTemplates.i18n.confirmDuplicate)) {
			return;
		}

		// Show loading state
		$button.text(shortcodeglutWooTemplates.i18n.duplicating).prop('disabled', true);

		// AJAX request to duplicate template
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'shortcodeglut_duplicate_template',
				template_id: templateId,
				nonce: shortcodeglutWooTemplates.nonce
			},
			success: function(response) {
				if (response.success) {
					window.location.reload();
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : shortcodeglutWooTemplates.i18n.duplicateFailed;
					alert(errorMsg);
					$button.html('<span class="dashicons dashicons-admin-page"></span><span>' + shortcodeglutWooTemplates.i18n.duplicate + '</span>').prop('disabled', false);
				}
			},
			error: function(xhr, status, errorThrown) {
				alert(shortcodeglutWooTemplates.i18n.ajaxError + ' ' + errorThrown + '. ' + shortcodeglutWooTemplates.i18n.tryAgain);
				$button.html('<span class="dashicons dashicons-admin-page"></span><span>' + shortcodeglutWooTemplates.i18n.duplicate + '</span>').prop('disabled', false);
			}
		});
	});

})(jQuery);
