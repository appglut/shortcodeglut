<?php
/**
 * WooCommerce Category Shortcode Handler
 *
 * Similar to WPDM's [wpdm_category] shortcode, this handles [shortcodeglut_woo_category] shortcode
 * to display products from one or more categories using WooTemplates for rendering
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\WooCategory;

use Shortcodeglut\wooTemplates\WooTemplatesEntity;
use Shortcodeglut\wooTemplates\ConditionalTagProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCategoryShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shortcodeglut_woo_category', array( $this, 'render_category_shortcode' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_shortcodeglut_woo_category_products', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_woo_category_products', array( $this, 'ajax_load_products' ) );

		// Register assets for frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register assets (style handle for inline CSS)
	 */
	public function register_assets() {
		// Register a handle for inline template styles
		wp_register_style(
			'shortcodeglut-woo-category-templates',
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
	public function render_category_shortcode( $atts ) {
		// Skip rendering during REST API requests (block editor validation)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-woo-category-placeholder">[Shortcodeglut WooCommerce Category Products]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		 $this->shortcode_counter++;        
		$unique_id = 'shortcodeglut_woo_category_' . $this->shortcode_counter;
																																			
		// Get current page from URL (for normal pagination)
		$current_page = get_query_var('paged') ? get_query_var('paged') : 1;    

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			'categories' => '',                  // Category slug or slugs (comma-separated) - REQUIRED
			'cat_field' => 'slug',               // Field to use for category ID (slug or id)
			'operator' => 'IN',                  // Query operator: IN, NOT IN, AND, EXISTS, NOT EXISTS
			'icon' => '',                        // Custom icon URL
			'icon_width' => 64,                  // Icon width in pixels
			'title' => '',                       // Custom title text (any text will be displayed as-is)
			'desc' => '',                        // Custom description or "1" for category description
			'items_per_page' => 10,              // Products per page
			'orderby' => 'date',                 // Order field: id, title, date, modified, price, total_sales, rating, popularity
			'order' => 'DESC',                   // Order direction: ASC or DESC
			'template' => 'product_card_basic',                    // Template ID from WooTemplates (default: product_card_basic)
			'toolbar' => '1',                    // Show toolbar: 1, 0, or "compact"
			'paging' => '1',                     // Show pagination: 1 or 0
			'cols' => 3,                         // Number of columns for desktop
			'colspad' => 1,                      // Number of columns for tablet
			'colsphone' => 1,                    // Number of columns for mobile
			'ajax' => 'off',                     // Enable AJAX for filtering: on or off (default: off)
			'ajax_pagination' => 'off',          // Enable AJAX for pagination: on or off (default: off)
		), $atts, 'shortcodeglut_woo_category' );

		// Validate required parameter
		if ( empty( $atts['categories'] ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'Category slug(s) is required for [shortcodeglut_woo_category] shortcode.', 'shortcodeglut' ) . '</p>';
		}

		// Sanitize and convert attributes
		$atts['categories'] = sanitize_text_field( $atts['categories'] );
		$atts['cat_field'] = sanitize_text_field( $atts['cat_field'] );
		$atts['operator'] = strtoupper( sanitize_text_field( $atts['operator'] ) );
		$atts['icon'] = esc_url( $atts['icon'] );
		$atts['icon_width'] = absint( $atts['icon_width'] );
		$atts['title'] = sanitize_text_field( $atts['title'] );
		$atts['desc'] = sanitize_text_field( $atts['desc'] );
		$atts['items_per_page'] = absint( $atts['items_per_page'] );
		$atts['orderby'] = sanitize_text_field( $atts['orderby'] );
		$atts['order'] = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['template'] = sanitize_text_field( $atts['template'] );
		$atts['toolbar'] = sanitize_text_field( $atts['toolbar'] );
		$atts['paging'] = absint( $atts['paging'] );
		$atts['cols'] = absint( $atts['cols'] );
		$atts['colspad'] = absint( $atts['colspad'] );
		$atts['colsphone'] = absint( $atts['colsphone'] );
		$atts['ajax'] = strtolower( sanitize_text_field( $atts['ajax'] ) );
			$atts['ajax'] = strtolower( sanitize_text_field( $atts['ajax'] ) );
			$atts['ajax_pagination'] = strtolower( sanitize_text_field( $atts['ajax_pagination'] ) );
			// Convert ajax to boolean
			$ajax_enabled = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
			$ajax_pagination_enabled = ( $atts['ajax_pagination'] === 'on' || $atts['ajax_pagination'] === '1' || $atts['ajax_pagination'] === 'true' );

			// Get category information
		// Get category information
		$category_slugs = array_map( 'trim', explode( ',', $atts['categories'] ) );
		$categories = $this->get_categories( $category_slugs, $atts['cat_field'] );

		if ( empty( $categories ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'No categories found.', 'shortcodeglut' ) . '</p>';
		}

		// Get the first category for title/desc/icon if not provided
		$first_category = $categories[0];

		// Prepare category data
		$category_data = $this->prepare_category_data( $first_category, $atts );

		// Enqueue required assets
		$this->enqueue_assets( $atts );

		// Start output buffering
		ob_start();

		// Render the shortcode output
		 $this->render_output( $unique_id, $atts, $category_data, $categories, $current_page );

		return ob_get_clean();
	}

	/**
	 * Enqueue required CSS and JS
	 */
	private function enqueue_assets( $atts ) {
		global $shortcodeglut_woo_category_ajax_enabled;
		global $shortcodeglut_woo_category_ajax_pagination_enabled;

		// Enqueue Bootstrap-like styles
		wp_enqueue_style( 'shortcodeglut-woo-category-shortcode', SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/css/woo-category.css', array(), SHORTCODEGLUT_VERSION );

		// Store ajax_enabled globally for use in other methods
		$shortcodeglut_woo_category_ajax_enabled = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
		$shortcodeglut_woo_category_ajax_pagination_enabled = ( $atts['ajax_pagination'] === 'on' || $atts['ajax_pagination'] === '1' || $atts['ajax_pagination'] === 'true' );

		// Enqueue script if either ajax or ajax_pagination is enabled
		if ( $shortcodeglut_woo_category_ajax_enabled || $shortcodeglut_woo_category_ajax_pagination_enabled ) {
			wp_enqueue_script( 'shortcodeglut-woo-category-shortcode', SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/js/woo-category.js', array( 'jquery' ), SHORTCODEGLUT_VERSION, true );
			wp_localize_script( 'shortcodeglut-woo-category-shortcode', 'shortcodeglutWooCategoryAjax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'shortcodeglut_woo_category_nonce' ),
				'ajax_pagination_enabled' => $shortcodeglut_woo_category_ajax_pagination_enabled,
			) );
		}
	}

	/**
	 * Get categories by slugs or IDs
	 */
	private function get_categories( $values, $field = 'slug' ) {
		$args = array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		);

		if ( $field === 'id' ) {
			$args['include'] = array_map( 'absint', $values );
		} else {
			$args['slug'] = $values;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * Prepare category display data
	 */
	private function prepare_category_data( $category, $atts ) {
		$data = array();

		// Icon
		if ( ! empty( $atts['icon'] ) ) {
			$data['icon'] = $atts['icon'];
		} else {
			// Try to get category thumbnail
			$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
			if ( $thumbnail_id ) {
				$image = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
				$data['icon'] = $image ? $image[0] : '';
			} else {
				$data['icon'] = '';
			}
		}

		// Title - any text provided will be displayed as-is
		if ( ! empty( $atts['title'] ) ) {
			$data['title'] = $atts['title'];
		} else {
			$data['title'] = '';
		}

		// Description
		if ( $atts['desc'] === '1' ) {
			$data['desc'] = $category->description;
		} elseif ( ! empty( $atts['desc'] ) ) {
			$data['desc'] = $atts['desc'];
		} else {
			$data['desc'] = '';
		}

		return $data;
	}

	/**
	 * Render the complete shortcode output
	 */
	 private function render_output( $unique_id, $atts, $category_data, $categories, $paged = 1 ) {
		$form_id = 'sc_form_' . $unique_id;
		$content_id = 'content_' . $unique_id;

        $atts_json = wp_json_encode( $atts );             
		$safe_atts = htmlspecialchars( $atts_json, ENT_QUOTES, 'UTF-8' );
																																			
		echo '<div class="shortcodeglut" id="' . esc_attr( $unique_id ) . '" data-atts="' . esc_attr( $safe_atts ) . '">';
		// Toolbar form
		if ( $atts['toolbar'] !== '0' ) {
			$this->render_toolbar( $form_id, $content_id, $atts, $category_data );
		}

		echo '<div class="spacer mb-3 d-block clearfix"></div>';

		// Products content area
		echo '<div id="' . esc_attr( $content_id ) . '">';

		// Load products

		$this->render_products( $atts, $categories, $paged );
		
		echo '</div>'; // End content area

		echo '<div style="clear:both"></div>';
		echo '</div>';
	}

	/**
	 * Render the toolbar with search, sort, and filter options
	 */
	private function render_toolbar( $form_id, $content_id, $atts, $category_data ) {
			global $shortcodeglut_woo_category_ajax_enabled;
			global $shortcodeglut_woo_category_ajax_pagination_enabled;

			// Determine if compact mode
			$is_compact = ( $atts['toolbar'] === 'compact' );

			// Determine async class for form (for filtering)
			$async_class = $shortcodeglut_woo_category_ajax_enabled ? '__shortcodeglut_submit_async' : '';

			// Get current values from GET parameters or use defaults
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
			$current_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $atts['orderby'];
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
			$current_order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : $atts['order'];

			// Build data attributes for AJAX
			$data_attrs = array(
				'shortcode-categories' => $atts['categories'],
				'shortcode-cat_field' => $atts['cat_field'],
				'shortcode-operator' => $atts['operator'],
				'shortcode-items_per_page' => $atts['items_per_page'],
				'shortcode-orderby' => $atts['orderby'],
				'shortcode-order' => $atts['order'],
				'shortcode-template' => $atts['template'],
				'shortcode-paging' => $atts['paging'],
				'shortcode-cols' => $atts['cols'],
				'shortcode-colspad' => $atts['colspad'],
				'shortcode-colsphone' => $atts['colsphone'],
				'shortcode-ajax' => $atts['ajax'],
				'shortcode-ajax-pagination' => $atts['ajax_pagination'],
			);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$current_order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : $atts['order'];

		// Build data attributes for AJAX
		$data_attrs = array(
			 'shortcode-categories' => $atts['categories'],
      'shortcode-cat_field' => $atts['cat_field'],  
      'shortcode-operator' => $atts['operator'],  
      'shortcode-items_per_page' => $atts['items_per_page'],
      'shortcode-orderby' => $atts['orderby'],              
      'shortcode-order' => $atts['order'],
      'shortcode-template' => $atts['template'],                                                                                      
      'shortcode-paging' => $atts['paging'],
      'shortcode-cols' => $atts['cols'],                                                                                              
      'shortcode-colspad' => $atts['colspad'],              
      'shortcode-colsphone' => $atts['colsphone'],
      'shortcode-ajax' => $atts['ajax'],
      'shortcode-ajax_pagination' => $atts['ajax_pagination']
		);

		 // Encode attributes for data attribute (for AJAX) - like BasicGrid does
		$atts_json = wp_json_encode( $atts );                                   
		$safe_atts = htmlspecialchars( $atts_json, ENT_QUOTES, 'UTF-8' );
																		
		echo '<form method="get" class="' . esc_attr( $async_class ) . '" data-container="#' . esc_attr( $content_id ) . '" data-atts="' .
		esc_attr( $safe_atts ) . '" id="' . esc_attr( $form_id ) . '">'; 

		// Clean filter toolbar container
		echo '<div class="shortcodeglut-filter-toolbar" style="
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 20px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		">';

		// Category panel header
		if ( ! $is_compact && ( ! empty( $category_data['title'] ) || ! empty( $category_data['desc'] ) ) ) {
			echo '<div class="shortcodeglut-category-header" style="
				background: #f9fafb;
				border-radius: 8px;
				padding: 16px 20px;
				margin-bottom: 20px;
				border-bottom: 1px solid #e5e7eb;
			">';
			echo '<div class="media" style="align-items: center;">';

			if ( ! empty( $category_data['icon'] ) ) {
				echo '<div class="shortcodeglut-category-icon" style="
					width: 50px;
					height: 50px;
					border-radius: 8px;
					overflow: hidden;
					flex-shrink: 0;
					background: #f3f4f6;
					display: flex;
					align-items: center;
					justify-content: center;
					margin-right: 16px;
				">';
				echo '<img src="' . esc_url( $category_data['icon'] ) . '" alt="" style="width: 100%; height: 100%; object-fit: cover;">';
				echo '</div>';
			}

			echo '<div class="media-body">';
			if ( ! empty( $category_data['title'] ) ) {
				echo '<h3 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 600; color: #111827;">' . esc_html( $category_data['title'] ) . '</h3>';
			}
			if ( ! empty( $category_data['desc'] ) ) {
				echo '<p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.5;">' . wp_kses_post( $category_data['desc'] ) . '</p>';
			}
			echo '</div>';

			echo '</div>';
			echo '</div>';
		}

		// Filter sections container
		echo '<div class="shortcodeglut-filters-container">';

		// Row 1: Search and Sort options
		echo '<div class="row" style="margin-bottom: 16px;">';

		// Search field
		echo '<div class="col-lg-3 col-md-6" style="padding-right: 12px;">';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for search filtering
		echo '<input type="text" name="skw" value="' . esc_attr( isset( $_GET['skw'] ) ? sanitize_text_field( wp_unslash( $_GET['skw'] ) ) : '' ) . '" placeholder="' . esc_attr__( 'Search products...', 'shortcodeglut' ) . '" class="form-control" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; font-size: 14px; transition: border-color 0.2s;" onfocus="this.style.borderColor=\'#3b82f6\';" onblur="this.style.borderColor=\'#d1d5db\';">';
		echo '</div>';

		// Order By dropdown
		echo '<div class="col-lg-3 col-md-2" style="padding-right: 12px;">';
		echo '<select name="orderby" class="form-control" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; font-size: 14px; line-height: 1.5; height: 42px; min-height: 42px; width: 100%; min-width: 140px; transition: border-color 0.2s; cursor: pointer;" onfocus="this.style.borderColor=\'#3b82f6\';" onblur="this.style.borderColor=\'#d1d5db\';">';
		echo '<option value="" disabled="disabled">' . esc_html__( 'Sort By', 'shortcodeglut' ) . '</option>';
		echo '<option value="date"' . selected( $current_orderby, 'date', false ) . '>' . esc_html__( 'Latest', 'shortcodeglut' ) . '</option>';
		echo '<option value="title"' . selected( $current_orderby, 'title', false ) . '>' . esc_html__( 'Name', 'shortcodeglut' ) . '</option>';
		echo '<option value="price"' . selected( $current_orderby, 'price', false ) . '>' . esc_html__( 'Price', 'shortcodeglut' ) . '</option>';
		echo '<option value="rating"' . selected( $current_orderby, 'rating', false ) . '>' . esc_html__( 'Rating', 'shortcodeglut' ) . '</option>';
		echo '<option value="popularity"' . selected( $current_orderby, 'popularity', false ) . '>' . esc_html__( 'Popularity', 'shortcodeglut' ) . '</option>';
		echo '<option value="total_sales"' . selected( $current_orderby, 'total_sales', false ) . '>' . esc_html__( 'Sales', 'shortcodeglut' ) . '</option>';
		echo '</select>';
		echo '</div>';

		// Order direction dropdown
		echo '<div class="col-lg-3 col-md-2" style="padding-right: 12px;">';
		echo '<select name="order" class="form-control" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; font-size: 14px; line-height: 1.5; height: 42px; min-height: 42px; width: 100%; min-width: 140px; transition: border-color 0.2s; cursor: pointer;" onfocus="this.style.borderColor=\'#3b82f6\';" onblur="this.style.borderColor=\'#d1d5db\';">';
		echo '<option value="" disabled="disabled">' . esc_html__( 'Order', 'shortcodeglut' ) . '</option>';
		echo '<option value="ASC"' . selected( $current_order, 'ASC', false ) . '>' . esc_html__( 'Low to High', 'shortcodeglut' ) . '</option>';
		echo '<option value="DESC"' . selected( $current_order, 'DESC', false ) . '>' . esc_html__( 'High to Low', 'shortcodeglut' ) . '</option>';
		echo '</select>';
		echo '</div>';

		// Apply Button
		echo '<div class="col-lg-3 col-md-2">';
		echo '<button type="submit" style="background: #2563eb; border: none; border-radius: 6px; padding: 10px 24px; font-size: 14px; font-weight: 600; color: #ffffff; transition: all 0.2s; cursor: pointer; width: 100%; height: 42px;" onmouseover="this.style.backgroundColor=\'#1d4ed8\';" onmouseout="this.style.backgroundColor=\'#2563eb\';">';
		echo esc_html__( 'Apply', 'shortcodeglut' );
		echo '</button>';
		echo '</div>';

		echo '</div>'; // End Row 1

		echo '</div>'; // End filters container
		echo '</div>'; // End filter toolbar
		echo '</form>';
	}

	/**
	 * Render products based on category and filters
	 */
	private function render_products( $atts, $categories, $paged = 1, $search_keyword = '', $orderby = '', $order = '' ) {
		// Get category IDs
		$category_ids = array();
		$category_slugs = array();
		foreach ( $categories as $category ) {
			$category_ids[] = $category->term_id;
			$category_slugs[] = $category->slug;
		}

		// Check for form submissions (read-only GET parameters for filtering)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for search
		$search_keyword = ! empty( $search_keyword ) ? $search_keyword : ( isset( $_GET['skw'] ) ? sanitize_text_field( wp_unslash( $_GET['skw'] ) ) : '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$orderby = ! empty( $orderby ) ? $orderby : ( isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $atts['orderby'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$order = ! empty( $order ) ? strtoupper( $order ) : ( isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : $atts['order'] );

		// Get new filter values
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for filtering
		$min_price = isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for filtering
		$max_price = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- filter_var handles sanitization
		$in_stock_only = isset( $_GET['in_stock'] ) ? filter_var( $_GET['in_stock'], FILTER_VALIDATE_BOOLEAN ) : false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- filter_var handles sanitization
		$on_sale_only = isset( $_GET['on_sale'] ) ? filter_var( $_GET['on_sale'], FILTER_VALIDATE_BOOLEAN ) : false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- filter_var handles sanitization
		$featured_only = isset( $_GET['featured'] ) ? filter_var( $_GET['featured'], FILTER_VALIDATE_BOOLEAN ) : false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for filtering
		$min_rating = isset( $_GET['min_rating'] ) ? intval( $_GET['min_rating'] ) : '';

		// Build WP_Query arguments
		$query_args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => $atts['items_per_page'],
			'paged' => $paged,
			'orderby' => $orderby,
			'order' => $order,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Standard WooCommerce category filtering
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field' => $atts['cat_field'] === 'id' ? 'term_id' : 'slug',
					'terms' => $atts['cat_field'] === 'id' ? $category_ids : $category_slugs,
					'operator' => $atts['operator'],
				),
			),
		);

		// Add search if provided
		if ( ! empty( $search_keyword ) ) {
			$query_args['s'] = $search_keyword;
		}

		// Handle special orderby cases
		if ( $orderby === 'price' ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Standard WooCommerce sorting by price
			$query_args['meta_key'] = '_price';
			$query_args['orderby'] = 'meta_value_num';
		} elseif ( $orderby === 'total_sales' ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Standard WooCommerce sorting by sales
			$query_args['meta_key'] = 'total_sales';
			$query_args['orderby'] = 'meta_value_num';
		} elseif ( $orderby === 'rating' ) {
			// Sorting by rating requires post-query sorting in WooCommerce
			// We'll use the average rating meta key
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Standard WooCommerce sorting by rating
			$query_args['meta_key'] = '_wc_average_rating';
			$query_args['orderby'] = 'meta_value_num';
		} elseif ( $orderby === 'popularity' ) {
			// Popularity is based on rating count in WooCommerce
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Standard WooCommerce sorting by popularity
			$query_args['meta_key'] = '_wc_review_count';
			$query_args['orderby'] = 'meta_value_num';
		}

		// Initialize meta query for custom filters
		$meta_query = array();

		// Price range filter
		if ( ! empty( $min_price ) || ! empty( $max_price ) ) {
			$price_query = array(
				'relation' => 'AND',
			);

			if ( ! empty( $min_price ) ) {
				$price_query[] = array(
					'key' => '_price',
					'value' => $min_price,
					'compare' => '>=',
					'type' => 'NUMERIC',
				);
			}

			if ( ! empty( $max_price ) ) {
				$price_query[] = array(
					'key' => '_price',
					'value' => $max_price,
					'compare' => '<=',
					'type' => 'NUMERIC',
				);
			}

			$meta_query[] = $price_query;
		}

		// In stock filter
		if ( $in_stock_only ) {
			$meta_query[] = array(
				'key' => '_stock_status',
				'value' => 'instock',
				'compare' => '=',
			);
		}

		// Apply meta query if not empty
		if ( ! empty( $meta_query ) ) {
			// Add relation if multiple meta queries
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
			}
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Standard WooCommerce meta filtering
			$query_args['meta_query'] = $meta_query;
		}

		// Taxonomy query for product visibility
		$tax_query = array();

		// On sale filter - using product visibility taxonomy
		if ( $on_sale_only ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field' => 'slug',
				'terms' => 'onsale',
			);
		}

		// Featured filter - using product visibility taxonomy
		if ( $featured_only ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field' => 'slug',
				'terms' => 'featured',
			);
		}

		// Merge with existing tax_query if needed
		if ( ! empty( $tax_query ) ) {
			// Add relation if multiple tax queries
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Standard WooCommerce tax filtering
			$query_args['tax_query'] = array_merge( $query_args['tax_query'], $tax_query );
		}

		// Rating filter - need to filter posts after query
		$rating_filter = false;
		if ( ! empty( $min_rating ) ) {
			$rating_filter = true;
		}

		// Query products
		$products_query = new \WP_Query( $query_args );

		// Apply rating filter if set
		if ( $rating_filter && $products_query->have_posts() ) {
			$filtered_posts = array();
			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( $product && $product->get_average_rating() >= $min_rating ) {
					$filtered_posts[] = get_the_ID();
				}
			}
			wp_reset_postdata();

			// Create new query with filtered posts
			if ( ! empty( $filtered_posts ) ) {
				$query_args['post__in'] = $filtered_posts;
				$query_args['posts_per_page'] = -1; // Get all filtered posts
				$products_query = new \WP_Query( $query_args );
			} else {
				// No products match the rating filter
				$query_args['post__in'] = array( 0 ); // Force empty result
				$products_query = new \WP_Query( $query_args );
			}
		}

		// Get template - use default 'product_card_basic' if no template specified
		$template = null;
		$template_id = ! empty( $atts['template'] ) ? $atts['template'] : 'product_card_basic';
		$template = WooTemplatesEntity::get_template_by_template_id( $template_id );

		// If template not found and user specified a custom one, log error but continue with default
		if ( ! $template && ! empty( $atts['template'] ) ) {
			// Try to get default template as fallback
			$template = WooTemplatesEntity::get_template_by_template_id( 'product_card_basic' );
		}

		// Calculate column classes - ensure valid Bootstrap column values
		// Bootstrap uses a 12-column grid, so valid column counts must divide evenly into 12: 1, 2, 3, 4, 6, 12
		// Invalid values will fall back to sensible defaults
		$valid_cols = array( 1, 2, 3, 4, 6, 12 );

		// Validate and sanitize cols values with fallbacks
		$cols_lg = in_array( $atts['cols'], $valid_cols ) ? $atts['cols'] : 3;        // Desktop: default 3 cols
		$cols_md = in_array( $atts['colspad'], $valid_cols ) ? $atts['colspad'] : 2;   // Tablet: default 2 cols
		$cols_sm = in_array( $atts['colsphone'], $valid_cols ) ? $atts['colsphone'] : 1; // Mobile: default 1 col

		// Calculate Bootstrap column classes (12 / number of columns = column width)
		// Examples: 2 cols = col-lg-6 (50%), 3 cols = col-lg-4 (33%), 4 cols = col-lg-3 (25%)
		$col_classes = array(
			'col-lg-' . intval( 12 / $cols_lg ),
			'col-md-' . intval( 12 / $cols_md ),
			'col-' . intval( 12 / $cols_sm ),
		);
		$col_class = implode( ' ', $col_classes );

		// Render products
		if ( $products_query->have_posts() ) {
			echo '<div class="row">';

			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				echo '<div class="' . esc_attr( $col_class ) . '">';

				if ( $template ) {
					// Use WooTemplate
					$this->render_with_template( $product, $template );
				} else {
					// Use default template
					$this->render_default_product( $product );
				}

				echo '</div>';
			}

			echo '</div>'; // End row

			// Pagination
			if ( $atts['paging'] ) {
				$this->render_pagination( $products_query, $atts, $paged );
			}

		} else {
			echo '<div class="shortcodeglut-no-products" style="
				text-align: center;
				padding: 60px 20px;
				background: #f9fafb;
				border-radius: 12px;
				margin: 30px 0;
			">';
			echo '<div style="
				font-size: 64px;
				color: #d1d5db;
				margin-bottom: 20px;
			">📦</div>';
			echo '<h3 style="
				font-size: 20px;
				font-weight: 600;
				color: #374151;
				margin: 0 0 10px 0;
			">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</h3>';
			echo '<p style="
				font-size: 15px;
				color: #6b7280;
				margin: 0;
			">' . esc_html__( 'Try adjusting your filters or search terms.', 'shortcodeglut' ) . '</p>';
			echo '</div>';
		}

		wp_reset_postdata();
	}

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template ) {
		// Check if this is a file-based template (has template_id but no template_html)
		$is_file_template = isset( $template['template_id'] ) && empty( $template['template_html'] );

		if ( $is_file_template && ! empty( $template['template_id'] ) ) {
			// Render file-based PHP template
			$this->render_file_template( $product, $template['template_id'] );
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
			$this->render_default_product( $product );
		}
	}

	/**
	 * Render file-based PHP template
	 */
	private function render_file_template( $product, $template_id ) {
		$template_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/template.php';
		$css_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/style.css';

		// Check if template file exists
		if ( ! file_exists( $template_path ) ) {
			$this->render_default_product( $product );
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

		// Include the template file
		include $template_path;

		// Restore the previous global product
		if ( $old_global_product !== null ) {
			$GLOBALS['shortcodeglut_product'] = $old_global_product;
		} else {
			unset( $GLOBALS['shortcodeglut_product'] );
		}

		// Get the output and clean the buffer
		$template_output = ob_get_clean();

		// Process template tags and replace with actual product data
		$template_output = $this->process_template_tags($template_output, $product);

		// Output with wrapper - escape output for security
		echo sprintf(
			'<div id="%s" class="shortcodeglut-template">%s</div>',
			esc_attr( $template_instance_id ),
			wp_kses_post( $template_output )
		);
	}

	/**
	 * Process template tags and replace with actual product data
	 */
	private function process_template_tags( $html, $product ) {
		// First, process conditional tags using ConditionalTagProcessor
		$html = ConditionalTagProcessor::process($html, $product);

		// Process categories specifically (handles [product_categories])
		$html = ConditionalTagProcessor::process_categories($html, $product);

		// Process rating specifically (handles [product_rating])
		$html = ConditionalTagProcessor::process_rating($html, $product);

		// Process discount badge specifically (handles [product_badge_sale])
		$html = ConditionalTagProcessor::process_discount_badge($html, $product);

		// Then process remaining basic tags
		$replacements = array(
			'[product_id]' => (string) $product->get_id(),
			'[product_permalink]' => esc_url($product->get_permalink()),
			'[product_title]' => $product->get_name(),
			'[product_price]' => $product->get_price_html(),
			'[product_regular_price]' => $product->get_regular_price() ? wc_price( $product->get_regular_price() ) : '',
			'[product_sale_price]' => $product->is_on_sale() ? wc_price( $product->get_sale_price() ) : '',
			'[product_short_description]' => $product->get_short_description() ?: '',
			'[product_description]' => $product->get_description() ?: '',
			'[sku]' => $product->get_sku() ?: '',
			'[stock_status]' => $product->get_stock_status() ?: '',
			'[stock_quantity]' => $product->get_stock_quantity() !== null ? (string) $product->get_stock_quantity() : '',
			'[add_to_cart_url]' => esc_url($product->add_to_cart_url()),
			'[cart_url]' => esc_url(wc_get_cart_url()),
		);

		// Product image
		if ( strpos( $html, '[product_image]' ) !== false ) {
			$image = $product->get_image_id() ?
				wp_get_attachment_image( $product->get_image_id(), 'woocommerce_single' ) :
				wc_placeholder_img( 'woocommerce_single' );
			$replacements['[product_image]'] = $image;
		}


		// Product gallery
		if ( strpos( $html, '[product_gallery]' ) !== false ) {
			$gallery_html = '';
			$attachment_ids = $product->get_gallery_image_ids();

			if ( ! empty( $attachment_ids ) ) {
				$gallery_html .= '<div class="shortcodeglut-product-gallery">';
				foreach ( $attachment_ids as $attachment_id ) {
					$gallery_html .= wp_get_attachment_image( $attachment_id, 'woocommerce_thumbnail' );
				}
				$gallery_html .= '</div>';
			}

			$replacements['[product_gallery]'] = $gallery_html;
		}

		// Categories
		if ( strpos( $html, '[product_categories]' ) !== false ) {
			$categories = get_the_terms( $product->get_id(), 'product_cat' );
			$categories_html = '';

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$categories_html = '<span class="shortcodeglut-product-categories">';
				$cat_links = array();

				foreach ( $categories as $category ) {
					$cat_links[] = '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
				}

				$categories_html .= implode( ', ', $cat_links );
				$categories_html .= '</span>';
			}

			$replacements['[product_categories]'] = $categories_html;
		}

		// Tags
		if ( strpos( $html, '[product_tags]' ) !== false ) {
			$tags = get_the_terms( $product->get_id(), 'product_tag' );
			$tags_html = '';

			if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
				$tags_html = '<span class="shortcodeglut-product-tags">';
				$tag_links = array();

				foreach ( $tags as $tag ) {
					$tag_links[] = '<a href="' . esc_url( get_term_link( $tag ) ) . '">' . esc_html( $tag->name ) . '</a>';
				}

				$tags_html .= implode( ', ', $tag_links );
				$tags_html .= '</span>';
			}

			$replacements['[product_tags]'] = $tags_html;
		}

		// Buttons
		if ( strpos( $html, '[btn_cart]' ) !== false ) {
			$cart_button = sprintf(
				'<a href="%s" class="button shortcodeglut-add-to-cart %s" %s>%s</a>',
				esc_url( $product->add_to_cart_url() ),
				$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button ajax_add_to_cart' : '',
				$product->is_purchasable() && $product->is_in_stock() ? 'data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '"' : '',
				esc_html( $product->is_purchasable() && $product->is_in_stock() ? __( 'Add to cart', 'shortcodeglut' ) : __( 'Read more', 'shortcodeglut' ) )
			);
			$replacements['[btn_cart]'] = $cart_button;
		}

		if ( strpos( $html, '[btn_view]' ) !== false ) {
			$view_button = sprintf(
				'<a href="%s" class="button shortcodeglut-view-product">%s</a>',
				esc_url( get_permalink( $product->get_id() ) ),
				esc_html__( 'View product', 'shortcodeglut' )
			);
			$replacements['[btn_view]'] = $view_button;
		}

		// Rating
		if ( strpos( $html, '[product_rating]' ) !== false ) {
			$rating_html = wc_get_rating_html( $product->get_average_rating() );
			$replacements['[product_rating]'] = $rating_html;
		}

		if ( strpos( $html, '[product_rating_count]' ) !== false ) {
			$rating_count = $product->get_rating_count();
			$rating_count_html = $rating_count > 0 ?
				'<span class="shortcodeglut-rating-count">(' . $rating_count . ')</span>' :
				'';
			$replacements['[product_rating_count]'] = $rating_count_html;
		}

		// Attributes
		if ( strpos( $html, '[product_attributes]' ) !== false ) {
			$attributes = $product->get_attributes();
			$attributes_html = '';

			if ( ! empty( $attributes ) ) {
				$attributes_html = '<div class="shortcodeglut-product-attributes">';

				foreach ( $attributes as $attribute ) {
					if ( $attribute->get_visible() ) {
						$attribute_name = wc_attribute_label( $attribute->get_name() );
						$values = array();

						if ( $attribute->is_taxonomy() ) {
							$attribute_terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );
							foreach ( $attribute_terms as $term ) {
								$values[] = $term->name;
							}
						} else {
							$values = $attribute->get_options();
						}

						$attributes_html .= '<div class="shortcodeglut-product-attribute">';
						$attributes_html .= '<span class="attribute-label">' . esc_html( $attribute_name ) . ': </span>';
						$attributes_html .= '<span class="attribute-value">' . esc_html( implode( ', ', $values ) ) . '</span>';
						$attributes_html .= '</div>';
					}
				}

				$attributes_html .= '</div>';
			}

			$replacements['[product_attributes]'] = $attributes_html;
		}

		// Dimensions
		if ( strpos( $html, '[product_dimensions]' ) !== false ) {
			$dimensions = $product->has_dimensions() ?
				wc_format_dimensions( $product->get_dimensions( false ) ) :
				'';
			$replacements['[product_dimensions]'] = $dimensions ?
				'<span class="shortcodeglut-dimensions">' . $dimensions . '</span>' :
				'';
		}

		// Weight
		if ( strpos( $html, '[product_weight]' ) !== false ) {
			$weight = $product->get_weight();
			$replacements['[product_weight]'] = $weight ?
				'<span class="shortcodeglut-weight">' . wc_format_weight( $weight ) . '</span>' :
				'';
		}

		// Replace all tags
		foreach ( $replacements as $tag => $replacement ) {
			$html = str_replace( $tag, (string) $replacement, $html );
		}

		return $html;
	}

	/**
	 * Render product using default template
	 */
	private function render_default_product( $product ) {
		echo '<!-- Shortcodeglut Default Product Template -->';
		echo '<div class="shortcodeglut-default-product-card card mb-2">';
		echo '<div class="card-body">';
		echo '<div class="media">';

		// Product image
		echo '<div class="mr-3 shortcodeglut-product-img">';
		echo wp_kses_post( $product->get_image( 'thumbnail' ) );
		echo '</div>';

		// Product info
		echo '<div class="media-body">';
		echo '<h3 class="product-title"><a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a></h3>';
		echo '<div class="text-muted text-small">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		echo '</div>';

		// Add to cart button
		echo '<div class="ml-3">';
		echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" class="btn btn-primary shortcodeglut-add-to-cart">' . esc_html__( 'Add to Cart', 'shortcodeglut' ) . '</a>';
		echo '</div>';

		echo '</div>'; // End media
		echo '</div>'; // End card-body
		echo '</div>'; // End card
	}

	/**
	 * Render pagination
	 */
	/**
		 * Render pagination
		 */
		private function render_pagination( $query, $atts, $current_page = 1 ) {
			global $shortcodeglut_woo_category_ajax_pagination_enabled;

			if ( $query->max_num_pages <= 1 ) {
				return;
			}

			$current_page = max( 1, $current_page );
			$max_pages = $query->max_num_pages;
			$is_ajax = $shortcodeglut_woo_category_ajax_pagination_enabled;
				$ajax_class = $is_ajax ? ' shortcodeglut-async-pagination' : '';

				// Get current URL for building pagination links
				$current_url = home_url( add_query_arg( array() ) );

				echo '<div style="clear:both"></div>';
				echo '<div class="shortcodeglut-pagination' . esc_attr( $ajax_class ) . ' text-center">';
			echo '<ul class="pagination shortcodeglut-pagination pagination-centered">';

			// Previous button
			if ( $current_page > 1 ) {
				$prev_page = $current_page - 1;
				if ( $is_ajax ) {
					$data_page = esc_attr( absint( $prev_page ) );
					$html = '<li class="page-item"><a class="page-link" href="#" data-page="' . $data_page . '">&laquo;</a></li>';
					echo wp_kses_post( $html );
				} else {
					$prev_link = esc_url( get_pagenum_link( $prev_page ) );
					$html = '<li class="page-item"><a class="page-link" href="' . $prev_link . '">&laquo;</a></li>';
					echo wp_kses_post( $html );
				}
			}

			// Page numbers
			for ( $i = 1; $i <= $max_pages; $i++ ) {
				$active_class = ( $i === $current_page ) ? ' active' : '';
				$active_class_escaped = esc_attr( $active_class );
				$page_num_escaped = esc_html( $i );
				if ( $is_ajax ) {
					$data_page = esc_attr( absint( $i ) );
					$html = '<li class="page-item' . $active_class_escaped . '"><a class="page-link" href="#" data-page="' . $data_page . '">' . $page_num_escaped . '</a></li>';
					echo wp_kses_post( $html );
				} else {
					$page_link = esc_url( get_pagenum_link( $i ) );
					$html = '<li class="page-item' . $active_class_escaped . '"><a class="page-link" href="' . $page_link . '">' . $page_num_escaped . '</a></li>';
					echo wp_kses_post( $html );
				}
			}

			// Next button
			if ( $current_page < $max_pages ) {
				$next_page = $current_page + 1;
				if ( $is_ajax ) {
					$data_page = esc_attr( absint( $next_page ) );
					$html = '<li class="page-item"><a class="page-link" href="#" data-page="' . $data_page . '">&raquo;</a></li>';
					echo wp_kses_post( $html );
				} else {
					$next_link = esc_url( get_pagenum_link( $next_page ) );
					$html = '<li class="page-item"><a class="page-link" href="' . $next_link . '">&raquo;</a></li>';
					echo wp_kses_post( $html );
				}
			}

			echo '</ul>';
			echo '</div>';
			echo '<div style="clear:both"></div>';
		}
	


	/**
	 * AJAX handler for loading products
	 */
	public function ajax_load_products() {
		// Verify nonce
		check_ajax_referer( 'shortcodeglut_woo_category_nonce', 'nonce' );

		// Get parameters from AJAX request
			// Get parameters from AJAX request
			$atts = array(
				'categories' => isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : '',
				'cat_field' => isset( $_POST['cat_field'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_field'] ) ) : 'slug',
				'operator' => isset( $_POST['operator'] ) ? sanitize_text_field( wp_unslash( $_POST['operator'] ) ) : 'IN',
				'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( $_POST['items_per_page'] ) : 10,
				'orderby' => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date',
				'order' => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
                'template' => isset( $_POST['template'] ) && $_POST['template'] !== '' ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'product_card_basic',				'paging' => isset( $_POST['paging'] ) ? absint( $_POST['paging'] ) : 1,
				'cols' => isset( $_POST['cols'] ) ? absint( $_POST['cols'] ) : 1,
				'colspad' => isset( $_POST['colspad'] ) ? absint( $_POST['colspad'] ) : 1,
				'colsphone' => isset( $_POST['colsphone'] ) ? absint( $_POST['colsphone'] ) : 1,
				'ajax' => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
				'ajax_pagination' => isset( $_POST['ajax_pagination'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax_pagination'] ) ) : 'off',
			);

			$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

			// Get categories
			$category_slugs = array_map( 'trim', explode( ',', $atts['categories'] ) );
			$categories = $this->get_categories( $category_slugs, $atts['cat_field'] );

			if ( empty( $categories ) ) {
				wp_send_json_error( array( 'message' => __( 'No categories found.', 'shortcodeglut' ) ) );
			}

			// Set the global ajax enabled flags so pagination gets the async class
			global $shortcodeglut_woo_category_ajax_enabled;
			global $shortcodeglut_woo_category_ajax_pagination_enabled;
			$shortcodeglut_woo_category_ajax_enabled = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
			$shortcodeglut_woo_category_ajax_pagination_enabled = ( $atts['ajax_pagination'] === 'on' || $atts['ajax_pagination'] === '1' || $atts['ajax_pagination'] === 'true' );


			// Render products
			ob_start();
			$search_keyword = isset( $_POST['skw'] ) ? sanitize_text_field( wp_unslash( $_POST['skw'] ) ) : '';
			$orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : $atts['orderby'];
			$order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : $atts['order'];
			$this->render_products( $atts, $categories, $paged, $search_keyword, $orderby, $order ); 
			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );
		}
	}
