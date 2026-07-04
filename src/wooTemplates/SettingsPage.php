<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;
use Shortcodeglut\wooTemplates\TemplateLoader;

class SettingsPage {
	private $menu_slug = 'shortcodeglut';
	private $template_tags = [
		'basic' => [
			'[product_title]' => 'Product Title - Displays the main product title',
			'[product_short_description]' => 'Short Description - Brief product summary',
			'[product_description]' => 'Full Description - Complete product details',
			'[product_sku]' => 'Product SKU - Stock keeping unit identifier',
			'[product_id]' => 'Product ID - Unique product identifier',
			'[product_type]' => 'Product Type - Type (simple, variable, etc.)',
			'[product_status]' => 'Product Status - Current status (publish, draft, etc.)'
		],
		'prices' => [
			'[product_price]' => 'Product Price - Current price HTML',
			'[product_price_formatted]' => 'Price Formatted - Nicely formatted with sale/original',
			'[product_price_range]' => 'Price Range - For variable products',
			'[product_regular_price]' => 'Regular Price - Standard price',
			'[product_sale_price]' => 'Sale Price - Discounted price when on sale',
			'[product_regular_price_strike]' => 'Regular Price Strike - Strikethrough price',
			'[product_savings]' => 'Savings Amount - Shows amount saved (e.g., Save: $10)',
			'[product_savings_percent]' => 'Savings Percent - Percentage off (e.g., 25% off)'
		],
		'images' => [
			'[product_image]' => 'Product Image - Main product image (default size)',
			'[product_gallery]' => 'Product Gallery - All product images',
			'[product_image_WxH]' => 'Custom Size Image - e.g., [product_image_800x600]',
			'[product_thumb_WxH]' => 'Custom Thumbnail - e.g., [product_thumb_300x300]',
			'[product_image_url_WxH]' => 'Image URL Only - e.g., [product_image_url_800x600]',
			'[product_thumb_url_WxH]' => 'Thumbnail URL - e.g., [product_thumb_url_300x300]',
			'[product_gallery_thumb_WxHxC]' => 'Custom Gallery - e.g., [product_gallery_thumb_150x150x4]'
		],
		'stock' => [
			'[product_stock]' => 'Stock Status - In Stock / Out of Stock',
			'[product_stock_quantity]' => 'Stock Quantity - Number in stock',
			'[product_stock_status]' => 'Detailed Stock Status - In Stock / Out / Backorder',
			'[product_availability]' => 'Availability - Availability text with formatting',
			'[product_backorders]' => 'Backorder Status - Backorder information',
			'[product_low_stock]' => 'Low Stock - Shows when < 10 items'
		],
		'badges' => [
			'[product_badges]' => 'All Badges - All applicable badges combined',
			'[product_badge_sale]' => 'Sale Badge - Shows when on sale',
			'[product_badge_new]' => 'New Badge - Shows for products ≤ 30 days',
			'[product_badge_featured]' => 'Featured Badge - Shows when featured',
			'[product_badge_outofstock]' => 'Out of Stock Badge - Shows when out of stock'
		],
		'links' => [
			'[product_url]' => 'Product URL - Direct link to product',
			'[product_permalink]' => 'Product Permalink - Alias for URL',
			'[product_link]' => 'Product Link - Linked product title',
			'[product_link_title]' => 'Link with Title - Linked title with title attribute',
			'[product_link_new]' => 'Link New Tab - Opens in new tab'
		],
		'buttons' => [
			'[btn_cart]' => 'Add to Cart - Add product to cart button',
			'[btn_view]' => 'View Product - Link to product page',
			'[btn_buy_now]' => 'Buy Now - Direct to checkout',
			'[btn_quickview]' => 'Quick View - Quick view button (theme support)',
			'[btn_wishlist]' => 'Wishlist - Add to wishlist (plugin required)',
			'[btn_compare]' => 'Compare - Add to compare (plugin required)'
		],
		'ratings' => [
			'[product_rating]' => 'Rating - Default star rating HTML',
			'[product_rating_count]' => 'Rating Count - Number of ratings',
			'[product_review_count]' => 'Review Count - Reviews with text',
			'[product_rating_number]' => 'Rating Number - e.g., 4.5/5',
			'[product_rating_stars]' => 'Star Display - Star symbols (★)',
			'[product_reviews_url]' => 'Reviews URL - Link to reviews section'
		],
		'categories' => [
			'[product_categories]' => 'Categories - Product categories with links',
			'[product_tags]' => 'Tags - Product tags with links'
		],
		'excerpt' => [
			'[product_excerpt_N]' => 'Custom Excerpt - e.g., [product_excerpt_100]'
		],
		'attributes' => [
			'[product_attributes]' => 'All Attributes - Complete attribute list',
			'[product_dimensions]' => 'Dimensions - Product size (L×W×H)',
			'[product_weight]' => 'Weight - Product weight with unit'
		],
		'meta' => [
			'[product_total_sales]' => 'Total Sales - Number of times sold',
			'[product_virtual]' => 'Virtual Status - Virtual or Physical',
			'[product_downloadable]' => 'Downloadable - Downloadable status',
			'[product_tax_class]' => 'Tax Class - Product tax class',
			'[product_shipping_class]' => 'Shipping Class - Product shipping class',
			'[product_menu_order]' => 'Menu Order - Sort order number',
			'[product_purchase_note]' => 'Purchase Note - Note for customers'
		],
		'dates' => [
			'[product_date]' => 'Published Date - When product was published',
			'[product_date_iso]' => 'ISO Date - ISO format date',
			'[product_modified]' => 'Modified Date - Last modified date'
		],
		'author' => [
			'[product_author]' => 'Author Name - Product author',
			'[product_author_link]' => 'Author Link - Linked author name'
		]
	];

