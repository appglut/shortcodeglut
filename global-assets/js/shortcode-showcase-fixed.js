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

		window.showPreviewModal = showPreviewModal;
		window.closeDetailsModal = closeDetailsModal;
		window.closePreviewModal = closePreviewModal;

	});
