<?php
/**
 * Shortcode Showcase Admin Page
 *
 * Displays the admin page for shortcode showcase with documentation
 * and examples of available shortcodes
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
	}

	/**
	 * Enqueue assets for admin page
	 */
	public function enqueueAdminAssets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for script loading
		if ( isset( $_GET['page'] ) && 'shortcodeglut' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			// Enqueue styles
			wp_enqueue_style(
				'shortcodeglut-shortcode-showcase',
				SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcode-showcase.css',
				array(),
				SHORTCODEGLUT_VERSION
			);

			// Enqueue scripts
			wp_enqueue_script(
				'shortcodeglut-shortcode-showcase',
				SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcode-showcase.js',
				array( 'jquery' ),
				SHORTCODEGLUT_VERSION,
				true
			);

			// Localize script
			wp_localize_script( 'shortcodeglut-shortcode-showcase', 'shortcodeglutShowcase', array(
				'i18n' => array(
					'description' => esc_html__( 'Description', 'shortcodeglut' ),
					'shortcodeSyntax' => esc_html__( 'Shortcode Syntax', 'shortcodeglut' ),
					'howToUse' => esc_html__( 'How to Use', 'shortcodeglut' ),
					'copyShortcode' => esc_html__( 'Copy the shortcode using the button above or the copy button on the card', 'shortcodeglut' ),
					'pasteShortcode' => esc_html__( 'Paste it into any WordPress page, post, or widget', 'shortcodeglut' ),
					'customizeParams' => esc_html__( 'Customize parameters as needed', 'shortcodeglut' ),
					'createTemplate' => esc_html__( 'For custom styling, create a WooTemplate in the Woo Templates section', 'shortcodeglut' ),
					'availableParams' => esc_html__( 'Available Parameters', 'shortcodeglut' ),
					'parameter' => esc_html__( 'Parameter', 'shortcodeglut' ),
					'searchPlaceholder' => esc_attr__( 'Search...', 'shortcodeglut' ),
					'orderByDate' => esc_html__( 'Order By: Date', 'shortcodeglut' ),
					'orderByTitle' => esc_html__( 'Order By: Title', 'shortcodeglut' ),
					'orderByPrice' => esc_html__( 'Order By: Price', 'shortcodeglut' ),
					'descending' => esc_html__( 'Descending', 'shortcodeglut' ),
					'ascending' => esc_html__( 'Ascending', 'shortcodeglut' ),
					'apply' => esc_html__( 'Apply', 'shortcodeglut' ),
					'title' => esc_html__( 'Title', 'shortcodeglut' ),
					'price' => esc_html__( 'Price', 'shortcodeglut' ),
					'stock' => esc_html__( 'Stock', 'shortcodeglut' ),
					'action' => esc_html__( 'Action', 'shortcodeglut' ),
					'inStock' => esc_html__( 'In Stock', 'shortcodeglut' ),
					'outOfStock' => esc_html__( 'Out of Stock', 'shortcodeglut' ),
					'addToCart' => esc_html__( 'Add to Cart', 'shortcodeglut' ),
					'readMore' => esc_html__( 'Read more', 'shortcodeglut' ),
					'showingProducts' => esc_html__( 'Showing 3 of 25 products', 'shortcodeglut' ),
					'wooCategoryPreview' => esc_html__( 'WooCommerce Category Products Preview', 'shortcodeglut' ),
					'productTablePreview' => esc_html__( 'Product Table Preview', 'shortcodeglut' ),
					'saleProductsPreview' => esc_html__( 'Products On Sale Preview', 'shortcodeglut' ),
					'wirelessHeadphones' => esc_html__( 'Wireless Headphones', 'shortcodeglut' ),
					'smartWatchPro' => esc_html__( 'Smart Watch Pro', 'shortcodeglut' ),
					'bluetoothSpeaker' => esc_html__( 'Bluetooth Speaker', 'shortcodeglut' ),
					'laptopStand' => esc_html__( 'Laptop Stand', 'shortcodeglut' ),
				)
			) );
		}
	}

	/**
	 * Render the shortcode showcase admin page content
	 */
	public function renderShortcodeShowcaseContent() {
		$woo_active = class_exists( 'WooCommerce' );
		?>
		<div class="wrap shopglut-admin-contents">
			<h1 style="text-align: center; font-weight: 600; font-size: 32px; margin: 30px 0;">
				Shortcode Showcase
			</h1>
			<p class="subheading" style="text-align: center; margin-bottom: 30px; margin-top: 8px; color: #6b7280;">
				Display WooCommerce content with powerful shortcodes
			</p>

			<?php if ( ! $woo_active ) : ?>
				<div class="notice notice-warning inline" style="max-width: 800px; margin: 0 auto 30px;">
					<p><?php esc_html_e( 'WooCommerce is not active. Some shortcodes may not work until WooCommerce is activated.', 'shortcodeglut' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="shopg-tab-container">
				<ul class="shopg-tabs">
					<li class="shopg-tab active" data-tab="tab-all" style="font-size: 16px; font-weight: 600;">All Shortcodes</li>
				</ul>

				<div class="shopg-tab-content active" id="tab-all">
					<div class="image-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">

						<?php
						// WooCommerce Category Shortcode
						$this->render_shortcode_card(
							'shopglut_woo_category',
							'WooCommerce Category Products',
							'Query and display products from one or more WooCommerce categories with customizable layout, filtering, and pagination.',
							array(
								'basic' => '[shopglut_woo_category id="your-category-slug"]',
								'advanced' => '[shopglut_woo_category id="electronics,accessories" title="1" desc="1" items_per_page="12" cols="3" toolbar="1"]',
							),
							array(
								'id' => 'Category slug(s) - comma-separated for multiple (required)',
								'cat_field' => 'Field type: slug or id (default: slug)',
								'operator' => 'Query operator: IN, NOT IN, AND (default: IN)',
								'title' => 'Custom title or "1" for category name',
								'desc' => 'Custom description or "1" for category description',
								'icon' => 'Custom icon URL',
								'icon_width' => 'Icon width in pixels (default: 64)',
								'items_per_page' => 'Number of products per page (default: 10)',
								'orderby' => 'Order by: date, title, modified, price, total_sales, menu_order (default: date)',
								'order' => 'Order direction: ASC or DESC (default: DESC)',
								'template' => 'WooTemplate ID for custom product display',
								'toolbar' => 'Show toolbar: 1, 0, or "compact" (default: 1)',
								'tbgrid' => 'Toolbar grid allocation (default: 6,2,2,2)',
								'paging' => 'Show pagination: 1 or 0 (default: 1)',
								'cols' => 'Number of columns on desktop (default: 1)',
								'colspad' => 'Number of columns on tablet (default: 1)',
								'colsphone' => 'Number of columns on mobile (default: 1)',
								'async' => 'Load pagination asynchronously: 1 or 0 (default: 0)',
							),
							'woo-category'
						);

						// Product Table Shortcode
						$this->render_shortcode_card(
							'shopglut_product_table',
							'Product Table',
							'Display products in an interactive data table with custom search, filters, column sorting, and pagination. Features real-time filtering, category/tag/stock dropdowns, and AJAX add-to-cart with View Cart button.',
							array(
								'basic' => '[shopglut_product_table]',
								'advanced' => '[shopglut_product_table design="modern" title="My Products" items_per_page="25" show_category_filter="1" show_stock_filter="1"]',
							),
							array(
								'cols' => 'Column definition (| separated, , for multiple fields) - default: title|price|stock|categories|date|add_to_cart',
								'colheads' => 'Column headers (| separated) - default: Product|Price|Stock|Categories|Date|Action',
								'design' => 'Design style: classic or modern (default: classic)',
								'title' => 'Table title (modern design)',
								'description' => 'Table description (modern design)',
								'show_items_per_page' => 'Show items per page dropdown: 1 or 0 (default: 1)',
								'items_per_page' => 'Items per page: 10, 25, 50, or 100 (default: 10)',
								'show_search' => 'Show search field: 1 or 0 (default: 1)',
								'show_category_filter' => 'Show category dropdown: 1 or 0 (default: 1)',
								'show_tag_filter' => 'Show tag dropdown: 1 or 0 (default: 0)',
								'show_stock_filter' => 'Show stock status dropdown: 1 or 0 (default: 0)',
								'orderby' => 'Order field: date, title, price, modified (default: date)',
								'order' => 'Order direction: ASC or DESC (default: DESC)',
								'categories' => 'Filter by category slugs (comma-separated)',
								'exclude' => 'Exclude product IDs (comma-separated)', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Documentation string only
								'include' => 'Include only product IDs (comma-separated)',
								'thumb' => 'Show thumbnail: 1 or 0 (default: 0)',
								'thumb_width' => 'Thumbnail width in pixels (default: 48)',
								'template' => 'WooTemplate ID for custom product display',
								'login' => 'Require login to view: 1 or 0 (default: 0)',
								'sorting' => 'Enable column sorting: 1 or 0 (default: 1)',
								'responsive' => 'Enable responsive table: 1 or 0 (default: 1)',
							),
							'product-table'
						);

						// Sale Products Shortcode
						$this->render_shortcode_card(
							'shopglut_sale_products',
							'Products On Sale',
							'Display products currently on sale with discount badges. Perfect for showcasing promotions, special offers, and clearance items.',
							array(
								'basic' => '[shopglut_sale_products]',
								'advanced' => '[shopglut_sale_products limit="12" columns="4" orderby="price" show_rating="1"]',
							),
							array(
								'limit' => 'Number of products to display (default: 12)',
								'columns' => 'Number of columns (1-6, default: 4)',
								'orderby' => 'Order by: date, title, price, popularity, rating (default: date)',
								'order' => 'Order direction: ASC or DESC (default: DESC)',
								'category' => 'Filter by category slug',
								// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Documentation string only, properly sanitized in implementation
								'exclude' => 'Exclude product IDs (comma-separated)',
								'template' => 'WooTemplate ID for custom product display',
								'paging' => 'Enable pagination: 1 or 0 (default: 0)',
								'items_per_page' => 'Items per page when paging enabled (default: 12)',
								'show_image' => 'Show product image: 1 or 0 (default: 1)',
								'show_title' => 'Show product title: 1 or 0 (default: 1)',
								'show_price' => 'Show product price: 1 or 0 (default: 1)',
								'show_button' => 'Show add to cart button: 1 or 0 (default: 1)',
								'show_rating' => 'Show product rating: 1 or 0 (default: 0)',
								'show_badge' => 'Show sale badge: 1 or 0 (default: 1)',
							),
							'sale-products'
						);
						?>

					</div>
				</div>
			</div>
		</div>

		<!-- Details Modal -->
		<div id="shopglut-details-modal" style="display: none;">
			<div class="shopglut-modal-overlay" onclick="closeDetailsModal()"></div>
			<div class="shopglut-modal-container">
				<div class="shopglut-modal-header">
					<h3 id="shopglut-details-title"></h3>
					<button type="button" class="shopglut-modal-close" onclick="closeDetailsModal()">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="shopglut-modal-body" id="shopglut-details-body"></div>
			</div>
		</div>

		<!-- Preview Modal -->
		<div id="shopglut-preview-modal" style="display: none;">
			<div class="shopglut-modal-overlay" onclick="closePreviewModal()"></div>
			<div class="shopglut-modal-container shopglut-preview-modal-container">
				<div class="shopglut-modal-header">
					<h3 id="shopglut-preview-title"></h3>
					<button type="button" class="shopglut-modal-close" onclick="closePreviewModal()">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="shopglut-modal-body" id="shopglut-preview-body"></div>
			</div>
		</div>
	</div>

	<!-- Details Modal -->
		<div id="shopglut-details-modal" style="display: none;">
			<div class="shopglut-modal-overlay" onclick="closeDetailsModal()"></div>
			<div class="shopglut-modal-container">
				<div class="shopglut-modal-header">
					<h3 id="shopglut-details-title"></h3>
					<button type="button" class="shopglut-modal-close" onclick="closeDetailsModal()">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="shopglut-modal-body" id="shopglut-details-body"></div>
			</div>
		</div>

		<!-- Preview Modal -->
		<div id="shopglut-preview-modal" style="display: none;">
			<div class="shopglut-modal-overlay" onclick="closePreviewModal()"></div>
			<div class="shopglut-modal-container shopglut-preview-modal-container">
				<div class="shopglut-modal-header">
					<h3 id="shopglut-preview-title"></h3>
					<button type="button" class="shopglut-modal-close" onclick="closePreviewModal()">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="shopglut-modal-body" id="shopglut-preview-body"></div>
			</div>
		</div>
	<?php
	}

	/**
	 * Render a shortcode card
	 *
	 * @param string $shortcode The shortcode tag
	 * @param string $title Display title
	 * @param string $description Shortcode description
	 * @param array $examples Example shortcodes (basic, advanced)
	 * @param array $params Parameters documentation
	 * @param string $preview_type Preview type for demo
	 */
	private function render_shortcode_card( $shortcode, $title, $description, $examples, $params, $preview_type ) {
		// Store shortcode data for JavaScript
		$data = array(
			'shortcode' => $shortcode,
			'title' => $title,
			'description' => $description,
			'examples' => $examples,
			'params' => $params,
		);
		$data_json = wp_json_encode( $data );
		?>
		<div class="shortcode-card" data-shortcode-data='<?php echo esc_attr( $data_json ); ?>'>
			<div class="shortcode-card-header">
				<h3><?php echo esc_html( $title ); ?></h3>
				<p><?php echo esc_html( $description ); ?></p>
			</div>

			<div class="shortcode-display">
				<div class="shortcode-code-wrapper">
					<code><?php echo esc_html( $examples['basic'] ); ?></code>
					<button type="button" class="copy-btn" onclick="copyShortcode('<?php echo esc_js( $examples['basic'] ); ?>', this)" title="<?php esc_attr_e( 'Copy shortcode', 'shortcodeglut' ); ?>">
						<span class="dashicons dashicons-admin-page"></span>
					</button>
				</div>
			</div>

			<div class="shortcode-actions">
				<button type="button" class="shortcode-action-btn details-btn">
					<span class="dashicons dashicons-info-outline"></span>
					<?php esc_html_e( 'Details', 'shortcodeglut' ); ?>
				</button>
				<button type="button" class="shortcode-action-btn preview-btn" onclick="showPreviewModal('<?php echo esc_js( $preview_type ); ?>')">
					<span class="dashicons dashicons-welcome-view-site"></span>
					<?php esc_html_e( 'Preview', 'shortcodeglut' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
			// Handle Details button clicks using event delegation
			jQuery(document).on('click', '.details-btn', function() {
				var card = jQuery(this).closest('.shortcode-card');
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
				html += '<h4><?php esc_html_e( 'Description', 'shortcodeglut' ); ?></h4>';
				html += '<p style="color: #6b7280; line-height: 1.6;">' + data.description + '</p>';
				html += '</div>';

				// Shortcode Syntax
				html += '<div class="shopglut-details-section">';
				html += '<h4><?php esc_html_e( 'Shortcode Syntax', 'shortcodeglut' ); ?></h4>';
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
				html += '<h4><?php esc_html_e( 'How to Use', 'shortcodeglut' ); ?></h4>';
				html += '<ol style="color: #6b7280; line-height: 1.8; padding-left: 20px;">';
				html += '<li><?php esc_html_e( 'Copy the shortcode using the button above or the copy button on the card', 'shortcodeglut' ); ?></li>';
				html += '<li><?php esc_html_e( 'Paste it into any WordPress page, post, or widget', 'shortcodeglut' ); ?></li>';
				html += '<li><?php esc_html_e( 'Customize parameters as needed', 'shortcodeglut' ); ?></li>';
				html += '<li><?php esc_html_e( 'For custom styling, create a WooTemplate in the Woo Templates section', 'shortcodeglut' ); ?></li>';
				html += '</ol>';
				html += '</div>';

				// Parameters Table
				if (data.params && Object.keys(data.params).length > 0) {
					html += '<div class="shopglut-details-section">';
					html += '<h4><?php esc_html_e( 'Available Parameters', 'shortcodeglut' ); ?></h4>';
					html += '<table class="shopglut-details-table">';
					html += '<thead><tr><th><?php esc_html_e( 'Parameter', 'shortcodeglut' ); ?></th><th><?php esc_html_e( 'Description', 'shortcodeglut' ); ?></th></tr></thead>';
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
					title = '<?php esc_html_e( 'WooCommerce Category Products Preview', 'shortcodeglut' ); ?>';
					content = generateWooCategoryPreview();
				} else if (type === 'product-table') {
					title = '<?php esc_html_e( 'Product Table Preview', 'shortcodeglut' ); ?>';
					content = generateProductTablePreview();
				} else if (type === 'sale-products') {
					title = '<?php esc_html_e( 'Products On Sale Preview', 'shortcodeglut' ); ?>';
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
							'<input type="text" placeholder="<?php esc_attr_e( 'Search...', 'shortcodeglut' ); ?>" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
							'<select style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
								'<option><?php esc_html_e( 'Order By: Date', 'shortcodeglut' ); ?></option>' +
								'<option><?php esc_html_e( 'Order By: Title', 'shortcodeglut' ); ?></option>' +
								'<option><?php esc_html_e( 'Order By: Price', 'shortcodeglut' ); ?></option>' +
							'</select>' +
							'<select style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
								'<option><?php esc_html_e( 'Descending', 'shortcodeglut' ); ?></option>' +
								'<option><?php esc_html_e( 'Ascending', 'shortcodeglut' ); ?></option>' +
							'</select>' +
							'<button style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php esc_html_e( 'Apply', 'shortcodeglut' ); ?></button>' +
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
									'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;"><?php esc_html_e( 'Title', 'shortcodeglut' ); ?></th>' +
									'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;"><?php esc_html_e( 'Price', 'shortcodeglut' ); ?></th>' +
									'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;"><?php esc_html_e( 'Stock', 'shortcodeglut' ); ?></th>' +
									'<th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;"><?php esc_html_e( 'Action', 'shortcodeglut' ); ?></th>' +
								'</tr>' +
							'</thead>' +
							'<tbody>' +
								'<tr>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong>Wireless Headphones</strong></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><del style="color: #9ca3af;">$199.00</del> <ins style="color: #059669;">$149.00</ins></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="color: #059669;"><?php esc_html_e( 'In Stock', 'shortcodeglut' ); ?></span></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><button style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php esc_html_e( 'Add to Cart', 'shortcodeglut' ); ?></button></td>' +
								'</tr>' +
								'<tr>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong>USB-C Cable</strong></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">$12.99</td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="color: #059669;"><?php esc_html_e( 'In Stock', 'shortcodeglut' ); ?></span></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><button style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php esc_html_e( 'Add to Cart', 'shortcodeglut' ); ?></button></td>' +
								'</tr>' +
								'<tr>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong>Laptop Stand</strong></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">$45.00</td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="color: #dc2626;"><?php esc_html_e( 'Out of Stock', 'shortcodeglut' ); ?></span></td>' +
									'<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><button style="padding: 6px 12px; background: #9ca3af; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php esc_html_e( 'Read More', 'shortcodeglut' ); ?></button></td>' +
								'</tr>' +
							'</tbody>' +
						'</table>' +
					'</div>' +
					'<div style="padding: 16px; border-top: 1px solid #e5e7eb; text-align: center;">' +
						'<p style="color: #6b7280; font-size: 13px;"><?php esc_html_e( 'Showing 3 of 25 products', 'shortcodeglut' ); ?></p>' +
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
					html += '<a href="#" class="btn"><?php esc_html_e( 'Add to Cart', 'shortcodeglut' ); ?></a>' +
						'</div>';
				}
				return html;
			}

			function generateSaleProductsPreview() {
				var products = [
					{ name: '<?php esc_html_e( 'Wireless Headphones', 'shortcodeglut' ); ?>', price: '$149.00', oldPrice: '$199.00', badge: '25%', color1: '#667eea', color2: '#764ba2' },
					{ name: '<?php esc_html_e( 'Smart Watch Pro', 'shortcodeglut' ); ?>', price: '$199.00', oldPrice: '$299.00', badge: '33%', color1: '#f093fb', color2: '#f5576c' },
					{ name: '<?php esc_html_e( 'Bluetooth Speaker', 'shortcodeglut' ); ?>', price: '$59.00', oldPrice: '$89.00', badge: '34%', color1: '#4facfe', color2: '#00f2fe' },
					{ name: '<?php esc_html_e( 'Laptop Stand', 'shortcodeglut' ); ?>', price: '$35.00', oldPrice: '$49.00', badge: '29%', color1: '#43e97b', color2: '#38f9d7' },
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
					html += '<button style="width: 100%; padding: 6px 12px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;"><?php esc_html_e( 'Add to Cart', 'shortcodeglut' ); ?></button>';
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
		</script>
		<?php
	}

	/**
	 * Render a shortcode card
	 *
	 * @param string $shortcode The shortcode tag
	 * @param string $title Display title
	 * @param string $description Shortcode description
	 * @param array $examples Example shortcodes (basic, advanced)
	 * @param array $params Parameters documentation
	 * @param string $preview_type Preview type for demo
	 */
	private function render_shortcode_card( $shortcode, $title, $description, $examples, $params, $preview_type ) {
		// Store shortcode data for JavaScript
		$data = array(
			'shortcode' => $shortcode,
			'title' => $title,
			'description' => $description,
			'examples' => $examples,
			'params' => $params,
		);
		$data_json = wp_json_encode( $data );
		?>
		<div class="shortcode-card" data-shortcode-data='<?php echo esc_attr( $data_json ); ?>'>
			<div class="shortcode-card-header">
				<h3><?php echo esc_html( $title ); ?></h3>
				<p><?php echo esc_html( $description ); ?></p>
			</div>

			<div class="shortcode-display">
				<div class="shortcode-code-wrapper">
					<code><?php echo esc_html( $examples['basic'] ); ?></code>
					<button type="button" class="copy-btn" onclick="copyShortcode('<?php echo esc_js( $examples['basic'] ); ?>', this)" title="<?php esc_attr_e( 'Copy shortcode', 'shortcodeglut' ); ?>">
						<span class="dashicons dashicons-admin-page"></span>
					</button>
				</div>
			</div>

			<div class="shortcode-actions">
				<button type="button" class="shortcode-action-btn details-btn">
					<span class="dashicons dashicons-info-outline"></span>
					<?php esc_html_e( 'Details', 'shortcodeglut' ); ?>
				</button>
				<button type="button" class="shortcode-action-btn preview-btn" onclick="showPreviewModal('<?php echo esc_js( $preview_type ); ?>')">
					<span class="dashicons dashicons-welcome-view-site"></span>
					<?php esc_html_e( 'Preview', 'shortcodeglut' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