	public function __construct() {
		// Initialize TemplateLoader to ensure templates are available for selection
		TemplateLoader::init();

		// Admin-specific hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueListScripts' ) );

		// Template actions (save/update/delete/clone) removed - PRO feature only
		// AJAX handler for loading preview content (prebuilt templates only)
		add_action( 'wp_ajax_shortcodeglut_load_preview', array( $this, 'ajaxLoadPreview' ) );
		// Editor preview AJAX removed - PRO feature only
	}

	/**
	 * Add body class to template list page
	 */
	public function addTemplateEditorBodyClass( $classes ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class only
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( $this->menu_slug === $page && isset( $_GET['view'] ) && 'woo_templates' === sanitize_text_field( wp_unslash( $_GET['view'] ) ) ) {		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class only

			$classes .= ' shortcodeglut-woo-templates-list';
		}
		return $classes;
	}

	/**
	 * Enqueue scripts and styles for the templates list page
	 */
	public function enqueueListScripts( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for script loading
		if ( ! isset( $_GET['page'] ) || $this->menu_slug !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for view verification
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
		if ( 'woo_templates' !== $view && 'woo_template_editor' !== $view ) {
			return;
		}

		// Enqueue templates list styles
		wp_enqueue_style(
			'shortcodeglut-woo-templates-list',
			SHORTCODEGLUT_URL . 'global-assets/css/woo-templates-list.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		// Enqueue template editor styles
		wp_enqueue_style(
			'shortcodeglut-template-editor',
			SHORTCODEGLUT_URL . 'global-assets/css/template-editor.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		// Add inline styles for editor preview button and modal
		wp_add_inline_style('shortcodeglut-template-editor', '
				.shortcodeglut-editor-preview-modal {
					display: none;
					position: fixed;
					top: 0;
					left: 0;
					right: 0;
					bottom: 0;
					z-index: 100000;
				}
			.shortcodeglut-editor-preview-modal.active {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.shortcodeglut-preview-modal-overlay {
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: rgba(0, 0, 0, 0.5);
				z-index: 1;
			}
			.shortcodeglut-preview-modal-container {
				position: relative;
				z-index: 2;
				background: #ffffff;
				border-radius: 12px;
				max-width: 800px;
				width: 90%;
				max-height: 80vh;
				overflow: hidden;
				box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			}
			.shortcodeglut-preview-modal-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 20px;
				border-bottom: 1px solid #e5e7eb;
				background: #f9fafb;
			}
			.shortcodeglut-preview-modal-header h3 {
				margin: 0;
				font-size: 18px;
				font-weight: 600;
				color: #1d2327;
			}
			.shortcodeglut-preview-modal-close {
				background: none;
				border: none;
				cursor: pointer;
				padding: 4px;
				color: #6b7280;
				transition: color 0.2s;
			}
			.shortcodeglut-preview-modal-close:hover {
				color: #1d2327;
			}
			.shortcodeglut-preview-modal-body {
				padding: 40px;
				max-height: calc(80vh - 80px);
				overflow-y: auto;
				background: #ffffff;
			}
			.shortcodeglut-preview-modal-loading {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 12px;
				padding: 60px 20px;
				color: #6b7280;
			}
			.shortcodeglut-preview-modal-loading .dashicons {
				font-size: 24px;
				width: 24px;
				height: 24px;
			}
			.shortcodeglut-preview-modal-content {
				display: flex;
				justify-content: center;
				align-items: center;
				min-height: 200px;
			}
		');

		// Enqueue templates list scripts
		wp_enqueue_script(
			'shortcodeglut-woo-templates-list',
			SHORTCODEGLUT_URL . 'global-assets/js/woo-templates-list.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		// Enqueue template editor script
		wp_enqueue_script(
			'shortcodeglut-template-editor',
			SHORTCODEGLUT_URL . 'global-assets/js/template-editor.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

			// Enqueue Font Awesome for preview modal icons
			wp_enqueue_style(
				"shortcodeglut-fontawesome",
				SHORTCODEGLUT_URL . "global-assets/vendor/fontawesome/all.min.css",
				array(),
				"7.2.0"
			);

		// Localize script
		wp_localize_script( 'shortcodeglut-woo-templates-list', 'shortcodeglutTemplatesList', array(
			'previewNonce' => wp_create_nonce( 'shortcodeglut_preview_nonce' ),
			'i18n' => array(
				'previewTemplate' => esc_html__( 'Preview Template', 'shortcodeglut' ),
				'close' => esc_html__( 'Close', 'shortcodeglut' ),
				'loadingPreview' => esc_html__( 'Loading preview...', 'shortcodeglut' ),
				'errorLoadingPreview' => esc_html__( 'Error loading preview', 'shortcodeglut' ),
			)
		) );
	}

	/**
	 * Display the templates list page (editor removed for WordPress.org compliance)
	 */
	public function templatesListPage() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}

		// Clear template cache to ensure fresh templates are loaded
		TemplateLoader::clear_cache();

		// Display settings errors
		settings_errors( 'shortcodeglut_templates' );

		// Create the list table
		$list_table = new WooTemplatesListTable();
		$list_table->prepare_items();

		// Display the templates list
		?>
		<div class="wrap shortcodeglut-woo-templates-page">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Product Templates', 'shortcodeglut' ); ?></h1>
			<hr class="wp-header-end">

			<?php
			// Display info notice about PRO features
			echo '<div class="notice notice-info inline">';
			echo '<p>';
			echo '<strong>' . esc_html__( 'Prebuilt Templates Only', 'shortcodeglut' ) . '</strong> - ';
			echo esc_html__( 'These are the prebuilt templates included with ShortcodeGlut. For custom template creation, cloning, and advanced editing features, please upgrade to', 'shortcodeglut' );
			echo ' <a href="https://appglut.com/shortcodeglut-pro/" target="_blank">' . esc_html__( 'ShortcodeGlut PRO', 'shortcodeglut' ) . '</a>.';
			echo '</p>';
			echo '</div>';
			?>
			<?php
			// Display success message if template was just saved (removed - no longer applicable)
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for success message display only
			if ( isset( $_GET['saved'] ) && $_GET['saved'] == '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html__( 'Template saved successfully!', 'shortcodeglut' );
				echo '</p></div>';
			}

			// Display success message if template was deleted
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for success message display only
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html__( 'Template deleted successfully!', 'shortcodeglut' );
				echo '</p></div>';
			}

			// Display success message if template was cloned
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for success message display only
			if ( isset( $_GET['cloned'] ) && $_GET['cloned'] == '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html__( 'Template cloned successfully! You can now edit the copy.', 'shortcodeglut' );
				echo '</p></div>';
			}
			?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $this->menu_slug ); ?>" />
				<input type="hidden" name="view" value="woo_templates" />
				<?php
								$list_table->display();
				?>
			</form>
		</div>

		<!-- Preview Modal -->
		<div id="shortcodeglut-preview-modal" style="display: none;">
			<div id="shortcodeglut-preview-modal-overlay"></div>
			<div id="shortcodeglut-preview-modal-container">
				<div id="shortcodeglut-preview-modal-header">
					<h3 id="shortcodeglut-preview-modal-title"></h3>
					<button type="button" id="shortcodeglut-preview-modal-close">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div id="shortcodeglut-preview-modal-body" class="shortcodeglut-preview-content">
					<div id="shortcodeglut-preview-content"></div>
				</div>
			</div>
		</div>
		<?php
	}

	

	/**
	 * AJAX handler for loading preview content from preview.html and preview.css files
	 */
	public function ajaxLoadPreview() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'shortcodeglut_preview_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
			return;
		}

		// Get template_id from POST
		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';

		if ( empty( $template_id ) ) {
			wp_send_json_error( array( 'message' => 'Template ID is required.' ) );
			return;
		}

		// Get template from database
		global $wpdb;
		$table = $wpdb->prefix . 'shortcodeglut_woo_templates';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + fixed suffix), values are prepared
		$db_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE template_id = %s", $template_id), ARRAY_A);

