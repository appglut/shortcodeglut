<?php
/**
 * WooCommerce Masonry Grid Shortcode Handler
 *
 * Handles [shortcodeglut_masonry_grid] shortcode to display products
 * in a responsive masonry/pinterest-style layout
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\MasonryGrid;

use Shortcodeglut\wooTemplates\WooTemplatesEntity;
use Shortcodeglut\wooTemplates\ConditionalTagProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MasonryGridShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shortcodeglut_masonry_grid', array( $this, 'render_masonry_shortcode' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_shortcodeglut_masonry_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_masonry_load', array( $this, 'ajax_load_products' ) );

		// Register assets for frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register assets (style handle for inline CSS)
	 */
	public function register_assets() {
		// Register masonry grid styles
		wp_register_style(
			'shortcodeglut-masonry-grid',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/masonry-grid/assets/css/masonry-grid.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		// Register masonry grid script
		wp_register_script(
			'shortcodeglut-masonry-grid',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/masonry-grid/assets/js/masonry-grid.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		// Register add to cart handler script
		wp_register_script(
			'shortcodeglut-add-to-cart-handler',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/basic-grid/assets/js/add-to-cart-handler.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		// Register add to cart handler style
		wp_register_style(
			'shortcodeglut-add-to-cart-handler',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/basic-grid/assets/css/add-to-cart-handler.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		// Register a handle for inline template styles
		wp_register_style(
			'shortcodeglut-masonry-templates',
			false,
			array(),
			SHORTCODEGLUT_VERSION
		);
	}

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Main shortcode handler
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_masonry_shortcode( $atts ) {
		// Skip rendering during REST API requests (block editor validation)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-masonry-placeholder">[Shortcodeglut Masonry Grid]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shortcodeglut_masonry_' . $this->shortcode_counter;

		// Check for URL parameter for sorting (non-AJAX mode)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe read-only URL parameters for sorting/paging display.
		$url_sort = isset( $_GET['shortcodeglut_sort'] ) ? sanitize_text_field( wp_unslash( $_GET['shortcodeglut_sort'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe read-only URL parameters for sorting/paging display.
		$url_paged = isset( $_GET['shortcodeglut_paged'] ) ? absint( wp_unslash( $_GET['shortcodeglut_paged'] ) ) : 1;

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			'columns' => 4,                       // Masonry columns for desktop
			'rows' => 0,                          // Number of rows
			'limit' => 0,                         // Total limit (0 = use columns * rows)
			'order_by' => 'date-desc',            // Order field: date, title, price, popularity, rating
			'order' => 'DESC',                     // Order direction: ASC or DESC
			'items_per_page' => 12,               // Items per page when paging is enabled
			'template' => 'product_card_basic',  // Template ID from WooTemplates
			'paging' => '1',                      // Enable pagination: 1 or 0
			'ajax' => 'off',                      // Enable AJAX pagination: on or off
			'category' => '',                     // Filter by category slug
			'exclude' => '',                      // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Default value declaration only; actual exclusion is bounded and intentional.
			'toolbar' => '1',                     // Show toolbar: 1, 0, or compact
			'show_breadcrumb' => '0',             // Show breadcrumb
			'card_style' => 'modern',            // Card style: modern, classic, minimal
			'gap' => '20',                        // Gap between items in px
			'border_radius' => '16',             // Card border radius in px
			'shadow' => '1',                      // Enable shadow: 1 or 0
			'hover_lift' => '1',                  // Enable hover lift effect: 1 or 0
			'image_height' => 'auto',            // Image height: auto, tall, medium, short
			'show_tags' => '1',                  // Show product tags: 1 or 0
			'show_excerpt' => '1',                // Show product excerpt: 1 or 0
			'tag_color' => '#fef3c7',            // Tag background color
			'tag_text_color' => '#92400e',       // Tag text color
		), $atts, 'shortcodeglut_masonry_grid' );

		// URL parameter overrides shortcode attribute for sorting
		if ( ! empty( $url_sort ) ) {
			$atts['order_by'] = $url_sort;
		}

		// Sanitize attributes
		$atts['columns'] = absint( $atts['columns'] );
		$atts['rows'] = absint( $atts['rows'] );
		$atts['limit'] = absint( $atts['limit'] );
		$atts['order_by'] = sanitize_text_field( $atts['order_by'] );
		$atts['order'] = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['items_per_page'] = absint( $atts['items_per_page'] );
		$atts['template'] = sanitize_text_field( $atts['template'] );
		$atts['paging'] = filter_var( $atts['paging'], FILTER_VALIDATE_BOOLEAN );
		$atts['ajax'] = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['category'] = sanitize_text_field( $atts['category'] );
		$atts['exclude'] = sanitize_text_field( $atts['exclude'] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-specified exclusions; bounded and intentional.
		$atts['toolbar'] = sanitize_text_field( $atts['toolbar'] );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		$atts['card_style'] = sanitize_text_field( $atts['card_style'] );
		$atts['gap'] = absint( $atts['gap'] );
		$atts['border_radius'] = absint( $atts['border_radius'] );
		$atts['shadow'] = filter_var( $atts['shadow'], FILTER_VALIDATE_BOOLEAN );
		$atts['hover_lift'] = filter_var( $atts['hover_lift'], FILTER_VALIDATE_BOOLEAN );
		$atts['image_height'] = sanitize_text_field( $atts['image_height'] );
		$atts['show_tags'] = filter_var( $atts['show_tags'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_excerpt'] = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );
		$atts['tag_color'] = sanitize_hex_color( $atts['tag_color'] );
		$atts['tag_text_color'] = sanitize_hex_color( $atts['tag_text_color'] );

		// Calculate limit if not set
		if ( empty( $atts['limit'] ) ) {
			$atts['limit'] = $atts['columns'] * $atts['rows'];
		}

		// Convert ajax to boolean - always enable AJAX for sorting
		$ajax_enabled = true;

		// Enqueue required assets
		$this->enqueue_assets( $atts, $ajax_enabled );

		// Start output buffering
		ob_start();

		// Render the shortcode output
		$this->render_output( $unique_id, $atts, $url_paged );

		return ob_get_clean();
	}

	/**
	 * Enqueue required CSS and JS
	 */
	private function enqueue_assets( $atts, $ajax_enabled ) {
		// Enqueue masonry styles
		wp_enqueue_style( 'shortcodeglut-masonry-grid' );

		// Always enqueue JavaScript for sorting functionality
		wp_enqueue_script( 'shortcodeglut-masonry-grid' );

		// Localize script with AJAX data and current page URL
		wp_localize_script( 'shortcodeglut-masonry-grid', 'shortcodeglutMasonryAjax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shortcodeglut_masonry_nonce' ),
			'ajax_enabled' => $ajax_enabled,
			'current_url' => remove_query_arg( array( 'shortcodeglut_sort', 'shortcodeglut_paged' ), home_url( add_query_arg( null, null ) ) ),
			'columns' => $atts['columns'],
		) );
	}

	/**
	 * Render the complete shortcode output
	 */
	private function render_output( $unique_id, $atts, $paged = 1 ) {
		$content_id = 'content_' . $unique_id;

		// Encode attributes for data attribute (for AJAX)
		$atts_json = wp_json_encode( $atts );
		$safe_atts = htmlspecialchars( $atts_json, ENT_QUOTES, 'UTF-8' );

		$card_style_class = 'shortcodeglut-card-style-' . esc_attr( $atts['card_style'] );
		$shadow_class = $atts['shadow'] ? 'has-shadow' : 'no-shadow';
		$hover_class = $atts['hover_lift'] ? 'has-hover-lift' : '';

		echo '<div class="shortcodeglut-masonry-wrapper ' . esc_attr( $card_style_class ) . ' ' . esc_attr( $shadow_class ) . ' ' . esc_attr( $hover_class ) . '" id="' . esc_attr( $unique_id ) . '_wrapper" data-atts="' . esc_attr( $safe_atts ) . '">';

		// Optional breadcrumb
		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		// Toolbar
		if ( $atts['toolbar'] !== '0' ) {
			$this->render_toolbar( $unique_id, $atts );
		}

		// Products content area
		echo '<div id="' . esc_attr( $content_id ) . '" class="shortcodeglut-masonry-content">';

		// Load products
		$this->render_products( $atts, $paged );

		echo '</div>'; // End content area

		echo '</div>';
	}

	/**
	 * Render breadcrumb
	 */
	private function render_breadcrumb() {
		echo '<nav class="shortcodeglut-breadcrumb">';
		echo '<a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Home', 'shortcodeglut' ) . '</a>';
		echo '<span>/</span>';
		echo '<span>' . esc_html__( 'Products', 'shortcodeglut' ) . '</span>';
		echo '</nav>';
	}

	/**
	 * Render toolbar with sorting options
	 */
	private function render_toolbar( $unique_id, $atts ) {
		$is_compact = ( $atts['toolbar'] === 'compact' );
		$current_orderby = $atts['order_by'];

		echo '<div class="shortcodeglut-toolbar">';

		if ( ! $is_compact ) {
			echo '<select class="shortcodeglut-sort-select">';
			echo '<option value="date"' . selected( $current_orderby, 'date', false ) . '>' . esc_html__( 'Newest', 'shortcodeglut' ) . '</option>';
			echo '<option value="date-desc"' . selected( $current_orderby, 'date-desc', false ) . '>' . esc_html__( 'Oldest', 'shortcodeglut' ) . '</option>';
			echo '<option value="title-asc"' . selected( $current_orderby, 'title-asc', false ) . '>' . esc_html__( 'Title (A-Z)', 'shortcodeglut' ) . '</option>';
			echo '<option value="title-desc"' . selected( $current_orderby, 'title-desc', false ) . '>' . esc_html__( 'Title (Z-A)', 'shortcodeglut' ) . '</option>';
			echo '<option value="price-asc"' . selected( $current_orderby, 'price-asc', false ) . '>' . esc_html__( 'Price: Low to High', 'shortcodeglut' ) . '</option>';
			echo '<option value="price-desc"' . selected( $current_orderby, 'price-desc', false ) . '>' . esc_html__( 'Price: High to Low', 'shortcodeglut' ) . '</option>';
			echo '<option value="popularity-desc"' . selected( $current_orderby, 'popularity-desc', false ) . '>' . esc_html__( 'Best Selling', 'shortcodeglut' ) . '</option>';
			echo '<option value="popularity-asc"' . selected( $current_orderby, 'popularity-asc', false ) . '>' . esc_html__( 'Least Selling', 'shortcodeglut' ) . '</option>';
			echo '<option value="rating-desc"' . selected( $current_orderby, 'rating-desc', false ) . '>' . esc_html__( 'Top Rated', 'shortcodeglut' ) . '</option>';
			echo '<option value="rating-asc"' . selected( $current_orderby, 'rating-asc', false ) . '>' . esc_html__( 'Low Rated', 'shortcodeglut' ) . '</option>';
			echo '</select>';
		}

		echo '<div class="shortcodeglut-results-count">' . esc_html__( 'Loading products...', 'shortcodeglut' ) . '</div>';

		echo '</div>';
	}

	/**
	 * Render products
	 */
	private function render_products( $atts, $paged = 1 ) {
		// Determine how many products to show
		$posts_per_page = $atts['limit'];
		$use_pagination = $atts['paging'] && $atts['items_per_page'] > 0 && $atts['rows'] == 0;

		if ( $use_pagination ) {
			$posts_per_page = $atts['items_per_page'];
		}

		// Build WP_Query arguments with proper sorting
		$query_args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
		);

		// Handle different sorting options
		$default_order = 'DESC';

		switch ( $atts['order_by'] ) {
			case 'price-asc':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['order'] = 'ASC';
				break;
			case 'price-desc':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['order'] = 'DESC';
				break;
			case 'popularity-desc':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['order'] = 'DESC';
				break;
			case 'popularity-asc':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['order'] = 'ASC';
				break;
			case 'rating-desc':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['order'] = 'DESC';
				break;
			case 'rating-asc':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['order'] = 'ASC';
				break;
			case 'title-asc':
				$query_args['orderby'] = 'title';
				$query_args['order'] = 'ASC';
				break;
			case 'title-desc':
				$query_args['orderby'] = 'title';
				$query_args['order'] = 'DESC';
				break;
			case 'date-desc':
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'ASC';
				break;
			case 'date':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'DESC';
				break;
		}

		// Filter by category if specified
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering; tax_query is the standard WP approach.
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => $atts['category'],
				),
			);
		}

		// Exclude specific products
		if ( ! empty( $atts['exclude'] ) ) {
			$exclude_ids = array_map( 'absint', explode( ',', $atts['exclude'] ) );
			$query_args['post__not_in'] = $exclude_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Intentional exclusion of user-specified product IDs; dataset is small and bounded.
		}

		// Add tax query to only show visible products
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
		if ( ! empty( $product_visibility_term_ids['exclude-from-catalog'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			);
		}

		// Query products
		$products_query = new \WP_Query( $query_args );

		// Get template
		$template = null;
		$template_id = ! empty( $atts['template'] ) ? $atts['template'] : 'product_card_basic';
		$template = WooTemplatesEntity::get_template_by_template_id( $template_id );

		// Calculate grid columns: clamp between 1 and 6
		$columns = max( 1, min( 6, $atts['columns'] ) );

		// Gradients for placeholder images
		$gradients = array(
			'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
			'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
			'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
			'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
			'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
			'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
			'linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%)',
			'linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%)',
			'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
			'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
		);

		// Render masonry grid
		if ( $products_query->have_posts() ) {
			echo '<div class="shortcodeglut-masonry" style="column-count: ' . esc_attr( (string) $columns ) . '; column-gap: ' . esc_attr( (string) $atts['gap'] ) . 'px;">';

			$item_index = 0;
			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$gradient = $gradients[ $item_index % count( $gradients ) ];

				if ( $template ) {
					// Use WooTemplate
					$this->render_with_template( $product, $template, $atts, $gradient );
				} else {
					// Use default template
					$this->render_default_product( $product, $atts, $gradient );
				}

				$item_index++;
			}

			echo '</div>'; // End masonry

			// Update results count via inline script
			$showing = absint( $products_query->post_count );
			$total = absint( $products_query->found_posts );
			echo '<script>jQuery(".shortcodeglut-results-count").text("Showing ' . esc_js( (string) $showing ) . ' of ' . esc_js( (string) $total ) . ' products");</script>';

			// Pagination
			if ( $use_pagination ) {
				$this->render_pagination( $products_query, $atts, $paged );
			}

		} else {
			echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</p>';
		}

		wp_reset_postdata();
	}

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template, $atts, $gradient ) {
		// Check if this is a file-based template (has template_id but no template_html)
		$is_file_template = isset( $template['template_id'] ) && empty( $template['template_html'] );

		if ( $is_file_template && ! empty( $template['template_id'] ) ) {
			// Render file-based PHP template
			$this->render_file_template( $product, $template['template_id'], $atts, $gradient );
		} elseif ( ! empty( $template['template_html'] ) ) {
			// Render database template with tag replacement
			$html = $template['template_html'];
			$processed_html = $this->process_template_tags( $html, $product );

			// Generate unique ID for this template instance
			$template_instance_id = 'shortcodeglut-template-' . ( isset( $template['id'] ) ? $template['id'] : 'unknown' ) . '-' . uniqid();

			// Handle custom CSS from database
			if ( ! empty( $template['template_css'] ) ) {
				// Output custom CSS as inline style
				echo sprintf(
					'<style id="%s-css">%s</style>',
					esc_attr( $template_instance_id ),
					wp_kses_post( $template['template_css'] )
				);
			}

			// Output the HTML container
			echo sprintf(
				'<div id="%s" class="shortcodeglut-template">%s</div>',
				esc_attr( $template_instance_id ),
				wp_kses_post( $processed_html )
			);
		} else {
			// Fallback to default rendering
			$this->render_default_product( $product, $atts, $gradient );
		}
	}

	/**
	 * Render file-based PHP template
	 */
	private function render_file_template( $product, $template_id, $atts, $gradient ) {
		$template_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/template.php';
		$css_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/style.css';

		// Check if template file exists
		if ( ! file_exists( $template_path ) ) {
			$this->render_default_product( $product, $atts, $gradient );
			return;
		}

		// Load and enqueue template CSS
		if ( file_exists( $css_path ) ) {
			$css_url = SHORTCODEGLUT_URL . 'src/wooTemplates/templates/' . $template_id . '/style.css';
			wp_enqueue_style( 'shortcodeglut-template-' . $template_id, $css_url, array(), SHORTCODEGLUT_VERSION );
		} elseif ( strpos( $template_id, '_clone_' ) !== false ) {
			// For cloned templates, try to load CSS from the base template
			$base_template_id = preg_replace( '/_clone_\d+$/', '', $template_id );
			$base_css_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $base_template_id . '/style.css';
			if ( file_exists( $base_css_path ) ) {
				$css_url = SHORTCODEGLUT_URL . 'src/wooTemplates/templates/' . $base_template_id . '/style.css';
				wp_enqueue_style( 'shortcodeglut-template-' . $template_id, $css_url, array(), SHORTCODEGLUT_VERSION );
			}
		}

		// Enqueue Add to Cart handler for AJAX functionality
		wp_enqueue_script( 'shortcodeglut-add-to-cart-handler' );
		wp_enqueue_style( 'shortcodeglut-add-to-cart-handler' );

		// Generate unique ID for this template instance
		$template_instance_id = 'shortcodeglut-template-' . $template_id . '-' . uniqid();

		// Start output buffering
		ob_start();

		// Store the current global product (if any)
		$old_global_product = null;
		if ( isset( $GLOBALS['shortcodeglut_product'] ) ) {
			$old_global_product = $GLOBALS['shortcodeglut_product'];
		}

		// Set the global product variable for the template
		$GLOBALS['shortcodeglut_product'] = $product;
		$GLOBALS['shortcodeglut_gradient'] = $gradient;

		// Include the template file
		include $template_path;

		// Restore the previous global product
		if ( $old_global_product !== null ) {
			$GLOBALS['shortcodeglut_product'] = $old_global_product;
		} else {
			unset( $GLOBALS['shortcodeglut_product'] );
		}
		unset( $GLOBALS['shortcodeglut_gradient'] );

		// Get the output and clean the buffer
		$template_output = ob_get_clean();

		// Process template tags and replace with actual product data
		$template_output = $this->process_template_tags( $template_output, $product );

		// Output with wrapper
		echo sprintf(
			'<div id="%s" class="shortcodeglut-masonry-item">%s</div>',
			esc_attr( $template_instance_id ),
			wp_kses_post( $template_output )
		);
	}

	/**
	 * Process template tags and replace with actual product data
	 */
	private function process_template_tags( $html, $product ) {
		// Use ConditionalTagProcessor to handle all template tags including conditionals
		return ConditionalTagProcessor::process_with_image_size( $html, $product, 'woocommerce_thumbnail' );
	}

	/**
	 * Render product using default template
	 */
	private function render_default_product( $product, $atts, $gradient ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );
		$image_id = $product->get_image_id();

		// Determine image height class
		$image_height_class = '';
		if ( $atts['image_height'] === 'tall' ) {
			$image_height_class = 'tall';
		} elseif ( $atts['image_height'] === 'medium' ) {
			$image_height_class = 'medium';
		} elseif ( $atts['image_height'] === 'short' ) {
			$image_height_class = 'short';
		}

		// Get product tags
		$tags = array();
		if ( $atts['show_tags'] ) {
			$product_tags = get_the_terms( $product_id, 'product_tag' );
			if ( ! empty( $product_tags ) && ! is_wp_error( $product_tags ) ) {
				$tags = wp_list_pluck( $product_tags, 'name' );
			}
		}

		// Get product excerpt
		$excerpt = '';
		if ( $atts['show_excerpt'] ) {
			$excerpt = $product->get_short_description();
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( $product->get_description(), 15 );
			}
		}

		echo '<div class="shortcodeglut-masonry-item">';

		echo '<div class="shortcodeglut-card" style="border-radius: ' . esc_attr( (string) $atts['border_radius'] ) . 'px;">';

		// Card image
		echo '<div class="shortcodeglut-card-image ' . esc_attr( $image_height_class ) . '">';
		echo '<a href="' . esc_url( $permalink ) . '">';

		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'style' => 'width: 100%; height: 100%; object-fit: cover;' ) );
		} else {
			echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		}

		echo '</a>';
		echo '</div>';

		echo '<div class="shortcodeglut-card-body">';

		// Product tags
		if ( ! empty( $tags ) ) {
			echo '<span class="shortcodeglut-tag" style="background-color: ' . esc_attr( $atts['tag_color'] ) . '; color: ' . esc_attr( $atts['tag_text_color'] ) . ';">';
			echo esc_html( $tags[0] );
			echo '</span>';
		}

		// Product title
		echo '<div class="shortcodeglut-card-title">';
		echo '<a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a>';
		echo '</div>';

		// Product excerpt
		if ( ! empty( $excerpt ) ) {
			echo '<div class="shortcodeglut-card-excerpt">' . wp_kses_post( $excerpt ) . '</div>';
		}

		echo '<div class="shortcodeglut-card-footer">';

		// Product price
		echo '<span class="shortcodeglut-price">' . wp_kses_post( $product->get_price_html() ) . '</span>';

		// Add to cart button
		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			echo '<button class="shortcodeglut-btn ajax_add_to_cart" data-product_id="' . esc_attr( (string) $product_id ) . '">' . esc_html__( 'Add', 'shortcodeglut' ) . '</button>';
		} else {
			echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-btn">' . esc_html__( 'View', 'shortcodeglut' ) . '</a>';
		}

		echo '</div>'; // End footer
		echo '</div>'; // End body
		echo '</div>'; // End card
		echo '</div>'; // End masonry item
	}

	/**
	 * Render pagination
	 */
	private function render_pagination( $query, $atts, $current_page = 1 ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		$current_page = max( 1, $current_page );
		$max_pages = $query->max_num_pages;
		$is_ajax = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
		$ajax_class = $is_ajax ? ' async-pagination' : '';

		// Get current URL for building pagination links
		$current_url = home_url( add_query_arg( array() ) );

		echo '<div class="shortcodeglut-pagination' . esc_attr( $ajax_class ) . '">';
		echo '<ul class="page-numbers">';

		// Previous button
		if ( $current_page > 1 ) {
			$prev_page = $current_page - 1;
			if ( $is_ajax ) {
				$prev_link = '#';
				$data_page = ' data-page="' . esc_attr( (string) $prev_page ) . '"';
			} else {
				$prev_link = add_query_arg( array( 'shortcodeglut_paged' => $prev_page ), $current_url );
				$data_page = '';
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_page is pre-escaped when assigned.
			echo '<li><a class="prev page-numbers" href="' . esc_url( $prev_link ) . '"' . $data_page . '>&laquo;</a></li>';
		}

		// Page numbers
		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';
			if ( $is_ajax ) {
				$page_link = '#';
				$data_page = ' data-page="' . esc_attr( (string) $i ) . '"';
			} else {
				$page_link = add_query_arg( array( 'shortcodeglut_paged' => $i ), $current_url );
				$data_page = '';
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_page is pre-escaped when assigned.
			echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $page_link ) . '"' . $data_page . '>' . esc_html( (string) $i ) . '</a></li>';
		}

		// Next button
		if ( $current_page < $max_pages ) {
			$next_page = $current_page + 1;
			if ( $is_ajax ) {
				$next_link = '#';
				$data_page = ' data-page="' . esc_attr( (string) $next_page ) . '"';
			} else {
				$next_link = add_query_arg( array( 'shortcodeglut_paged' => $next_page ), $current_url );
				$data_page = '';
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_page is pre-escaped when assigned.
			echo '<li><a class="next page-numbers" href="' . esc_url( $next_link ) . '"' . $data_page . '>&raquo;</a></li>';
		}

		echo '</ul>';
		echo '</div>';
	}

	/**
	 * AJAX handler for loading products
	 */
	public function ajax_load_products() {
		check_ajax_referer( 'shortcodeglut_masonry_nonce', 'nonce' );

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		// Get attributes from AJAX request
		$atts = array(
			'columns' => isset( $_POST['columns'] ) ? absint( wp_unslash( $_POST['columns'] ) ) : 4,
			'rows' => isset( $_POST['rows'] ) ? absint( wp_unslash( $_POST['rows'] ) ) : 2,
			'limit' => isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 0,
			'order_by' => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'date',
			'order' => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 12,
			'template' => isset( $_POST['template'] ) && $_POST['template'] !== '' ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'product_card_basic',
			'paging' => isset( $_POST['paging'] ) ? absint( wp_unslash( $_POST['paging'] ) ) : 0,
			'ajax' => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
			'category' => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'exclude' => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitization only.
			'toolbar' => isset( $_POST['toolbar'] ) ? sanitize_text_field( wp_unslash( $_POST['toolbar'] ) ) : '1',
			'show_breadcrumb' => isset( $_POST['show_breadcrumb'] ) ? absint( wp_unslash( $_POST['show_breadcrumb'] ) ) : 0,
			'card_style' => isset( $_POST['card_style'] ) ? sanitize_text_field( wp_unslash( $_POST['card_style'] ) ) : 'modern',
			'gap' => isset( $_POST['gap'] ) ? absint( wp_unslash( $_POST['gap'] ) ) : 20,
			'border_radius' => isset( $_POST['border_radius'] ) ? absint( wp_unslash( $_POST['border_radius'] ) ) : 16,
			'shadow' => isset( $_POST['shadow'] ) ? filter_var( wp_unslash( $_POST['shadow'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'hover_lift' => isset( $_POST['hover_lift'] ) ? filter_var( wp_unslash( $_POST['hover_lift'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'image_height' => isset( $_POST['image_height'] ) ? sanitize_text_field( wp_unslash( $_POST['image_height'] ) ) : 'auto',
			'show_tags' => isset( $_POST['show_tags'] ) ? filter_var( wp_unslash( $_POST['show_tags'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_excerpt' => isset( $_POST['show_excerpt'] ) ? filter_var( wp_unslash( $_POST['show_excerpt'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'tag_color' => isset( $_POST['tag_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['tag_color'] ) ) : '#fef3c7',
			'tag_text_color' => isset( $_POST['tag_text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['tag_text_color'] ) ) : '#92400e',
		);

		// Render products
		ob_start();
		$this->render_products( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
