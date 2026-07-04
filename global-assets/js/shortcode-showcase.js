/**
 * Shortcode Showcase Admin Page JavaScript
 */
jQuery(document).ready(function($) {
	'use strict';

	// Ensure i18n object exists
	if (typeof shortcodeglutShowcase === 'undefined' || !shortcodeglutShowcase.i18n) {
		// Set default values
		window.shortcodeglutShowcase = {
			i18n: {
				description: 'Description',
				shortcodeSyntax: 'Shortcode Syntax',
				howToUse: 'How to Use',
				copyShortcode: 'Copy the shortcode',
				pasteShortcode: 'Paste it into any page',
				customizeParams: 'Customize parameters',
				createTemplate: 'Create a template',
				availableParams: 'Available Parameters',
				parameter: 'Parameter',
				copiedToClipboard: 'Shortcode copied!',
				failedToCopy: 'Failed to copy',
				searchPlaceholder: 'Search...',
				orderByDate: 'Date',
				orderByTitle: 'Title',
				orderByPrice: 'Price',
				descending: 'Descending',
				ascending: 'Ascending',
				apply: 'Apply',
				title: 'Title',
				price: 'Price',
				stock: 'Stock',
				action: 'Action',
				inStock: 'In Stock',
				outOfStock: 'Out of Stock',
				addToCart: 'Add to Cart',
				readMore: 'Read more',
				showingProducts: 'Showing products',
				wooCategoryPreview: 'WooCommerce Category Preview',
				productTablePreview: 'Product Table Preview',
				saleProductsPreview: 'Sale Products Preview',
				wirelessHeadphones: 'Wireless Headphones',
				smartWatchPro: 'Smart Watch Pro',
				bluetoothSpeaker: 'Bluetooth Speaker',
				laptopStand: 'Laptop Stand',
				browseElectronicProducts: 'Browse our products',
				electronics: 'Electronics'
			}
		};
	}

	// Handle Details button clicks using event delegation
	$(document).on('click', '.details-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
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
		notification.className = 'shortcodeglut-copy-notification';
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
		if (!data) {
			return;
		}

		var modal = document.getElementById('shortcodeglut-details-modal');
		var titleEl = document.getElementById('shortcodeglut-details-title');
		var bodyEl = document.getElementById('shortcodeglut-details-body');

		if (!modal || !titleEl || !bodyEl) {
			return;
		}

		titleEl.textContent = data.title;
		bodyEl.innerHTML = generateDetailsContent(data);
		modal.style.display = 'block';
		document.body.style.overflow = 'hidden';
	}

	function closeDetailsModal() {
		var modal = document.getElementById('shortcodeglut-details-modal');
		if (modal) {
			modal.style.display = 'none';
		}
		document.body.style.overflow = '';
	}

	function generateDetailsContent(data) {
		var html = '';

		// Description with icon
		html += '<div class="shortcodeglut-details-section">';
		html += '<div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;">';
		html += '<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">';
		html += '<span style="font-size: 20px;">📦</span></div>';
		html += '<div style="flex: 1;">';
		html += '<h4 style="margin: 0 0 8px 0; color: #111827; font-size: 16px; font-weight: 600;">' + shortcodeglutShowcase.i18n.description + '</h4>';
		html += '<p style="color: #4b5563; line-height: 1.7; margin: 0;">' + data.description + '</p>';
		html += '</div></div>';
		html += '</div>';

		// Key Features section
		if (data.features && data.features.length > 0) {
			html += '<div class="shortcodeglut-details-section" style="background: #f9fafb; padding: 16px; border-radius: 8px; border-left: 4px solid #667eea;">';
			html += '<h4 style="margin: 0 0 12px 0; color: #111827; font-size: 15px; font-weight: 600;">✨ Key Features</h4>';
			html += '<ul style="margin: 0; padding-left: 20px; color: #4b5563; line-height: 1.8;">';
			for (var i = 0; i < data.features.length; i++) {
				html += '<li style="margin-bottom: 6px;">' + data.features[i] + '</li>';
			}
			html += '</ul>';
			html += '</div>';
		}

		// Use Cases section
		if (data.useCases && data.useCases.length > 0) {
			html += '<div class="shortcodeglut-details-section">';
			html += '<h4 style="margin: 0 0 12px 0; color: #111827; font-size: 15px; font-weight: 600;">🎯 Use Cases</h4>';
			html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">';
			for (var i = 0; i < data.useCases.length; i++) {
				html += '<div style="background: #f3f4f6; padding: 10px 14px; border-radius: 6px; font-size: 13px; color: #4b5563;">';
				html += '<span style="margin-right: 6px;">→</span>' + data.useCases[i];
				html += '</div>';
			}
			html += '</div>';
			html += '</div>';
		}

		// Shortcode Syntax
		html += '<div class="shortcodeglut-details-section">';
		html += '<h4 style="margin: 0 0 12px 0; color: #111827; font-size: 15px; font-weight: 600;">📋 ' + shortcodeglutShowcase.i18n.shortcodeSyntax + '</h4>';

		// Basic example
		html += '<div style="margin-bottom: 12px;">';
		html += '<span style="display: inline-block; background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 8px;">BASIC</span>';
		html += '<div class="shortcodeglut-details-code-block">';
		html += '<code>' + escapeHtml(data.examples.basic) + '</code>';
		html += '</div></div>';

		// Advanced example
		if (data.examples.advanced) {
			html += '<div>';
			html += '<span style="display: inline-block; background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 8px;">ADVANCED</span>';
			html += '<div class="shortcodeglut-details-code-block">';
			html += '<code>' + escapeHtml(data.examples.advanced) + '</code>';
			html += '</div></div>';
		}
		html += '</div>';

		// How to Use
		html += '<div class="shortcodeglut-details-section">';
		html += '<h4 style="margin: 0 0 12px 0; color: #111827; font-size: 15px; font-weight: 600;">🚀 ' + shortcodeglutShowcase.i18n.howToUse + '</h4>';
		html += '<div style="background: #ecfdf5; padding: 16px; border-radius: 8px; border: 1px solid #a7f3d0;">';
		html += '<ol style="color: #065f46; line-height: 2; padding-left: 20px; margin: 0;">';
		html += '<li><strong>Copy</strong> the shortcode using the copy button above</li>';
		html += '<li><strong>Paste</strong> it into any page, post, or widget area</li>';
		html += '<li><strong>Customize</strong> parameters to match your needs</li>';
		html += '<li><strong>Preview</strong> your changes and adjust as needed</li>';
		html += '</ol>';
		html += '</div>';
		html += '</div>';

		// Parameters Table
		if (data.params && Object.keys(data.params).length > 0) {
			html += '<div class="shortcodeglut-details-section">';
			html += '<h4 style="margin: 0 0 12px 0; color: #111827; font-size: 15px; font-weight: 600;">⚙️ ' + shortcodeglutShowcase.i18n.availableParams + '</h4>';
			html += '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px;">';
			html += '<table class="shortcodeglut-details-table" style="margin: 0;">';
			html += '<thead style="position: sticky; top: 0; background: #f9fafb; z-index: 1;"><tr><th style="padding: 12px 16px;">' + shortcodeglutShowcase.i18n.parameter + '</th><th style="padding: 12px 16px;">' + shortcodeglutShowcase.i18n.description + '</th></tr></thead>';
			html += '<tbody>';

			for (var param in data.params) {
				var isRequired = param === 'categories' || param === 'limit';
				var requiredBadge = isRequired ? '<span style="background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; margin-left: 6px;">REQUIRED</span>' : '';
				html += '<tr>';
				html += '<td style="padding: 10px 16px;"><code>' + escapeHtml(param) + '</code>' + requiredBadge + '</td>';
				html += '<td style="padding: 10px 16px; color: #4b5563;">' + escapeHtml(data.params[param]) + '</td>';
				html += '</tr>';
			}

			html += '</tbody></table>';
			html += '</div>';
			html += '</div>';
		}

		// Tips section
		if (data.tips && data.tips.length > 0) {
			html += '<div class="shortcodeglut-details-section" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">';
			html += '<h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 14px; font-weight: 600;">💡 Pro Tips</h4>';
			html += '<ul style="margin: 0; padding-left: 18px; color: #78350f; line-height: 1.7; font-size: 13px;">';
			for (var i = 0; i < data.tips.length; i++) {
				html += '<li style="margin-bottom: 6px;">' + data.tips[i] + '</li>';
			}
			html += '</ul>';
			html += '</div>';
		}

		return html;
	}

	function showPreviewModal(type) {
		var title, content;

		if (type === 'woo-category') {
			title = shortcodeglutShowcase.i18n.wooCategoryPreview;
			content = generateWooCategoryPreview();
		} else if (type === 'table-list') {
			title = 'WooCommerce Flat List Preview';
			content = generateTablePreview();
		} else if (type === 'basic-grid') {
			title = 'WooCommerce Basic Grid Preview';
			content = generateBasicGridPreview();
		} else if (type === 'category-tree') {
			title = 'WooCommerce Category Tree Preview';
			content = generateCategoryTreePreview();
		} else if (type === 'sideone') {
			title = 'WooCommerce SideOne Layout Preview';
			content = generateSideonePreview();
		} else if (type === 'minimal-list') {
			title = 'WooCommerce Minimal List Preview';
			content = generateMinimalListPreview();
		} else if (type === 'card-grid') {
			title = 'WooCommerce Card Grid Preview';
			content = generateCardGridPreview();
		} else if (type === 'masonry-grid') {
			title = 'WooCommerce Masonry Grid Preview';
			content = generateMasonryGridPreview();
		} else if (type === 'product-table') {
			title = shortcodeglutShowcase.i18n.productTablePreview;
			content = generateProductTablePreview();
		} else if (type === 'sale-products') {
			title = shortcodeglutShowcase.i18n.saleProductsPreview;
			content = generateSaleProductsPreview();
			} else if (type === 'product-carousel') {
				title = 'Product Carousel Preview (PRO)';
				content = generateProductCarouselPreview();
			} else if (type === 'advanced-filter') {
				title = 'Advanced Filter Preview (PRO)';
				content = generateAdvancedFilterPreview();
			} else if (type === 'price-range-filter') {
				title = 'Price Range Filter Preview (PRO)';
				content = generatePriceRangeFilterPreview();
			} else if (type === 'compare-products') {
				title = 'Compare Products Preview (PRO)';
				content = generateCompareProductsPreview();
			}  else if (type === 'tabs') {                                                                                         
				title = 'WooCommerce Tabs Layout Preview';                                                                        
				content = generateTabsPreview();                                                                                  
			} else if (type === 'carousel') {                                                                                     
				title = 'Carousel Slider Preview';                                                                                
				content = generateCarouselPreview(); 
				} else if (type === 'kanban') {
				title = 'WooCommerce Kanban Board Preview';
				content = generateKanbanPreview();
			} else if (type === 'accordion') {
				title = 'WooCommerce Accordion List Preview';
				content = generateAccordionPreview();
			} else if (type === 'timeline') {
				title = 'WooCommerce Timeline View Preview';
				content = generateTimelinePreview();
			} else if (type === 'zigzag') {
				title = 'WooCommerce Zigzag Layout Preview';
				content = generateZigzagPreview();
			} else if (type === 'drawer') {
				title = 'WooCommerce Drawer Panels Preview';
				content = generateDrawerPreview();
			} else if (type === 'conveyor-belt') {
				title = 'WooCommerce Conveyor Belt Preview';
				content = generateConveyorBeltPreview();
			} else if (type === 'horizontal-left') {
				title = 'WooCommerce Horizontal Image Left Preview';
				content = generateHorizontalLeftPreview();
			} else if (type === 'radial-circle') {
				title = 'WooCommerce Radial Circle Preview';
				content = generateRadialCirclePreview();
			} else if (type === 'book-flip') {
				title = 'WooCommerce Book Flip Preview';
				content = generateBookFlipPreview();
			} else if (type === 'magazine-grid') {
				title = 'WooCommerce Magazine Grid Preview';
				content = generateMagazineGridPreview();
			} else {
				return;
			}

		var modal = document.getElementById('shortcodeglut-preview-modal');
		var titleEl = document.getElementById('shortcodeglut-preview-title');
		var bodyEl = document.getElementById('shortcodeglut-preview-body');

		if (!modal || !titleEl || !bodyEl) {
			return;
		}

		titleEl.textContent = title;
		bodyEl.innerHTML = content;
		modal.style.display = 'block';
		document.body.style.overflow = 'hidden';
	}

	function closePreviewModal() {
		var modal = document.getElementById('shortcodeglut-preview-modal');
		if (modal) {
			modal.style.display = 'none';
		}
		document.body.style.overflow = '';
	}

	function generateWooCategoryPreview() {
		return '<div class="shortcodeglut-preview-demo">' +
			'<div class="shortcodeglut-preview-toolbar">' +
				'<div style="display: flex; gap: 10px; margin-bottom: 10px; justify-content: center;">' +
					'<input type="text" placeholder="' + shortcodeglutShowcase.i18n.searchPlaceholder + '" style="width: 400px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
					'<select style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
						'<option>' + shortcodeglutShowcase.i18n.orderByDate + '</option>' +
						'<option>' + shortcodeglutShowcase.i18n.orderByTitle + '</option>' +
						'<option>' + shortcodeglutShowcase.i18n.orderByPrice + '</option>' +
					'</select>' +
					'<select style="width: 110px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
						'<option>' + shortcodeglutShowcase.i18n.descending + '</option>' +
						'<option>' + shortcodeglutShowcase.i18n.ascending + '</option>' +
					'</select>' +
					'<button style="padding: 8px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap;">' + shortcodeglutShowcase.i18n.apply + '</button>' +
				'</div>' +
			'</div>' +
			'<div class="shortcodeglut-preview-grid">' +
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
		return '<div class="shortcodeglut-preview-demo">' +
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
			{ name: 'Bluetooth Speaker', price: '$59.00', oldPrice: '' },
		];

		var html = '';
		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div class="shortcodeglut-preview-product">' +
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

		var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px;">';
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

	function generateProductCarouselPreview() {
		var products = [
			{ name: 'Wireless Headphones', price: '$149.00', color1: '#667eea', color2: '#764ba2' },
			{ name: 'Smart Watch Pro', price: '$199.00', color1: '#f093fb', color2: '#f5576c' },
			{ name: 'Bluetooth Speaker', price: '$59.00', color1: '#4facfe', color2: '#00f2fe' },
			{ name: 'Laptop Stand', price: '$35.00', color1: '#43e97b', color2: '#38f9d7' },
			{ name: 'USB-C Hub', price: '$79.00', color1: '#fa709a', color2: '#fee140' },
			{ name: 'Wireless Mouse', price: '$29.99', color1: '#30cfd0', color2: '#330867' },
		];

		var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f9fafb;">';

		// Carousel header
		html += '<div style="margin-bottom: 20px; text-align: center;">';
		html += '<h3 style="margin: 0 0 8px 0; color: #111827;">Featured Products</h3>';
		html += '<p style="margin: 0; color: #6b7280; font-size: 13px;">Slide to see more products</p>';
		html += '</div>';

		// Carousel container
		html += '<div style="position: relative; overflow: hidden; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';

		// Carousel slides
		html += '<div style="display: flex; gap: 16px; padding: 16px; overflow-x: auto;">';

		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div style="flex: 0 0 calc(25% - 12px); min-width: 200px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">';
			html += '<div style="width: 100%; height: 160px; background: linear-gradient(135deg, ' + p.color1 + ' 0%, ' + p.color2 + ' 100%);"></div>';
			html += '<div style="padding: 16px;">';
			html += '<h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #111827;">' + p.name + '</h4>';
			html += '<div style="margin-bottom: 12px;">';
			html += '<span style="color: #059669; font-weight: 600; font-size: 16px;">' + p.price + '</span>';
			html += '</div>';
			html += '<button style="width: 100%; padding: 8px 16px; background: linear-gradient(135deg, #c7009c, #a70085); color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">' + shortcodeglutShowcase.i18n.addToCart + '</button>';
			html += '</div>';
			html += '</div>';
		}

		html += '</div>'; // End carousel slides

		// Navigation arrows
		html += '<div style="display: flex; justify-content: space-between; position: absolute; top: 50%; left: 0; right: 0; transform: translateY(-50%); padding: 0 8px; pointer-events: none;">';
		html += '<span style="font-size: 24px; color: #374151; user-select: none;">&#8249;</span>';
		html += '<span style="font-size: 24px; color: #374151; user-select: none;">&#8250;</span>';
		html += '</div>';

		// Dots
		html += '<div style="position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px;">';
		html += '<span style="width: 8px; height: 8px; border-radius: 50%; background: #c7009c;"></span>';
		html += '<span style="width: 8px; height: 8px; border-radius: 50%; background: #d1d5db;"></span>';
		html += '<span style="width: 8px; height: 8px; border-radius: 50%; background: #d1d5db;"></span>';
		html += '</div>';

		html += '</div>'; // End carousel container

		// PRO badge notice
		html += '<div style="margin-top: 20px; padding: 16px; background: linear-gradient(135deg, #fdf2f8 0%, #ffffff 100%); border: 1px solid #f9a8d4; border-radius: 8px; text-align: center;">';
		html += '<div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">';
		html += '<span style="padding: 4px 12px; background: linear-gradient(135deg, #c7009c, #a70085); color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; border-radius: 4px; text-transform: uppercase;">PRO</span>';
		html += '<span style="color: #9d174d; font-weight: 600; font-size: 14px;">Premium Carousel Features</span>';
		html += '</div>';
		html += '<p style="margin: 0; color: #6b7280; font-size: 13px;">Autoplay, touch/swipe support, customizable arrows & dots, infinite loop, and more!</p>';
		html += '</div>';

		html += '</div>';
		return html;
	}

	function generateAdvancedFilterPreview() {
		var html = '<div class="shortcodeglut-preview-demo" style="padding: 20px; background: #f9fafb;">';
		
		// Layout with sidebar and content
		html += '<div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start;">';
		
		// Sidebar
		html += '<div style="background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">';
		
		// Search section
		html += '<div style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f1;">';
		html += '<h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 0 0 12px 0;">Search</h4>';
		html += '<div style="position: relative;"><input type="text" placeholder="Search products..." style="width: 100%; padding: 10px 36px 10px 12px; border: 1px solid #e0e0e1; border-radius: 8px; font-size: 13px;">';
		html += '<span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #888;">&#128269;</span></div>';
		html += '</div>';
		
		// Categories section
		html += '<div style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f1;">';
		html += '<h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 0 0 12px 0;">Categories</h4>';
		html += '<div style="font-size: 13px;">';
		html += '<div style="display: flex; align-items: center; padding: 8px; border-radius: 6px;"><input type="checkbox" style="margin-right: 8px;"><span>Electronics (24)</span></div>';
		html += '<div style="display: flex; align-items: center; padding: 8px; border-radius: 6px; margin-left: 16px;"><input type="checkbox" style="margin-right: 8px;"><span>Computers (8)</span></div>';
		html += '<div style="display: flex; align-items: center; padding: 8px; border-radius: 6px;"><input type="checkbox" style="margin-right: 8px;"><span>Fashion (18)</span></div>';
		html += '</div></div>';
		
		// Price range section
		html += '<div style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f1;">';
		html += '<h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 0 0 12px 0;">Price Range</h4>';
		html += '<input type="range" min="0" max="500" value="250" style="width: 100%; accent-color: #4f46e5;">';
		html += '<div style="display: flex; justify-content: space-between; font-size: 11px; color: #666; margin-top: 6px;"><span>$0</span><span>$500</span></div>';
		html += '</div>';
		
		// Rating section
		html += '<div style="margin-bottom: 16px;">';
		html += '<h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 0 0 12px 0;">Rating</h4>';
		html += '<div style="display: flex; flex-wrap: wrap; gap: 6px;">';
		html += '<span style="padding: 6px 12px; background: #f0f0f1; border-radius: 16px; font-size: 11px; cursor: pointer;">★★★★★</span>';
		html += '<span style="padding: 6px 12px; background: #f0f0f1; border-radius: 16px; font-size: 11px; cursor: pointer;">★★★★☆</span>';
		html += '<span style="padding: 6px 12px; background: #f0f0f1; border-radius: 16px; font-size: 11px; cursor: pointer;">★★★☆☆</span>';
		html += '</div></div>';
		
		// Reset button
		html += '<button style="width: 100%; padding: 10px; background: #f0f0f1; color: #555; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Reset All Filters</button>';
		
		html += '</div>'; // End sidebar
		
		// Content area
		html += '<div>';
		
		// Toolbar
		html += '<div style="background: #fff; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">';
		html += '<span style="font-size: 13px; color: #666;">Showing 1-4 of 48 products</span>';
		html += '<div style="display: flex; gap: 8px;">';
		html += '<button style="padding: 6px 12px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Grid</button>';
		html += '<button style="padding: 6px 12px; background: #fff; color: #555; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; cursor: pointer;">List</button>';
		html += '</div></div>';
		
		// Product grid
		html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">';
		
		// Sample products
		var products = [
			{ name: 'Wireless Headphones', price: '$149.00', oldPrice: '$199.00', badge: 'Sale', color1: '#667eea', color2: '#764ba2' },
			{ name: 'Fashion Theme', price: '$59.00', oldPrice: '', badge: '', color1: '#f093fb', color2: '#f5576c' },
			{ name: 'Audio Bundle', price: '$29.00', oldPrice: '', badge: '', color1: '#4facfe', color2: '#00f2fe' },
			{ name: 'Green Energy Kit', price: '$45.00', oldPrice: '', badge: 'New', color1: '#43e97b', color2: '#38f9d7' }
		];
		
		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">';
			html += '<div style="position: relative; width: 100%; height: 120px; background: linear-gradient(135deg, ' + p.color1 + ' 0%, ' + p.color2 + ' 100%);">';
			if (p.badge) {
				html += '<span style="position: absolute; top: 8px; left: 8px; background: rgba(255,255,255,0.95); padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; color: ' + p.color1 + ';">' + p.badge + '</span>';
			}
			html += '</div>';
			html += '<div style="padding: 12px;">';
			html += '<h4 style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600;">' + p.name + '</h4>';
			if (p.oldPrice) {
				html += '<div style="font-size: 16px; font-weight: 700; color: #1a1a1a;"><del style="font-size: 12px; color: #999; font-weight: 400; margin-right: 4px;">' + p.oldPrice + '</del>' + p.price + '</div>';
			} else {
				html += '<div style="font-size: 16px; font-weight: 700; color: #1a1a1a;">' + p.price + '</div>';
			}
			html += '<div style="display: flex; gap: 6px; margin-top: 10px;">';
			html += '<button style="flex: 1; padding: 8px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">Add to Cart</button>';
			html += '<button style="padding: 8px 12px; background: #f0f0f1; color: #555; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">View</button>';
			html += '</div></div></div>';
		}
		
		html += '</div>'; // End product grid
		
		// Pagination
		html += '<div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;" disabled>&laquo; Previous</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #4f46e5; color: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">1</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">2</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">3</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">Next &raquo;</button>';
		html += '</div>';
		
		html += '</div>'; // End content area
		html += '</div>'; // End layout
		
		// PRO badge notice
		html += '<div style="margin-top: 20px; padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%); border: 1px solid #7dd3fc; border-radius: 8px; text-align: center;">';
		html += '<div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">';
		html += '<span style="padding: 4px 12px; background: linear-gradient(135deg, #4f46e5, #4338ca); color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; border-radius: 4px; text-transform: uppercase;">PRO</span>';
		html += '<span style="color: #1e40af; font-weight: 600; font-size: 14px;">Advanced Filtering Features</span>';
		html += '</div>';
		html += '<p style="margin: 0; color: #64748b; font-size: 13px;">Real-time AJAX filtering, category tree with nested children, price range slider, rating filters, grid/list view toggle, and more!</p>';
		html += '</div>';
		
		html += '</div>';
		return html;
	}

	function generatePriceRangeFilterPreview() {
		var html = '<div class="shortcodeglut-preview-demo" style="padding: 16px; background: #f9fafb;">';

		// Layout with sidebar and grid
		html += '<div style="display: grid; grid-template-columns: 240px 1fr; gap: 20px; align-items: start;">';

		// Sidebar
		html += '<div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.06);">';

		// Price Range section
		html += '<div style="margin-bottom: 20px; padding-bottom: 18px; border-bottom: 1px solid #f0f0f1;">';
		html += '<h4 style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 14px 0; color: #6b7280;">Price Range</h4>';
		html += '<div style="padding: 8px 0;">';
		// Range slider
		html += '<div style="position: relative; height: 4px; background: #e5e7eb; border-radius: 2px; margin: 20px 6px 16px;">';
		html += '<div style="position: absolute; height: 100%; background: #4f46e5; border-radius: 2px; left: 10%; right: 30%;"></div>';
		html += '<div style="position: absolute; width: 16px; height: 16px; background: #4f46e5; border-radius: 50%; top: 50%; transform: translate(-50%, -50%); cursor: pointer; box-shadow: 0 1px 4px rgba(79,70,229,0.4); left: 10%;"></div>';
		html += '<div style="position: absolute; width: 16px; height: 16px; background: #4f46e5; border-radius: 50%; top: 50%; transform: translate(-50%, -50%); cursor: pointer; box-shadow: 0 1px 4px rgba(79,70,229,0.4); left: 70%;"></div>';
		html += '</div>';
		// Min/max inputs
		html += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">';
		html += '<div style="flex: 1; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; text-align: center; color: #374151; background: #f9fafb;">$50</div>';
		html += '<span style="color: #9ca3af; font-size: 13px;">&ndash;</span>';
		html += '<div style="flex: 1; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; text-align: center; color: #374151; background: #f9fafb;">$350</div>';
		html += '</div>';
		html += '</div></div>';

		// Results section
		html += '<div>';
		html += '<h4 style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 10px 0; color: #6b7280;">Selected Range</h4>';
		html += '<div style="text-align: center; padding: 6px 0;">';
		html += '<div style="font-size: 20px; font-weight: 800; color: #4f46e5; line-height: 1.2;">$50 &ndash; $350</div>';
		html += '<div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">8 products found</div>';
		html += '</div></div>';

		html += '</div>'; // End sidebar

		// Product grid - 2 columns
		html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">';

		var products = [
			{ name: 'Basic Pack', price: '$59.00', color1: '#4f46e5', color2: '#7c3aed' },
			{ name: 'Standard Pack', price: '$99.00', color1: '#f093fb', color2: '#f5576c' },
			{ name: 'Premium Pack', price: '$149.00', color1: '#4facfe', color2: '#00f2fe' },
			{ name: 'Elite Pack', price: '$199.00', color1: '#43e97b', color2: '#38f9d7' }
		];

		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,0.06); transition: all 0.3s;">';
			// Colored header area
			html += '<div style="height: 80px; background: linear-gradient(135deg, ' + p.color1 + ' 0%, ' + p.color2 + ' 100%); display: flex; align-items: center; justify-content: center;">';
			html += '<svg viewBox="0 0 24 24" style="width: 32px; height: 32px; fill: rgba(255,255,255,0.85);"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			html += '</div>';
			// Card body
			html += '<div style="padding: 14px;">';
			html += '<h3 style="font-weight: 600; font-size: 14px; margin: 0 0 6px 0; color: #111827;">' + p.name + '</h3>';
			html += '<div style="font-weight: 700; font-size: 18px; color: #4f46e5; margin-bottom: 10px;">' + p.price + '</div>';
			html += '<button style="width: 100%; padding: 8px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;">Add to Cart</button>';
			html += '</div></div>';
		}

		html += '</div>'; // End product grid
		html += '</div>'; // End layout

		// PRO badge notice
		html += '<div style="margin-top: 16px; padding: 12px 16px; background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%); border: 1px solid #bae6fd; border-radius: 8px; text-align: center;">';
		html += '<div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">';
		html += '<span style="padding: 3px 10px; background: linear-gradient(135deg, #4f46e5, #4338ca); color: #fff; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; border-radius: 4px; text-transform: uppercase;">PRO</span>';
		html += '<span style="color: #1e40af; font-weight: 600; font-size: 13px;">Price Range Filter</span>';
		html += '</div>';
		html += '<p style="margin: 0; color: #64748b; font-size: 12px;">Dual-handle price slider with min/max inputs, real-time AJAX product filtering, customizable range and step values</p>';
		html += '</div>';

		html += '</div>';
		return html;
	}


	function generateCompareProductsPreview() {
		var html = '<div class="shortcodeglut-preview-demo" style="padding: 16px; background: #f9fafb;">';

		// Header row with gradient
		html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px 12px 0 0; display: grid; grid-template-columns: 130px repeat(3, 1fr); overflow: hidden;">';
		html += '<div style="padding: 16px; color: rgba(255,255,255,0.7); font-size: 12px; font-weight: 600;">Product</div>';

		var products = [
			{ name: 'Wireless Headphones', price: '$149.00' },
			{ name: 'Smart Watch Pro', price: '$199.00' },
			{ name: 'Bluetooth Speaker', price: '$59.00' }
		];

		for (var i = 0; i < products.length; i++) {
			var p = products[i];
			html += '<div style="padding: 16px; text-align: center; border-left: 1px solid rgba(255,255,255,0.15);">';
			html += '<div style="width: 48px; height: 48px; margin: 0 auto 8px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">';
			html += '<svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: rgba(255,255,255,0.9);"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			html += '</div>';
			html += '<div style="color: #fff; font-weight: 600; font-size: 13px; margin-bottom: 4px;">' + p.name + '</div>';
			html += '<div style="color: #fff; font-weight: 700; font-size: 16px;">' + p.price + '</div>';
			html += '</div>';
		}
		html += '</div>';

		// Feature rows
		var features = [
			{ label: 'Price', values: ['$149.00', '$199.00', '$59.00'] },
			{ label: 'Rating', values: ['\u2605\u2605\u2605\u2605\u2606 (24)', '\u2605\u2605\u2605\u2605\u2605 (42)', '\u2605\u2605\u2605\u2606\u2606 (18)'] },
			{ label: 'SKU', values: ['WH-1000', 'SW-PRO-200', 'BT-SPK-50'] },
			{ label: 'Availability', values: ['<span style="color:#22c55e">In Stock</span>', '<span style="color:#22c55e">In Stock</span>', '<span style="color:#ef4444">Out of Stock</span>'] },
			{ label: 'Weight', values: ['0.5 kg', '0.2 kg', '1.2 kg'] }
		];

		for (var f = 0; f < features.length; f++) {
			var rowBg = f % 2 === 0 ? '#fff' : '#f9fafb';
			html += '<div style="display: grid; grid-template-columns: 130px repeat(3, 1fr); background: ' + rowBg + '; border-bottom: 1px solid #f0f0f1;">';
			html += '<div style="padding: 12px 16px; font-weight: 600; font-size: 12px; color: #6b7280; background: #f9fafb;">' + features[f].label + '</div>';
			for (var v = 0; v < features[f].values.length; v++) {
				html += '<div style="padding: 12px 16px; text-align: center; font-size: 13px; color: #374151; border-left: 1px solid #f0f0f1;">' + features[f].values[v] + '</div>';
			}
			html += '</div>';
		}

		// Add to cart row
		html += '<div style="display: grid; grid-template-columns: 130px repeat(3, 1fr); background: #fff; border-radius: 0 0 12px 12px; overflow: hidden;">';
		html += '<div style="padding: 12px 16px; background: #f9fafb;"></div>';
		var cartBtns = ['Add to Cart', 'Add to Cart', 'Read More'];
		var cartColors = ['#4f46e5', '#4f46e5', '#9ca3af'];
		for (var b = 0; b < cartBtns.length; b++) {
			html += '<div style="padding: 12px 16px; text-align: center; border-left: 1px solid #f0f0f1;">';
			html += '<button style="width: 100%; padding: 8px; background: ' + cartColors[b] + '; color: #fff; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">' + cartBtns[b] + '</button>';
			html += '</div>';
		}
		html += '</div>';

		// PRO badge notice
		html += '<div style="margin-top: 16px; padding: 12px 16px; background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%); border: 1px solid #bae6fd; border-radius: 8px; text-align: center;">';
		html += '<div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">';
		html += '<span style="padding: 3px 10px; background: linear-gradient(135deg, #4f46e5, #4338ca); color: #fff; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; border-radius: 4px; text-transform: uppercase;">PRO</span>';
		html += '<span style="color: #1e40af; font-weight: 600; font-size: 13px;">Compare Products</span>';
		html += '</div>';
		html += '<p style="margin: 0; color: #64748b; font-size: 12px;">Side-by-side product comparison with features, pricing, ratings, stock status, and add-to-cart buttons</p>';
		html += '</div>';

		html += '</div>';
		return html;
	}

		function generateTablePreview() {
                var html = '<div class="shortcodeglut-preview-demo">';

		
		// Header
		;
		
		// Table
		html += '<div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
		
		// Table header
		html += '<div style="display: grid; grid-template-columns: 50px 1fr 100px 100px 100px; padding: 12px 16px; background: #f8f9fa; font-weight: 600; font-size: 12px; color: #555; border-bottom: 1px solid #eee;">';
		html += '<span></span>';
		html += '<span>Product</span>';
		html += '<span>Price</span>';
		html += '<span>Date</span>';
		html += '<span>Actions</span>';
		html += '</div>';
		
		// Sample rows
		var products = [
			{ name: 'Premium Images Pack', price: '$99.00', date: 'Nov 20, 2023' },
			{ name: 'E-Commerce Theme', price: '$59.00', date: 'Jan 31, 2023' },
			{ name: 'Audio Songs Bundle', price: '$29.00', date: 'Dec 13, 2023' },
			{ name: 'PDF Template Pack', price: '$19.00', date: 'May 22, 2023' }
		];
		
		products.forEach(function(product) {
			html += '<div style="display: grid; grid-template-columns: 50px 1fr 100px 100px 100px; padding: 12px 16px; border-bottom: 1px solid #f5f5f5; align-items: center;">';
			html += '<div style="width: 36px; height: 36px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 6px;"></div>';
			html += '<div style="font-weight: 600; font-size: 14px; color: #333;">' + product.name + '</div>';
			html += '<div style="font-weight: 700; font-size: 14px; color: #2271b1;">' + product.price + '</div>';
			html += '<div style="font-size: 12px; color: #666;">' + product.date + '</div>';
			html += '<div style="display: flex; gap: 6px;">';
			html += '<button style="padding: 6px 10px; background: #f0f0f1; border: none; border-radius: 4px; font-size: 11px; cursor: pointer;">View</button>';
			html += '<button style="padding: 6px 10px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 11px; cursor: pointer;">Add</button>';
			html += '</div>';
			html += '</div>';
		});
		
		html += '</div>'; // End table
		html += '</div>';
		
		return html;
	}

	function generateBasicGridPreview() {
		var html = '<div class="shortcodeglut-preview-demo">';
		
		// Toolbar
		html += '<div style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
		html += '<select style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">';
		html += '<option>Sort by: Latest</option>';
		html += '<option>Sort by: Price Low</option>';
		html += '<option>Sort by: Price High</option>';
		html += '</select>';
		html += '<div style="font-size: 13px; color: #666;">Showing 4 of 12 products</div>';
		html += '</div>';
		
		// Grid
		html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">';
		
		var products = [
			{ name: 'Premium Images Pack', excerpt: 'High-quality images', price: '$99.00', gradient: 'linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)' },
			{ name: 'Fashion Theme', excerpt: 'Beautiful WordPress theme', price: '<del>$79</del> $59.00', gradient: 'linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%)' },
			{ name: 'Audio Bundle', excerpt: 'Royalty-free music', price: '$29.00', gradient: 'linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%)' },
			{ name: 'Green Energy Kit', excerpt: 'Sustainable design resources', price: '$45.00', gradient: 'linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%)' }
		];
		
		products.forEach(function(product) {
			html += '<div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">';
			html += '<div style="height: 120px; background: ' + product.gradient + '; display: flex; align-items: center; justify-content: center;">';
			html += '<svg viewBox="0 0 24 24" style="width: 40px; height: 40px; fill: rgba(102, 126, 234, 0.5);"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			html += '</div>';
			html += '<div style="padding: 12px;">';
			html += '<div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">' + product.name + '</div>';
			html += '<div style="font-size: 12px; color: #666; margin-bottom: 8px;">' + product.excerpt + '</div>';
			html += '<div style="display: flex; justify-content: space-between; align-items: center;">';
			html += '<div style="font-weight: 700; font-size: 16px; color: #2271b1;">' + product.price + '</div>';
			html += '<button style="padding: 6px 12px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">Add</button>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
		});
		
		html += '</div>'; // End grid
		
		// Pagination
		html += '<div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">&laquo; Prev</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #2271b1; color: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">1</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">2</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">3</button>';
		html += '<button style="padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px;">Next &raquo;</button>';
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
	window.showDetailsModal = showDetailsModal;

	function generateCategoryTreePreview() {
		var html = '';
		html += '<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">';
		html += '<div class="shortcodeglut-tree-container" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">';
		html += '<h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #1a1a1a;">Browse by Category</h2>';
		html += '<ul style="list-style: none; margin: 0; padding: 0;">';

		// Electronics
		html += '<li style="margin: 0;">';
		html += '<div style="display: flex; align-items: center; padding: 12px 16px; border-radius: 8px; cursor: pointer; background: #f0f0f1;">';
		html += '<svg style="width: 20px; height: 20px; margin-right: 12px; fill: #666;" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';
		html += '<div style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">';
		html += '<svg style="width: 16px; height: 16px; fill: #fff;" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		html += '</div>';
		html += '<span style="flex: 1; font-size: 15px;">Electronics</span>';
		html += '<span style="font-size: 12px; color: #888; background: #f0f0f1; padding: 4px 10px; border-radius: 12px; margin-left: 12px;">24</span>';
		html += '</div>';
		html += '<ul style="list-style: none; padding-left: 32px; margin-top: 8px;">';
		html += '<li><a href="#" style="display: flex; align-items: center; padding: 10px 16px; color: #333; text-decoration: none; border-radius: 8px;"><span style="flex: 1;">Computers & Laptops</span><span style="font-size: 12px; color: #888; background: #f0f0f1; padding: 4px 10px; border-radius: 12px;">8</span></a></li>';
		html += '<li><a href="#" style="display: flex; align-items: center; padding: 10px 16px; color: #333; text-decoration: none; border-radius: 8px;"><span style="flex: 1;">Smartphones</span><span style="font-size: 12px; color: #888; background: #f0f0f1; padding: 4px 10px; border-radius: 12px;">6</span></a></li>';
		html += '</ul>';
		html += '</li>';

		// Fashion
		html += '<li style="margin: 0; margin-top: 8px;">';
		html += '<a href="#" style="display: flex; align-items: center; padding: 12px 16px; color: #333; text-decoration: none; border-radius: 8px;">';
		html += '<div style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">';
		html += '<svg style="width: 16px; height: 16px; fill: #fff;" viewBox="0 0 24 24"><path d="M12 2l-5.5 9h11z"/></svg>';
		html += '</div>';
		html += '<span style="flex: 1; font-size: 15px;">Fashion</span>';
		html += '<span style="font-size: 12px; color: #888; background: #f0f0f1; padding: 4px 10px; border-radius: 12px; margin-left: 12px;">18</span>';
		html += '</a>';
		html += '</li>';

		html += '</ul></div></div>';
		return html;
	}

		function generateSideonePreview() {
			var html = '';
			html += '<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">';
			
			// Product 1 - Image Left
			html += '<div style="display: flex; align-items: center; gap: 40px; margin-bottom: 60px; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">';
			html += '<div style="flex: 0 0 50%;">';
			html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; height: 280px; display: flex; align-items: center; justify-content: center;">';
			html += '<span style="color: #fff; font-size: 48px;">📦</span>';
			html += '</div>';
			html += '</div>';
			html += '<div style="flex: 1; padding: 0 20px;">';
			html += '<h3 style="font-size: 24px; font-weight: 700; margin: 0 0 15px 0; line-height: 1.3;">Premium Product Pack</h3>';
			html += '<p style="color: #666; margin: 0 0 20px 0; line-height: 1.6; font-size: 15px;">High-quality digital products for your next project. Includes templates, assets, and more.</p>';
			html += '<div style="font-size: 22px; font-weight: 700; color: #2271b1; margin-bottom: 20px;">$99.00</div>';
			html += '<a href="#" style="display: inline-block; padding: 12px 28px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">Add to Cart</a>';
			html += '</div>';
			html += '</div>';
			
			// Product 2 - Image Right
			html += '<div style="display: flex; align-items: center; gap: 40px; margin-bottom: 60px; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); flex-direction: row-reverse;">';
			html += '<div style="flex: 0 0 50%;">';
			html += '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; height: 280px; display: flex; align-items: center; justify-content: center;">';
			html += '<span style="color: #fff; font-size: 48px;">🎨</span>';
			html += '</div>';
			html += '</div>';
			html += '<div style="flex: 1; padding: 0 20px;">';
			html += '<h3 style="font-size: 24px; font-weight: 700; margin: 0 0 15px 0; line-height: 1.3;">Creative Design Bundle</h3>';
			html += '<p style="color: #666; margin: 0 0 20px 0; line-height: 1.6; font-size: 15px;">Beautiful design assets for creative professionals. Icons, illustrations, and templates included.</p>';
			html += '<div style="font-size: 22px; font-weight: 700; color: #2271b1; margin-bottom: 20px;">$79.00</div>';
			html += '<a href="#" style="display: inline-block; padding: 12px 28px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">Add to Cart</a>';
			html += '</div>';
			html += '</div>';
			
			// Product 3 - Image Left
			html += '<div style="display: flex; align-items: center; gap: 40px; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">';
			html += '<div style="flex: 0 0 50%;">';
			html += '<div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 8px; height: 280px; display: flex; align-items: center; justify-content: center;">';
			html += '<span style="color: #fff; font-size: 48px;">🚀</span>';
			html += '</div>';
			html += '</div>';
			html += '<div style="flex: 1; padding: 0 20px;">';
			html += '<h3 style="font-size: 24px; font-weight: 700; margin: 0 0 15px 0; line-height: 1.3;">Startup Toolkit</h3>';
			html += '<p style="color: #666; margin: 0 0 20px 0; line-height: 1.6; font-size: 15px;">Everything you need to launch your next big idea. Business templates, guides, and resources.</p>';
			html += '<div style="font-size: 22px; font-weight: 700; color: #2271b1; margin-bottom: 20px;">$149.00</div>';
			html += '<a href="#" style="display: inline-block; padding: 12px 28px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">Add to Cart</a>';
			html += '</div>';
			html += '</div>';
			
			html += '</div>';
			return html;
		}
	function generateMinimalListPreview() {
		var html = '';
		html += '<div style="max-width: 900px; margin: 0 auto; padding: 40px 20px;">';
		html += '<div style="text-align: center; margin-bottom: 48px;">';
		html += '<h1 style="font-size: 32px; font-weight: 300; color: #1a1a1a; margin-bottom: 8px;">Products</h1>';
		html += '<p style="font-size: 15px; color: #888; margin: 0;">Browse our collection</p>';
		html += '</div>';

		html += '<ul style="list-style: none; margin: 0; padding: 0;">';

		var products = [
			{ title: 'Premium Images Pack', desc: 'High-quality images for your projects', price: '$99.00' },
			{ title: 'Fashion Theme', desc: 'Beautiful WordPress theme for fashion stores', price: '$59.00', sale: '$79' },
			{ title: 'Audio Bundle', desc: 'Royalty-free music collection', price: '$29.00' },
			{ title: 'PDF Template Pack', desc: 'Professional PDF templates', price: '$19.00' },
		];

		products.forEach(function(product) {
			html += '<li style="border-bottom: 1px solid #f0f0f1;">';
			html += '<a href="#" style="display: flex; justify-content: space-between; align-items: center; padding: 24px 0; text-decoration: none; color: inherit; transition: padding 0.2s;">';
			html += '<div>';
			html += '<div style="font-size: 18px; font-weight: 500; color: #1a1a1a; margin-bottom: 4px;">' + product.title + '</div>';
			html += '<div style="font-size: 14px; color: #888;">' + product.desc + '</div>';
			html += '</div>';
			html += '<div style="display: flex; align-items: center; gap: 20px;">';
			html += '<div style="font-size: 20px; font-weight: 600; color: #2271b1;">';
			if (product.sale) {
				html += '<del style="font-size: 15px; color: #999; font-weight: 400; margin-right: 6px;">' + product.sale + '</del>' + product.price;
			} else {
				html += product.price;
			}
			html += '</div>';
			html += '<svg style="width: 24px; height: 24px; fill: #ccc;" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';
			html += '</div>';
			html += '</a>';
			html += '</li>';
		});

		html += '</ul>';
		html += '</div>';
		return html;
	}

	function generateCardGridPreview() {
		var html = '';
		html += '<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">';
		html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">';

		var products = [
			{ title: 'Premium Images Pack', desc: 'High-quality images for all your creative projects.', price: '$99.00', badge: 'Best Seller' },
			{ title: 'Fashion Theme', desc: 'Beautiful WordPress theme for fashion and lifestyle stores.', price: '$59.00', sale: '$79' },
			{ title: 'Audio Bundle', desc: 'Royalty-free music collection for videos and podcasts.', price: '$29.00' },
		];

		products.forEach(function(product) {
			html += '<div style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">';
			html += '<div style="width: 100%; height: 220px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; position: relative;">';
			if (product.badge) {
				html += '<span style="position: absolute; top: 16px; left: 16px; background: rgba(255,255,255,0.95); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #667eea;">' + product.badge + '</span>';
			}
			html += '<svg style="width: 72px; height: 72px; fill: rgba(255,255,255,0.3);" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			html += '</div>';
			html += '<div style="padding: 24px;">';
			html += '<h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #1a1a1a;">' + product.title + '</h3>';
			html += '<p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 16px;">' + product.desc + '</p>';
			html += '<div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #f0f0f1;">';
			html += '<div style="font-size: 22px; font-weight: 700; color: #2271b1;">';
			if (product.sale) {
				html += '<del style="font-size: 16px; color: #999; font-weight: 400; margin-right: 6px;">' + product.sale + '</del>' + product.price;
			} else {
				html += product.price;
			}
			html += '</div>';
			html += '<button style="padding: 10px 20px; background: #2271b1; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">Add to Cart</button>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
		});

		html += '</div>';
		html += '</div>';
		return html;
	}

	function generateMasonryGridPreview() {
		var html = '';
		html += '<div style="max-width: 1400px; margin: 0 auto; padding: 20px;">';
		html += '<div style="column-count: 4; column-gap: 20px;">';

		var items = [
			{ height: '240px', title: 'Premium Images Pack', desc: 'High-quality images for all your creative projects. Perfect for websites, social media, and print.', price: '$99.00', tag: 'Featured' },
			{ height: '120px', title: 'Fashion Theme', desc: 'Beautiful WordPress theme', price: '$59.00', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
			{ height: '160px', title: 'Audio Bundle', desc: 'Royalty-free music collection', price: '$29.00', tag: 'New', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
			{ height: '220px', title: 'Green Energy Kit', desc: 'Sustainable design resources for eco-friendly projects.', price: '$45.00', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
		];

		items.forEach(function(item) {
			html += '<div style="break-inside: avoid; margin-bottom: 20px;">';
			html += '<div style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">';
			html += '<div style="width: 100%; height: ' + item.height + '; background: ' + (item.gradient || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)') + '; display: flex; align-items: center; justify-content: center; position: relative;">';
			if (item.tag) {
				html += '<span style="position: absolute; top: 12px; right: 12px; background: rgba(255,255,255,0.95); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">' + item.tag + '</span>';
			}
			html += '<svg style="width: 56px; height: 56px; fill: rgba(255,255,255,0.4);" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			html += '</div>';
			html += '<div style="padding: 20px;">';
			html += '<h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px; line-height: 1.3;">' + item.title + '</h3>';
			html += '<p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 16px;">' + item.desc + '</p>';
			html += '<div style="font-size: 24px; font-weight: 800; color: #2271b1; margin-bottom: 12px;">' + item.price + '</div>';
			html += '<button style="width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Add to Cart</button>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
		});

		html += '</div>';
		html += '</div>';
		return html;
	}

		function generateTabsPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 20px; background: #f9fafb;">';
			html += '<div style="margin-bottom: 20px;">';
			html += '<div style="display: flex; gap: 8px; padding: 8px 8px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px 8px 0 0;">';
			html += '<button style="padding: 10px 20px; background: #fff; color: #667eea; border: none; border-radius: 6px 6px 0 0; font-weight: 600; font-size: 13px; cursor: pointer;">Electronics</button>';
			html += '<button style="padding: 10px 20px; background: transparent; color: #fff; border: none; font-weight: 500; font-size: 13px; cursor: pointer; opacity: 0.9;">Clothing</button>';
			html += '<button style="padding: 10px 20px; background: transparent; color: #fff; border: none; font-weight: 500; font-size: 13px; cursor: pointer; opacity: 0.9;">Accessories</button>';
			html += '</div></div>';
			html += '<div style="background: #fff; padding: 20px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">';
			html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">';

			var products = [
				{ name: 'Wireless Headphones', price: '$149.00', color: '#667eea' },
				{ name: 'Smart Watch Pro', price: '$199.00', color: '#f093fb' },
				{ name: 'Bluetooth Speaker', price: '$59.00', color: '#4facfe' },
				{ name: 'Laptop Stand', price: '$35.00', color: '#43e97b' }
			];

			for (var i = 0; i < products.length; i++) {
				var p = products[i];
				html += '<div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">';
				html += '<div style="width: 100%; height: 140px; background: linear-gradient(135deg, ' + p.color + ' 0%, ' + p.color + 'dd 100%);"></div>';
				html += '<div style="padding: 12px;">';
				html += '<h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #111827;">' + p.name + '</h4>';
				html += '<div style="color: #059669; font-weight: 600; font-size: 14px;">' + p.price + '</div>';
				html += '<button style="width: 100%; margin-top: 10px; padding: 8px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">Add to Cart</button>';
				html += '</div></div>';
			}

			html += '</div></div></div>';
			return html;
		}

		function generateCarouselPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f9fafb;">';
			html += '<div style="margin-bottom: 20px;">';
			html += '<h3 style="margin: 0 0 4px 0; color: #111827; font-size: 20px;">Featured Products</h3>';
			html += '<p style="margin: 0; color: #6b7280; font-size: 13px;">Slide through our collection</p>';
			html += '</div>';
			html += '<div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';
			html += '<div style="display: flex; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 200px;">';
			html += '<div style="flex: 1; padding: 32px; color: #fff;">';
			html += '<span style="display: inline-block; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 12px; font-size: 11px; font-weight: 600; margin-bottom: 12px;">BEST SELLER</span>';
			html += '<h2 style="margin: 0 0 8px 0; font-size: 24px;">Wireless Headphones</h2>';
			html += '<p style="margin: 0 0 16px 0; opacity: 0.9; font-size: 13px;">Premium sound quality with noise cancellation</p>';
			html += '<div style="font-size: 20px; font-weight: 700; margin-bottom: 16px;">$149.00</div>';
			html += '<button style="padding: 10px 24px; background: #fff; color: #667eea; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Add to Cart</button>';
			html += '</div>';
			html += '<div style="flex: 0 0 40%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1);">';
			html += '<div style="width: 120px; height: 120px; background: rgba(255,255,255,0.2); border-radius: 12px;"></div>';
			html += '</div></div>';
			html += '<button style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); width: 36px; height: 36px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; font-size: 18px;">&lsaquo;</button>';
			html += '<button style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); width: 36px; height: 36px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; font-size: 18px;">&rsaquo;</button>';
			html += '<div style="position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px;">';
			html += '<span style="width: 8px; height: 8px; border-radius: 50%; background: #fff;"></span>';
			html += '<span style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5);"></span>';
			html += '<span style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5);"></span>';
			html += '</div>';
			html += '</div></div>';
			return html;
		}

		function generateKanbanPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f9fafb;">';
			html += '<div style="margin-bottom: 20px; text-align: center;">';
			html += '<h3 style="margin: 0 0 4px 0; color: #111827; font-size: 20px;">Kanban Board</h3>';
			html += '<p style="margin: 0; color: #6b7280; font-size: 13px;">Drag and drop products between columns</p>';
			html += '</div>';

			html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">';

			var columns = [
				{ title: 'Featured', color: '#667eea', products: [
					{ name: 'Wireless Headphones', price: '$149.00', tag: 'Hot' },
					{ name: 'Smart Watch Pro', price: '$199.00', tag: 'New' }
				]},
				{ title: 'New Arrivals', color: '#4facfe', products: [
					{ name: 'Bluetooth Speaker', price: '$59.00', tag: '' },
					{ name: 'USB-C Hub', price: '$79.00', tag: 'Sale' }
				]},
				{ title: 'On Sale', color: '#f5576c', products: [
					{ name: 'Laptop Stand', price: '$35.00', tag: '30% Off' },
					{ name: 'Wireless Mouse', price: '$29.99', tag: '' }
				]}
			];

			for (var c = 0; c < columns.length; c++) {
				var col = columns[c];
				html += '<div style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">';
				html += '<div style="padding: 12px 16px; background: linear-gradient(135deg, ' + col.color + ' 0%, ' + col.color + 'cc 100%); color: #fff;">';
				html += '<h4 style="margin: 0; font-size: 14px; font-weight: 600;">' + col.title + '</h4>';
				html += '</div>';
				html += '<div style="padding: 12px;">';

				for (var p = 0; p < col.products.length; p++) {
					var prod = col.products[p];
					html += '<div style="padding: 12px; margin-bottom: 8px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">';
					if (prod.tag) {
						html += '<span style="display: inline-block; padding: 2px 8px; background: ' + col.color + '; color: #fff; border-radius: 10px; font-size: 10px; font-weight: 600; margin-bottom: 6px;">' + prod.tag + '</span>';
					}
					html += '<div style="font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 4px;">' + prod.name + '</div>';
					html += '<div style="font-size: 14px; font-weight: 700; color: ' + col.color + ';">' + prod.price + '</div>';
					html += '</div>';
				}

				html += '<button style="width: 100%; padding: 8px; background: #f3f4f6; color: #6b7280; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">Load More</button>';
				html += '</div></div>';
			}

			html += '</div></div>';
			return html;
		}

		function generateAccordionPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 20px; background: #f8f9fa;">';

			html += '<div style="max-width: 700px; margin: 0 auto;">';
			html += '<div style="text-align: center; margin-bottom: 24px;">';
			html += '<h2 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 800; color: #1a1a2e;">Shortcode Demo</h2>';
			html += '</div>';

			html += '<div style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">';

			var items = [
				{
					name: 'Premium Images Pack', meta: '24 files &bull; 1.2 GB &bull; Digital Download', price: '$99',
					gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
					excerpt: 'High-quality images for all your creative projects. Perfect for websites, social media, and print materials.',
					features: ['4K Resolution', 'PNG + JPG', 'Commercial License'],
					active: true
				},
				{
					name: 'Fashion Theme', meta: 'WordPress Theme &bull; 5 MB &bull; Instant Download', price: '$59',
					gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
					excerpt: 'Beautiful WordPress theme for fashion and lifestyle stores. Fully responsive and customizable.',
					features: ['One-Click Demo', 'WooCommerce Ready', 'SEO Optimized'],
					active: false
				},
				{
					name: 'Audio Bundle', meta: '50 tracks &bull; 500 MB &bull; MP3 + WAV', price: '$29',
					gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
					excerpt: 'Royalty-free music collection for videos, podcasts, and multimedia projects.',
					features: [],
					active: false
				},
				{
					name: 'Video Course', meta: '24 videos &bull; 4 GB &bull; Lifetime Access', price: '$149',
					gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
					excerpt: 'Complete web development course for beginners. Learn HTML, CSS, JavaScript, and more.',
					features: ['24 Hours Video', 'Source Files', 'Certificate'],
					active: false
				}
			];

			for (var i = 0; i < items.length; i++) {
				var item = items[i];

				html += '<div style="' + (i < items.length - 1 ? 'border-bottom: 1px solid #f0f0f1;' : '') + '">';

				// Header
				html += '<div style="display: flex; align-items: center; padding: 18px 24px;">';
				html += '<div style="width: 48px; height: 48px; background: ' + item.gradient + '; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px; flex-shrink: 0;">';
				html += '<svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: #fff;"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
				html += '</div>';
				html += '<div style="flex: 1; min-width: 0;">';
				html += '<div style="font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 2px;">' + item.name + '</div>';
				html += '<div style="font-size: 12px; color: #888;">' + item.meta + '</div>';
				html += '</div>';
				html += '<div style="font-size: 20px; font-weight: 800; color: #2271b1; margin-right: 12px; white-space: nowrap;">' + item.price + '</div>';

				if (item.active) {
					html += '<div style="width: 36px; height: 36px; border: 2px solid #2271b1; background: #2271b1; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">';
					html += '<svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #fff; transform: rotate(180deg);"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>';
				} else {
					html += '<div style="width: 36px; height: 36px; border: 2px solid #e0e0e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">';
					html += '<svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #666;"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>';
				}
				html += '</div>';
				html += '</div>';

				// Expanded body (only for active item)
				if (item.active) {
					html += '<div style="padding: 0 24px 20px 88px;">';
					html += '<p style="font-size: 14px; color: #666; line-height: 1.6; margin: 0 0 12px 0;">' + item.excerpt + '</p>';
					if (item.features.length > 0) {
						html += '<div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">';
						for (var f = 0; f < item.features.length; f++) {
							html += '<span style="background: #f0f0f1; padding: 4px 10px; border-radius: 6px; font-size: 12px; color: #555;">' + item.features[f] + '</span>';
						}
						html += '</div>';
					}
					html += '<div style="display: flex; gap: 10px;">';
					html += '<span style="padding: 10px 20px; background: #2271b1; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Add to Cart</span>';
					html += '<span style="padding: 10px 20px; background: #f0f0f1; color: #555; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">View Product</span>';
					html += '</div>';
					html += '</div>';
				}

				html += '</div>';
			}

			html += '</div></div></div>';
			return html;
		}

		function generateTimelinePreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 20px; background: #f8f9fa;">';
			html += '<div style="max-width: 800px; margin: 0 auto;">';
			html += '<div style="text-align: center; margin-bottom: 24px;">';
			html += '<h2 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 800; color: #1a1a2e;">Release Timeline</h2>';
			html += '</div>';
			html += '<div style="position: relative; padding: 20px 0;">';
			html += '<div style="position: absolute; left: 50%; transform: translateX(-50%); width: 3px; height: 100%; background: linear-gradient(180deg, #667eea 0%, #764ba2 50%, #f093fb 100%); border-radius: 3px;"></div>';

			var items = [
				{ date: 'December 2025', name: 'Video Course Pro', excerpt: 'Complete web development course with 24 hours of video content, source files, and lifetime access.', price: '$149.00', btn: 'Enroll Now', side: 'left', dotColor: '#667eea' },
				{ date: 'November 2025', name: 'UI Component Set', excerpt: '200+ ready-to-use UI components for modern web applications. Fully customizable and responsive.', price: '$69.00', btn: 'Add to Cart', side: 'right', dotColor: '#764ba2' },
				{ date: 'October 2025', name: 'Icon Pack Pro', excerpt: '1000+ premium icons in multiple formats. Perfect for web, mobile, and print projects.', price: '$39.00', btn: 'Add to Cart', side: 'left', dotColor: '#f093fb' },
				{ date: 'September 2025', name: 'Premium Images Pack', excerpt: 'High-quality 4K images for all your creative projects. Includes commercial license.', price: '$99.00', btn: 'Add to Cart', side: 'right', dotColor: '#667eea' }
			];

			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				var isLeft = item.side === 'left';

				html += '<div style="position: relative; margin-bottom: 32px;">';
				html += '<div style="position: absolute; left: 50%; transform: translateX(-50%); width: 20px; height: 20px; background: #fff; border: 3px solid ' + item.dotColor + '; border-radius: 50%; z-index: 2; box-shadow: 0 0 0 3px ' + item.dotColor + '33;"></div>';

				var cardMargin = isLeft ? 'margin-right: calc(50% + 24px);' : 'margin-left: calc(50% + 24px);';
				var textAlign = isLeft ? 'text-align: left;' : 'text-align: right;';
				var arrowPos = isLeft ? 'right: -8px; border-width: 8px 0 8px 8px; border-color: transparent transparent transparent #fff;' : 'left: -8px; border-width: 8px 8px 8px 0; border-color: transparent #fff transparent transparent;';

				html += '<div style="' + cardMargin + textAlign + '">';
				html += '<div style="background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); position: relative;">';
				html += '<div style="position: absolute; top: 20px; width: 0; height: 0; border-style: solid; ' + arrowPos + '"></div>';

				var gradients = ['#667eea', '#764ba2', '#f093fb'];
				var gStart = gradients[i % 3];
				var gEnd = gradients[(i + 1) % 3];
				html += '<span style="display: inline-block; background: linear-gradient(135deg, ' + gStart + ' 0%, ' + gEnd + ' 100%); color: #fff; padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">' + item.date + '</span>';

				html += '<div style="font-size: 17px; font-weight: 700; margin-bottom: 6px; color: #2c3e50;">' + item.name + '</div>';
				html += '<div style="font-size: 13px; color: #666; line-height: 1.5; margin-bottom: 12px;">' + item.excerpt + '</div>';
				html += '<div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f0f0f1;">';
				html += '<div style="font-size: 20px; font-weight: 800; color: #2271b1;">' + item.price + '</div>';
				html += '<div style="padding: 8px 16px; background: #2271b1; color: #fff; border-radius: 6px; font-size: 12px; font-weight: 600;">' + item.btn + '</div>';
				html += '</div>';
				html += '</div>';
				html += '</div>';
				html += '</div>';
			}

			html += '</div></div></div>';
			return html;
		}

		function generateZigzagPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 20px; background: #f8f9fa;">';
			html += '<div style="max-width: 900px; margin: 0 auto;">';

			var items = [
				{ name: 'Wireless Headphones', price: '$149.00', excerpt: 'Premium noise-cancelling with 30-hour battery life', features: ['Active Noise Cancellation', '30-Hour Battery', 'Bluetooth 5.2'], color: '#667eea', side: 'left' },
				{ name: 'Smart Watch Pro', price: '$299.00', excerpt: 'Fitness tracking with heart rate monitoring', features: ['Heart Rate Monitor', 'GPS Tracking', 'Water Resistant'], color: '#764ba2', side: 'right' },
				{ name: 'Bluetooth Speaker', price: '$79.00', excerpt: '360-degree sound with deep bass', features: ['360° Sound', '12-Hour Playtime', 'IPX7 Waterproof'], color: '#f093fb', side: 'left' }
			];

			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				var isLeft = item.side === 'left';

				html += '<div style="display: flex; align-items: center; gap: 40px; margin-bottom: 32px;' + (isLeft ? '' : 'flex-direction: row-reverse;') + '">';
				html += '<div style="flex: 1; background: linear-gradient(135deg, ' + item.color + ' 0%, ' + item.color + 'aa 100%); border-radius: 16px; padding: 40px; min-height: 200px; display: flex; align-items: center; justify-content: center;">';
				html += '<div style="font-size: 48px;">🎧</div>';
				html += '</div>';
				html += '<div style="flex: 1;">';
				html += '<div style="display: inline-block; background: ' + item.color + '; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 10px; font-weight: 700; margin-bottom: 12px;">NEW</div>';
				html += '<div style="font-size: 22px; font-weight: 700; margin-bottom: 8px; color: #1a1a2e;">' + item.name + '</div>';
				html += '<div style="font-size: 14px; color: #666; margin-bottom: 16px; line-height: 1.6;">' + item.excerpt + '</div>';
				html += '<div style="margin-bottom: 16px;">';
				for (var j = 0; j < item.features.length; j++) {
					html += '<div style="font-size: 13px; color: #444; margin-bottom: 6px;"><span style="color: ' + item.color + '; margin-right: 6px;">✓</span>' + item.features[j] + '</div>';
				}
				html += '</div>';
				html += '<div style="display: flex; align-items: center; gap: 16px;">';
				html += '<div style="font-size: 24px; font-weight: 800; color: #2271b1;">' + item.price + '</div>';
				html += '<div style="padding: 10px 20px; background: #2271b1; color: #fff; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Add to Cart</div>';
				html += '</div>';
				html += '</div>';
				html += '</div>';
			}

			html += '</div></div>';
			return html;
		}

		function generateDrawerPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f8f9fa;">';
			html += '<div style="max-width: 1000px; margin: 0 auto; display: flex; gap: 24px; height: 400px;">';

			html += '<div style="width: 280px; background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">';
			html += '<div style="font-size: 14px; font-weight: 700; margin-bottom: 16px; color: #1a1a2e; border-bottom: 2px solid #0071e3; padding-bottom: 12px;">Select Product</div>';

			var products = [
				{ name: 'Basic Plan', price: '$29/mo', active: true },
				{ name: 'Pro Plan', price: '$49/mo', active: false },
				{ name: 'Enterprise', price: '$99/mo', active: false },
				{ name: 'Custom', price: 'Contact Us', active: false }
			];

			for (var i = 0; i < products.length; i++) {
				var p = products[i];
				var bg = p.active ? '#0071e3' : '#f5f5f7';
				var color = p.active ? '#fff' : '#1a1a2e';
				html += '<div style="padding: 12px; border-radius: 8px; margin-bottom: 8px; cursor: pointer; background: ' + bg + '; color: ' + color + ';">';
				html += '<div style="font-size: 14px; font-weight: 600;">' + p.name + '</div>';
				html += '<div style="font-size: 12px; opacity: 0.8;">' + p.price + '</div>';
				html += '</div>';
			}
			html += '</div>';

			html += '<div style="flex: 1; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); position: relative; overflow: hidden;">';
			html += '<div style="position: absolute; top: 0; right: 0; background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%); color: #fff; padding: 8px 16px; border-radius: 0 12px 0 16px; font-size: 11px; font-weight: 700;">POPULAR</div>';
			html += '<div style="text-align: center; margin-bottom: 24px;"><div style="font-size: 56px; margin-bottom: 16px;">📦</div></div>';
			html += '<div style="font-size: 28px; font-weight: 700; margin-bottom: 8px; color: #1a1a2e;">Basic Plan</div>';
			html += '<div style="font-size: 18px; color: #0071e3; font-weight: 700; margin-bottom: 16px;">$29<span style="font-size: 14px; color: #666; font-weight: 400;">/month</span></div>';
			html += '<div style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 24px;">Perfect for individuals and small projects. All essential features included.</div>';

			html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; background: #f5f5f7; padding: 16px; border-radius: 8px;">';
			var features = ['5 Projects', '10GB Storage', 'Email Support', 'Basic Analytics', 'SSL Certificate', 'Daily Backups'];
			for (var j = 0; j < features.length; j++) {
				html += '<div style="font-size: 13px; color: #333;"><span style="color: #0071e3; margin-right: 6px;">✓</span>' + features[j] + '</div>';
			}
			html += '</div>';

			html += '<div style="display: flex; gap: 12px;">';
			html += '<div style="flex: 1; padding: 14px; background: #0071e3; color: #fff; border-radius: 8px; text-align: center; font-size: 14px; font-weight: 600; cursor: pointer;">Add to Cart</div>';
			html += '<div style="flex: 1; padding: 14px; background: #f5f5f7; color: #1a1a2e; border-radius: 8px; text-align: center; font-size: 14px; font-weight: 600; cursor: pointer;">View Details</div>';
			html += '</div>';

			html += '</div>';
			html += '</div></div>';
			return html;
		}

		function generateConveyorBeltPreview() {
			var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f8f9fa;">';
			html += '<div style="max-width: 1000px; margin: 0 auto;">';
			html += '<div style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #1a1a2e;">Featured Products - Conveyor Belt</div>';

			html += '<div style="display: flex; gap: 16px; overflow-x: auto; padding: 16px; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">';

			var products = [
				{ name: 'Wireless Headphones', price: '$89', icon: '🎧', badge: 'SALE' },
				{ name: 'Smart Watch', price: '$199', icon: '⌚', badge: 'NEW' },
				{ name: 'Bluetooth Speaker', price: '$59', icon: '🔊', badge: '' },
				{ name: 'Laptop Stand', price: '$45', icon: '💻', badge: 'POPULAR' },
				{ name: 'USB-C Hub', price: '$35', icon: '🔌', badge: '' },
				{ name: 'Webcam HD', price: '$79', icon: '📷', badge: 'NEW' }
			];

			for (var i = 0; i < products.length; i++) {
				var p = products[i];
				html += '<div style="min-width: 180px; background: #f5f5f7; border-radius: 10px; padding: 20px; text-align: center; position: relative;">';
				if (p.badge) {
					html += '<div style="position: absolute; top: 8px; right: 8px; background: #0071e3; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">' + p.badge + '</div>';
				}
				html += '<div style="font-size: 40px; margin-bottom: 12px;">' + p.icon + '</div>';
				html += '<div style="font-size: 14px; font-weight: 600; color: #1a1a2e; margin-bottom: 6px;">' + p.name + '</div>';
				html += '<div style="font-size: 16px; font-weight: 700; color: #0071e3; margin-bottom: 12px;">' + p.price + '</div>';
				html += '<div style="padding: 8px; background: #0071e3; color: #fff; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">Add to Cart</div>';
				html += '</div>';
			}

			html += '</div>';
			html += '<div style="margin-top: 16px; text-align: center; font-size: 13px; color: #666;">← Scroll to see more products →</div>';
			html += '</div></div>';
			return html;
		}

			function generateHorizontalLeftPreview() {
				var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f8f9fa;">';
				html += '<div style="max-width: 1000px; margin: 0 auto;">';
				html += '<div style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #1a1a2e;">Featured Products - Horizontal Layout</div>';

				html += '<div style="background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden;">';

				var products = [
					{ name: 'Wireless Headphones', price: '$149.00', rating: '★★★★★', badge: 'SALE', color: '#667eea' },
					{ name: 'Smart Watch Pro', price: '$199.00', rating: '★★★★☆', badge: 'NEW', color: '#f093fb' },
					{ name: 'Bluetooth Speaker', price: '$59.00', rating: '★★★★★', badge: '', color: '#4facfe' }
				];

				for (var i = 0; i < products.length; i++) {
					var p = products[i];
					html += '<div style="display: flex; padding: 20px; border-bottom: 1px solid #e5e7eb; ' + (i === products.length - 1 ? 'border-bottom: none;' : '') + '">';
					html += '<div style="width: 120px; height: 120px; background: linear-gradient(135deg, ' + p.color + ' 0%, ' + p.color + 'cc 100%); border-radius: 8px; flex-shrink: 0; margin-right: 20px;"></div>';
					html += '<div style="flex: 1;">';
					if (p.badge) {
						html += '<span style="display: inline-block; padding: 4px 10px; background: #f59e0b; color: #fff; border-radius: 4px; font-size: 11px; font-weight: 700; margin-bottom: 8px;">' + p.badge + '</span>';
					}
					html += '<h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700; color: #1a1a2e;">' + p.name + '</h4>';
					html += '<div style="color: #fbbf24; font-size: 14px; margin-bottom: 8px;">' + p.rating + '</div>';
					html += '<div style="color: #059669; font-size: 18px; font-weight: 700; margin-bottom: 12px;">' + p.price + '</div>';
					html += '<button style="padding: 10px 20px; background: #0071e3; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Add to Cart</button>';
					html += '</div></div>';
				}

				html += '</div>';
				html += '</div></div>';
				return html;
			}

			function generateRadialCirclePreview() {
				var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f8f9fa;">';
				html += '<div style="max-width: 700px; margin: 0 auto;">';
				html += '<div style="font-size: 16px; font-weight: 700; margin-bottom: 20px; text-align: center; color: #1a1a2e;">Radial Circle Layout</div>';

				html += '<div style="position: relative; width: 100%; height: 450px; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">';

				// Center product
				html += '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);">';
				html += '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 12px; font-weight: 600; text-align: center;">Featured<br>Product</div>';
				html += '</div>';

				// Orbiting products
				var orbitProducts = [
					{ angle: 0, color: '#f093fb', label: '1' },
					{ angle: 60, color: '#4facfe', label: '2' },
					{ angle: 120, color: '#43e97b', label: '3' },
					{ angle: 180, color: '#f5576c', label: '4' },
					{ angle: 240, color: '#fa709a', label: '5' },
					{ angle: 300, color: '#fee140', label: '6' }
				];

				var radius = 150;
				for (var i = 0; i < orbitProducts.length; i++) {
					var p = orbitProducts[i];
					var rad = p.angle * Math.PI / 180;
					var x = 50 + radius * Math.cos(rad) / 3;
					var y = 50 + radius * Math.sin(rad) / 5;

					html += '<div style="position: absolute; top: ' + y + '%; left: ' + x + '%; width: 70px; height: 70px; background: ' + p.color + '; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">';
					html += '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 18px; font-weight: 700;">' + p.label + '</div>';
					html += '</div>';
				}

				html += '</div>';
				html += '<div style="margin-top: 16px; text-align: center; font-size: 13px; color: #666;">Products orbit around featured center item</div>';
				html += '</div></div>';
				return html;
			}

			function generateBookFlipPreview() {
				var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f8f9fa;">';
				html += '<div style="max-width: 900px; margin: 0 auto;">';
				html += '<div style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #1a1a2e;">Book Flip Gallery</div>';

				html += '<div style="display: flex; gap: 24px; justify-content: center; flex-wrap: wrap;">';

				var books = [
					{ title: 'Wireless Audio', price: '$149', color1: '#667eea', color2: '#764ba2' },
					{ title: 'Smart Watch', price: '$199', color1: '#f093fb', color2: '#f5576c' },
					{ title: 'Bluetooth Speaker', price: '$59', color1: '#4facfe', color2: '#00f2fe' },
					{ title: 'Laptop Stand', price: '$35', color1: '#43e97b', color2: '#38f9d7' }
				];

				for (var i = 0; i < books.length; i++) {
					var b = books[i];
					html += '<div style="position: relative; width: 140px; height: 200px; perspective: 1000px; cursor: pointer;">';
					html += '<div style="position: relative; width: 100%; height: 100%; transform-style: preserve-3d; transition: transform 0.6s;">';
					// Front cover
					html += '<div style="position: absolute; width: 100%; height: 100%; backface-visibility: hidden; background: linear-gradient(135deg, ' + b.color1 + ' 0%, ' + b.color2 + ' 100%); border-radius: 4px 8px 8px 4px; box-shadow: 2px 2px 10px rgba(0,0,0,0.2);">';
					html += '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 13px; font-weight: 600; text-align: center; padding: 10px;">' + b.title + '</div>';
					html += '<div style="position: absolute; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 11px; opacity: 0.8;">Hover to flip</div>';
					html += '</div>';
					// Back cover hint
					html += '<div style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; font-size: 10px; color: #fff;">' + b.price + '</div>';
					html += '</div></div>';
				}

				html += '</div>';
				html += '<div style="margin-top: 16px; text-align: center; font-size: 13px; color: #666;">Hover over books to see details (flip animation)</div>';
				html += '</div></div>';
				return html;
			}

			function generateMagazineGridPreview() {
				var html = '<div class="shortcodeglut-preview-demo" style="padding: 24px; background: #f8f9fa;">';
				html += '<div style="max-width: 1000px; margin: 0 auto;">';
				html += '<div style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #1a1a2e;">Magazine Grid Layout</div>';

				html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 150px; gap: 16px;">';

				var items = [
					{ size: 'hero', title: 'Featured Collection', color: '#667eea', span: '2 / span 2' },
					{ size: 'large', title: 'New Arrivals', color: '#f093fb', span: '1 / span 2' },
					{ size: 'medium', title: 'Sale', color: '#4facfe', span: '1 / span 1' },
					{ size: 'medium', title: 'Popular', color: '#43e97b', span: '1 / span 1' },
					{ size: 'large', title: 'Trending', color: '#f5576c', span: '1 / span 2' },
					{ size: 'medium', title: 'Best Sellers', color: '#fee140', span: '1 / span 1' },
					{ size: 'medium', title: 'Limited', color: '#fa709a', span: '1 / span 1' }
				];

				for (var i = 0; i < items.length; i++) {
					var item = items[i];
					html += '<div style="grid-column: ' + item.span + '; background: linear-gradient(135deg, ' + item.color + ' 0%, ' + item.color + 'cc 100%); border-radius: 8px; overflow: hidden; position: relative;">';
					html += '<div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 16px; background: linear-gradient(transparent, rgba(0,0,0,0.7));">';
					html += '<div style="color: #fff; font-size: 14px; font-weight: 700; margin-bottom: 4px;">' + item.title + '</div>';
					html += '<div style="color: rgba(255,255,255,0.8); font-size: 11px;">View Products →</div>';
					html += '</div></div>';
				}

				html += '</div>';
				html += '<div style="margin-top: 16px; text-align: center; font-size: 13px; color: #666;">Dynamic magazine-style layout with varying sizes</div>';
				html += '</div></div>';
				return html;
			}

		window.showPreviewModal = showPreviewModal;
		window.closeDetailsModal = closeDetailsModal;
		window.closePreviewModal = closePreviewModal;

	});