		$preview_html = '';
		$preview_css = '';

		// If it's a database (custom) template, generate preview from template HTML
		if ($db_template && !empty($db_template['template_html'])) {
			// Use custom CSS from database if available, otherwise load from file
			if (!empty($db_template['template_css'])) {
				$preview_css = $db_template['template_css'];
			} else {
				// Get CSS from the corresponding file-based template if it exists
				TemplateLoader::init();

				// For cloned templates, try to get CSS from the base template
				$css_template_id = $template_id;
				if (strpos($template_id, '_clone_') !== false) {
					// Extract the base template ID from cloned template ID
					$base_template_id = preg_replace('/_clone_\d+$/', '', $template_id);
					if (TemplateLoader::template_exists($base_template_id)) {
						$css_template_id = $base_template_id;
					}
				}

				// Use style.css instead of preview.css for proper scoping
				$preview_css = TemplateLoader::get_template_css($css_template_id);
			}
			// Process the template HTML with sample data for preview
			$preview_html = $this->generate_preview_from_template($db_template['template_html']);

			if (empty($preview_html)) {
				wp_send_json_error( array( 'message' => 'Could not generate preview for this template.' ) );
				return;
			}
		} else {
			// Generate preview from the actual template.php file for file-based templates
			TemplateLoader::init();
			$template_html = TemplateLoader::get_template_html( $template_id );
			// Use style.css for proper scoping
			$preview_css = TemplateLoader::get_template_css( $template_id );

			// Check if template file exists
			if ( empty( $template_html ) ) {
				wp_send_json_error( array( 'message' => 'Template not found.' ) );
				return;
			}

			// Generate preview from template HTML with sample data
			$preview_html = $this->generate_preview_from_template( $template_html );

			if ( empty( $preview_html ) ) {
				wp_send_json_error( array( 'message' => 'Could not generate preview for this template.' ) );
				return;
			}
		}

