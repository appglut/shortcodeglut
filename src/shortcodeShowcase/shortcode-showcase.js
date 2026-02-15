/**
 * Shortcode Showcase Admin Page JavaScript
 */
(function($) {
	'use strict';

	// Handle Details button clicks using event delegation
	$(document).on('click', '.details-btn', function() {
		var card = $(this).closest('.shortcode-card');
		var data = card.data('shortcode-data');
		if (data) {
			showDetailsModal(data);
		}
	});

	function copyShortcode(shortcode, button) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(shortcode).then(function() {
				showCopyNotification('Shortcode copied to clipboard!');
				if (button) {
					button.classList.add('copied');
					setTimeout(function() {
						button.classList.remove('copied');
					}, 2000);
				}
			}).catch(function() {
				fallbackCopyTextToClipboard(shortcode, button);
			});
		} else {
			fallbackCopyTextToClipboard(shortcode, button);
		}
	}

	function fallbackCopyTextToClipboard(text, button) {
		var textArea = document.createElement("textarea");
		textArea.value = text;
		textArea.style.position = "fixed";
		textArea.style.top = "0";
		textArea.style.left = "0";
		textArea.style.opacity = "0";
		document.body.appendChild(textArea);
		textArea.select();
		try {
			document.execCommand('copy');
			showCopyNotification('Shortcode copied to clipboard!');
			if (button) {
				button.classList.add('copied');
				setTimeout(function() {
					button.classList.remove('copied');
				}, 2000);
			}
		} catch (err) {
			showCopyNotification('Failed to copy shortcode', false);
		}
		document.body.removeChild(textArea);
	}

	function showCopyNotification(message, isSuccess) {
		isSuccess = isSuccess !== undefined ? isSuccess : true;
		var notification = document.createElement('div');
		notification.className = 'shopglut-copy-notification';
		notification.textContent = message;
		if (!isSuccess) {
			notification.style.background = '#ef4444';
		}
		document.body.appendChild(notification);

		setTimeout(function() {
			notification.classList.add('show');
		}, 10);

		setTimeout(function() {
			notification.classList.remove('show');
			setTimeout(function() {
				document.body.removeChild(notification);
			}, 300);
		}, 2500);
	}

	function showDetailsModal(data) {
		if (!data) return;

		document.getElementById('shopglut-details-title').textContent = data.title;
		document.getElementById('shopglut-details-body').innerHTML = generateDetailsContent(data);
		document.getElementById('shopglut-details-modal').style.display = 'block';
		document.body.style.overflow = 'hidden';
	}

	function closeDetailsModal() {
		document.getElementById('shopglut-details-modal').style.display = 'none';
		document.body.style.overflow = '';
	}

	function generateDetailsContent(data) {
		var html = '';

		// Description
		html += '<div class="shopglut-details-section">';
		html += '<h4>' + shortcodeglutShowcase.i18n.description + '</h4>';
		html += '<p style="color: #6b7280; line-height: 1.6;">' + data.description + '</p>';
		html += '</div>';

		// Shortcode Syntax
		html += '<div class="shopglut-details-section">';
		html += '<h4>' + shortcodeglutShowcase.i18n.shortcodeSyntax + '</h4>';
		html += '<div class="shopglut-details-code-block">';
		html += '<code>' + escapeHtml(data.examples.basic) + '</code>';
		html += '</div>';

		if (data.examples.advanced) {
			html += '<div class="shopglut-details-code-block">';
			html += '<code>' + escapeHtml(data.examples.advanced) + '</code>';
			html += '</div>';
		}
		html += '</div>';

		// How to Use
		html += '<div class="shopglut-details-section">';
		html += '<h4>' + shortcodeglutShowcase.i18n.howToUse + '</h4>';
		html += '<ol style="color: #6b7280; line-height: 1.8; padding-left: 20px;">';
		html += '<li>' + shortcodeglutShowcase.i18n.copyShortcode + '</li>';
		html += '<li>' + shortcodeglutShowcase.i18n.pasteShortcode + '</li>';
		html += '<li>' + shortcodeglutShowcase.i18n.customizeParams + '</li>';
		html += '<li>' + shortcodeglutShowcase.i18n.createTemplate + '</li>';
		html += '</ol>';
		html += '</div>';

		// Parameters Table
		if (data.params && Object.keys(data.params).length > 0) {
			html += '<div class="shopglut-details-section">';
			html += '<h4>' + shortcodeglutShowcase.i18n.availableParams + '</h4>';
			html += '<table class="shopglut-details-table">';
			html += '<thead><tr><th>' + shortcodeglutShowcase.i18n.parameter + '</th><th>' + shortcodeglutShowcase.i18n.description + '</th></tr></thead>';
			html += '<tbody>';

			for (var param in data.params) {
				html += '<tr>';
				html += '<td><code>' + escapeHtml(param) + '</code></td>';
				html += '<td>' + escapeHtml(data.params[param]) + '</td>';
				html += '</tr>';
			}

			html += '</tbody></table>';
			html += '</div>';
		}

		return html;
	}

	function showPreviewModal(type) {
		var title, content;

		if (type === 'woo-category') {
			title = shortcodeglutShowcase.i18n.wooCategoryPreview;
			content = generateWooCategoryPreview();
		} else if (type === 'product-table') {
			title = shortcodeglutShowcase.i18n.productTablePreview;
			content = generateProductTablePreview();
		} else if (type === 'sale-products') {
			title = shortcodeglutShowcase.i18n.saleProductsPreview;
			content = generateSaleProductsPreview();
		}

		document.getElementById('shopglut-preview-title').textContent = title;
		document.getElementById('shopglut-preview-body').innerHTML = content;
		document.getElementById('shopglut-preview-modal').style.display = 'block';
		document.body.style.overflow = 'hidden';
	}

	function closePreviewModal() {
		document.getElementById('shopglut-preview-modal').style.display = 'none';
		document.body.style.overflow = '';
	}

	function generateWooCategoryPreview() {
		return '<div class="shopglut-preview-demo">' +
			'<div class="shopglut-preview-toolbar">' +
				'<div style="display: flex; gap: 10px; margin-bottom: 10px;">' +
					'<input type="text" placeholder="' + shortcodeglutShowcase.i18n.searchPlaceholder + '" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
					'<select style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
						'<option>' + shortcodeglutShowcase.i18n.orderByDate + '</option>' +
						'<option>' + shortcodeglutShowcase.i18n.orderByTitle + '</option>' +
						'<option>' + shortcodeglutShowcase.i18n.orderByPrice + '</option>' +
					'</select>' +
					'<select style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
						'<option>' + shortcodeglutShowcase.i18n.descending + '</option>' +
						'<option>' + shortcodeglutShowcase.i18n.ascending + '</option>' +
					'</select>' +
					'<button style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">' + shortcodeglutShowcase.i18n.apply + '</button>' +
				'</div>' +
				'<div style="display: flex; align-items: center; gap: 12px;">' +
					'<div style="width: 50px; height: 50px; background: #e5e7eb; border-radius: 4px;"></div>' +
					'<div>' +
						'<h3 style="margin: 0; font-size: 16px;">Electronics</h3>' +
						'<p style="margin: 0; color: #6b7280; font-size: 13px;">Browse our electronic products</p>' +
					'</div>' +
				'</div>' +
			'</div>' +
			'<div class="shopglut-preview-grid">' +
				generatePreviewProducts() +
			'</div>' +
			'<div style="padding: 16px; border-top: 1px solid #e5e7eb; text-align: center;">' +
				'<div style="display: inline-flex; gap: 4px;">' +
					'<span style="padding: 6px 12px; background: #3b82f6; color: white; border-radius: 4px; cursor: pointer;">1</span>' +
					'<span style="padding: 6px 12px; background: #f3f4f6; color: #6b7280; border-radius: 4px; cursor: pointer;">2</span>' +
					'<span style="padding: 6px 12px; background: #f3f4f6; color: #6b7280; border-radius: 4px; cursor: pointer;">3</span>' +
					'<span style="padding: 6px 12px; color: #6b7280; cursor: pointer;">&raquo;</span>' +
				'</div>' +
			'</div>' +
		'</div>';
	}

	function generateProductTablePreview() {
		return '<div class="shopglut-preview-demo">' +
			'<div style="overflow-x: auto;">' +
				'<table style="width: 100%; border-collapse: collapse; font-size: 13px;">' +
					'<thead style="background: #f9fafb;">' +
						'<tr>' +
							'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">' + shortcodeglutShowcase.i18n.title + '</th>' +
							'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">' + shortcodeglutShowcase.i18n.price + '</th>' +
							'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">' + shortcodeglutShowcase.i18n.stock + '</th>' +
							'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">' + shortcodeglutShowcase.i18n.action + '</th>' +
						'</tr>' +
					'</thead>' +
					'<tbody>' +
						'<tr>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong>Wireless Headphones</strong></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><del style="color: #9ca3af;">$199.00</del> <ins style="color: #059669;">$149.00</ins></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="color: #059669;">' + shortcodeglutShowcase.i18n.inStock + '</span></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><button style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">' + shortcodeglutShowcase.i18n.addToCart + '</button></td>' +
						'</tr>' +
						'<tr>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong>USB-C Cable</strong></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">$12.99</td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="color: #059669;">' + shortcodeglutShowcase.i18n.inStock + '</span></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><button style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">' + shortcodeglutShowcase.i18n.addToCart + '</button></td>' +
						'</tr>' +
						'<tr>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong>Laptop Stand</strong></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">$45.00</td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="color: #dc2626;">' + shortcodeglutShowcase.i18n.outOfStock + '</span></td>' +
							'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><button style="padding: 6px 12px; background: #9ca3af; color: white; border: none; border-radius: 4px; cursor: pointer;">' + shortcodeglutShowcase.i18n.readMore + '</button></td>' +
						'</tr>' +
					'</tbody>' +
				'</table>' +
			'</div>' +
			'<div style="padding: 16px; border-top: 1px solid #e5e7eb; text-align: center;">' +
				'<p style="color: #6b7280; font-size: 13px;">' + shortcodeglutShowcase.i18n.showingProducts + '</p>' +
			'</div>' +
		'</div>';
	}

	function generatePreviewProducts() {
		var products = [
			{ name: 'Wireless Headphones', price: '$149.00', oldPrice: '$199.00' },
			{ name: 'USB-C Cable', price: '$12.99', oldPrice: '' },
			{ name: 'Laptop Stand', price: '$45.00', oldPrice: '' },
			{ name: 'Wireless Mouse', price: '$29.99', oldPrice: '' },
		];

		var html = '';
		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div class="shopglut-preview-product">' +
				'<div style="width: 100%; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; margin-bottom: 12px;"></div>' +
				'<h4>' + p.name + '</h4>';
			if (p.oldPrice) {
				html += '<div class="price"><del style="color: #9ca3af; font-size: 12px;">' + p.oldPrice + '</del> ' + p.price + '</div>';
			} else {
				html += '<div class="price">' + p.price + '</div>';
			}
			html += '<a href="#" class="btn">' + shortcodeglutShowcase.i18n.addToCart + '</a>' +
				'</div>';
		}
		return html;
	}

	function generateSaleProductsPreview() {
		var products = [
			{ name: shortcodeglutShowcase.i18n.wirelessHeadphones, price: '$149.00', oldPrice: '$199.00', badge: '25%', color1: '#667eea', color2: '#764ba2' },
			{ name: shortcodeglutShowcase.i18n.smartWatchPro, price: '$199.00', oldPrice: '$299.00', badge: '33%', color1: '#f093fb', color2: '#f5576c' },
			{ name: shortcodeglutShowcase.i18n.bluetoothSpeaker, price: '$59.00', oldPrice: '$89.00', badge: '34%', color1: '#4facfe', color2: '#00f2fe' },
			{ name: shortcodeglutShowcase.i18n.laptopStand, price: '$35.00', oldPrice: '$49.00', badge: '29%', color1: '#43e97b', color2: '#38f9d7' },
		];

		var html = '<div class="shopglut-preview-demo" style="padding: 24px;">';
		html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">';

		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div style="position: relative; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">';
			html += '<span style="position: absolute; top: 8px; left: 8px; background: #dc2626; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; z-index: 2;">-' + p.badge + '</span>';
			html += '<div style="width: 100%; height: 140px; background: linear-gradient(135deg, ' + p.color1 + ' 0%, ' + p.color2 + ' 100%);"></div>';
			html += '<div style="padding: 12px;">';
			html += '<h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #111827;">' + p.name + '</h4>';
			html += '<div style="margin-bottom: 8px;">';
			html += '<del style="color: #9ca3af; font-size: 12px; margin-right: 4px;">' + p.oldPrice + '</del>';
			html += '<span style="color: #059669; font-weight: 600; font-size: 14px;">' + p.price + '</span>';
			html += '</div>';
			html += '<button style="width: 100%; padding: 6px 12px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">' + shortcodeglutShowcase.i18n.addToCart + '</button>';
			html += '</div>';
			html += '</div>';
		}

		html += '</div>';
		html += '<div style="margin-top: 16px; text-align: center;">';
		html += '<div style="display: inline-flex; gap: 4px;">';
		html += '<span style="padding: 6px 12px; background: #2271b1; color: white; border-radius: 4px; cursor: pointer; font-size: 13px;">1</span>';
		html += '<span style="padding: 6px 12px; background: #f3f4f6; color: #6b7280; border-radius: 4px; cursor: pointer; font-size: 13px;">2</span>';
		html += '<span style="padding: 6px 12px; color: #6b7280; cursor: pointer; font-size: 13px;">&raquo;</span>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
		return html;
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Close modals on ESC key
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			closeDetailsModal();
			closePreviewModal();
		}
	});

	// Expose functions globally for inline onclick handlers
	window.copyShortcode = copyShortcode;
	window.showPreviewModal = showPreviewModal;
	window.closeDetailsModal = closeDetailsModal;
	window.closePreviewModal = closePreviewModal;

})(jQuery);