		// Return the preview content
		wp_send_json_success( array(
			'html' => $preview_html,
			'css' => $preview_css
		) );
	}

	/**
	 * Generate preview HTML from template HTML using sample product data
	 *
	 * @param string $template_html The template HTML with template tags
	 * @return string Processed HTML with sample data
	 */
	private function generate_preview_from_template($template_html) {
		// Remove PHP header and code from template files
		// Remove PHP opening tag and docblock
		$template_html = preg_replace('/<\?php\s*\/\*\*.*?\*\/\s*/s', '', $template_html);
		// Remove ABSPATH check
		$template_html = preg_replace('/if\s*\(\s*!defined\([\'"]ABSPATH[\'"]\)\s*\)\s*\{[^}]+\}\s*/s', '', $template_html);
		// Remove global product declaration
		$template_html = preg_replace('/global\s+\$product;[^\n]*/', '', $template_html);
		// Remove exit; statement
		$template_html = preg_replace('/exit;[^\n]*/', '', $template_html);
		// Remove remaining PHP tags
		$template_html = preg_replace('/<\?php|\?>/', '', $template_html);
		// Clean up extra whitespace
		$template_html = trim($template_html);

		// Sample product data for preview
		$sample_image_url = SHORTCODEGLUT_URL . 'global-assets/images/sample-image.png';
		$sample_data = array(
			'[product_id]' => '123',
			'[product_permalink]' => '#',
			'[product_title]' => 'Sample Product',
			'[product_price]' => '<span class="woocommerce-Price-amount amount"><bdi>$29.99</bdi></span>',
			'[product_sale_price]' => '<span class="woocommerce-Price-amount amount"><bdi>$19.99</bdi></span>',
			'[product_regular_price]' => '<span class="woocommerce-Price-amount amount"><bdi>$29.99</bdi></span>',
			'[product_short_description]' => 'This is a sample product description for preview purposes.',
			'[product_description]' => 'This is a sample product description for preview purposes.',
			'[add_to_cart_url]' => '#',
			'[cart_url]' => '#',
			'[product_image_url]' => $sample_image_url,
			'[product_image_url_800x600]' => $sample_image_url,
			'[product_image]' => '<img src="' . esc_url($sample_image_url) . '" alt="Sample Product">',
			'[sku]' => 'SAMPLE-123',
			'[stock_status]' => 'instock',
			'[stock_quantity]' => '10',
		);

			// Process conditional tags for preview
			// Handle mutually exclusive conditions: randomly choose one scenario
			$show_sale_scenario = (bool) wp_rand( 0, 1 );
			
			// Remove all non-preferred conditional tags first
			if ($show_sale_scenario) {
				// Remove "not_on_sale" and "out_of_stock" conditions
				$template_html = preg_replace('/\[is_not_on_sale:((?:[^\[\]]|\[[^\]]*\])*?)\]/s', '', $template_html);
				$template_html = preg_replace('/\[is_out_of_stock:((?:[^\[\]]|\[[^\]]*\])*?)\]/s', '', $template_html);
			} else {
				// Remove "on_sale" conditions
				$template_html = preg_replace('/\[is_on_sale:((?:[^\[\]]|\[[^\]]*\])*?)\]/s', '', $template_html);
			}
			
			// Now process remaining conditional tags - show their content
			$template_html = preg_replace_callback(
				'/\[([a-zA-Z_][a-zA-Z0-9_]*):((?:[^\[\]]|\[[^\]]*\])*)\]/s',
				function($matches) {
					return trim($matches[2]);
				},
				$template_html
			);

		// Process translation tags - just return the text for preview
		$template_html = preg_replace('/\[t:([^\]]+)\]/', '$1', $template_html);

		// Replace basic template tags with sample data
		$template_html = str_replace(array_keys($sample_data), array_values($sample_data), $template_html);

		// Process product categories tag
		if (strpos($template_html, '[product_categories]') !== false) {
			$categories_html = '<a href="#">Category 1</a>, <a href="#">Category 2</a>';
			$template_html = str_replace('[product_categories]', $categories_html, $template_html);
		}

		// Process product rating tag
		if (strpos($template_html, '[product_rating]') !== false) {
			$rating_html = '<div class="spr-badge" data-rating="4">
				<span class="spr-starrating spr-badge-starrating">
					<i class="fa-solid fa-star"></i>
					<i class="fa-solid fa-star"></i>
					<i class="fa-solid fa-star"></i>
					<i class="fa-solid fa-star"></i>
					<i class="fa-regular fa-star"></i>
				</span>
			</div>';
			$template_html = str_replace('[product_rating]', $rating_html, $template_html);
		}

		// Process product badge sale tag
		if (strpos($template_html, '[product_badge_sale]') !== false) {
			$badge_html = '<span class="discount-badge product-label">-33%</span>';
			$template_html = str_replace('[product_badge_sale]', $badge_html, $template_html);
		}

		return $template_html;
	}

	/**
	 * AJAX handler for loading editor preview
	 */
	/**
	 * AJAX handler for template preview (kept for viewing prebuilt templates)
	 */
	/**
	 * Get sample product for preview
	 */
	private function get_sample_product_for_preview() {
		// Try to get a real WooCommerce product
		if ( class_exists( 'WooCommerce' ) ) {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			);
			$product_ids = get_posts( $args );
			if ( ! empty( $product_ids ) ) {
				return wc_get_product( $product_ids[0] );
			}
		}

		// Return a sample product object
		return $this->create_mock_product();
	}

	/**
	 * Create a mock product for preview
	 */
	private function create_mock_product() {
		$mock = new stdClass();

		$mock->ID = 0;
		$mock->post_title = 'Sample Product';
		$mock->post_excerpt = 'This is a sample product for preview purposes.';
		$mock->post_content = 'This is the full description of the sample product.';
		$mock->post_name = 'sample-product';
		$mock->regular_price = '29.99';
		$mock->sale_price = '';
		$mock->price = '29.99';
		$mock->price_html = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span>29.99</bdi></span>';
		$mock->sku = 'SAMPLE-001';
		$mock->stock_status = 'instock';
		$mock->average_rating = '4.5';
		$mock->rating_count = 15;
		$mock->image_id = 0;
		$mock->image_url = SHORTCODEGLUT_URL . 'global-assets/images/sample-image.png';
		$mock->permalink = '#';
		$mock->add_to_cart_url = '#';
		$mock->add_to_cart_text = 'Add to cart';

		return $mock;
	}

	/**
	 * Process template tags for preview
	 */
	private function process_template_tags_for_preview( $html, $product ) {
		$is_wc_product = is_object( $product ) && method_exists( $product, 'get_id' );

		// Helper function to get product data
		$get_data = function( $key, $default = '' ) use ( $product, $is_wc_product ) {
			if ( $is_wc_product ) {
				switch ( $key ) {
					case 'id':
						return $product->get_id();
					case 'title':
						return $product->get_name();
					case 'price_html':
						return $product->get_price_html();
					case 'short_description':
						return $product->get_short_description();
					case 'description':
						return $product->get_description();
					case 'sku':
						return $product->get_sku();
					case 'average_rating':
						return $product->get_average_rating();
					case 'image_id':
						return $product->get_image_id();
					case 'permalink':
						return get_permalink( $product->get_id() );
					case 'add_to_cart_url':
						return $product->add_to_cart_url();
					case 'add_to_cart_text':
						return $product->add_to_cart_text();
					default:
						return $default;
				}
			} else {
				// Mock product
				switch ( $key ) {
					case 'id':
						return isset( $product->ID ) ? $product->ID : $default;
					case 'title':
						return isset( $product->post_title ) ? $product->post_title : $default;
					case 'price_html':
						return isset( $product->price_html ) ? $product->price_html : $default;
					case 'short_description':
						return isset( $product->post_excerpt ) ? $product->post_excerpt : $default;
					case 'description':
						return isset( $product->post_content ) ? $product->post_content : $default;
					case 'sku':
						return isset( $product->sku ) ? $product->sku : $default;
					case 'average_rating':
						return isset( $product->average_rating ) ? $product->average_rating : $default;
					case 'image_id':
						return isset( $product->image_id ) ? $product->image_id : 0;
					case 'image_url':
						return isset( $product->image_url ) ? $product->image_url : '';
					case 'permalink':
						return isset( $product->permalink ) ? $product->permalink : '#';
					default:
						return $default;
				}
			}
		};

		// Build replacements array
		$replacements = array(
			'[product_title]' => $get_data( 'title' ),
			'[product_price]' => $get_data( 'price_html' ),
			'[product_short_description]' => $get_data( 'short_description' ),
			'[product_description]' => $get_data( 'description' ),
			'[product_sku]' => $get_data( 'sku' ),
			'[product_rating]' => $is_wc_product ? wc_get_rating_html( $get_data( 'average_rating' ) ) : '<div class="star-rating" role="img" aria-label="Rated 4.5 out of 5"><span style="width:90%">Rated 4.5 out of 5</span></div>',
		);

		// Product image - always use sample image for preview
		if ( strpos( $html, '[product_image]' ) !== false ) {
			$image_url = SHORTCODEGLUT_URL . 'global-assets/images/sample-image.png';
			$image = sprintf( '<img src="%s" alt="%s" style="max-width:100%%;height:auto;display:block;" />', esc_url( $image_url ), esc_attr( $get_data( 'title' ) ) );
			$replacements['[product_image]'] = $image;
		}

		// Product badges - add sample badges for preview
		$badges_html = '<span class="new-badge product-label">New</span>';
		$badges_html .= '<span class="discount-badge product-label">-15%</span>';
		$replacements['[product_badges]'] = $badges_html;

		// Product permalink
		$replacements['[product_permalink]'] = $get_data('permalink');

		// Product categories - add sample category for preview
		if (strpos($html, '[product_categories]') !== false) {
			$replacements['[product_categories]'] = '<a href="#">Sample Category</a>';
		}

		// Buttons
		if ( strpos( $html, '[btn_cart]' ) !== false ) {
			if ( $is_wc_product ) {
				$cart_btn = sprintf(
					'<a href="%s" class="button" data-product_id="%d">%s</a>',
					esc_url( $get_data( 'add_to_cart_url' ) ),
					$get_data( 'id' ),
					esc_html( $get_data( 'add_to_cart_text' ) )
				);
			} else {
				$cart_btn = '<button class="button" disabled>Add to Cart</button>';
			}
			$replacements['[btn_cart]'] = $cart_btn;
		}

		if ( strpos( $html, '[btn_view]' ) !== false ) {
			$view_btn = sprintf(
				'<a href="%s" class="button">View Product</a>',
				esc_url( $get_data( 'permalink' ) )
			);
			$replacements['[btn_view]'] = $view_btn;
		}

		// Replace all tags
		foreach ( $replacements as $tag => $replacement ) {
			$html = str_replace( $tag, $replacement, $html );
		}

		return $html;
	}

	/**
	 * Handle template actions - save, update, delete
	 */

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
