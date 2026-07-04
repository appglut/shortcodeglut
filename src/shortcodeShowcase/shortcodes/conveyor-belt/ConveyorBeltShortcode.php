<?php
/**
 * WooCommerce Conveyor Belt Shortcode Handler
 *
 * Handles [shortcodeglut_conveyor_belt] shortcode to display products
 * in an infinite scrolling horizontal carousel
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\ConveyorBelt;

use Shortcodeglut\wooTemplates\WooTemplatesEntity;
use Shortcodeglut\wooTemplates\ConditionalTagProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ConveyorBeltShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shortcodeglut_conveyor_belt', array( $this, 'render_conveyor_shortcode' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_shortcodeglut_conveyor_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_conveyor_load', array( $this, 'ajax_load_products' ) );

		// Register assets for frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register assets (style handle for inline CSS)
	 */
	public function register_assets() {
		// Register conveyor belt styles
		wp_register_style(
			'shortcodeglut-conveyor-belt',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/conveyor-belt/assets/css/conveyor-belt.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		// Register conveyor belt script
		wp_register_script(
			'shortcodeglut-conveyor-belt',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/conveyor-belt/assets/js/conveyor-belt.js',
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
			'shortcodeglut-conveyor-templates',
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
	public function render_conveyor_shortcode( $atts ) {
		// Skip rendering during REST API requests (block editor validation)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-conveyor-placeholder">[Shortcodeglut Conveyor Belt]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shortcodeglut_conveyor_' . $this->shortcode_counter;

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			'limit' => 16,                         // Number of products to show (will be duplicated)
			'columns' => 4,                        // Number of items visible at once
			'speed' => 'continuous',              // Scroll speed: slow, continuous, fast, paused
			'direction' => 'left',                // Scroll direction: left or right
			'card_width' => 300,                  // Card width in pixels
			'card_height' => 400,                 // Card height in pixels
			'gap' => 30,                          // Gap between cards in pixels
			'border_radius' => 20,                // Card border radius
			'template' => 'product_card_basic',  // Template ID from WooTemplates
			'category' => '',                     // Filter by category slug
			'exclude' => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Documentation string only.
			'order_by' => 'date',                 // Order by: date, title, price, popularity, rating
			'order' => 'DESC',                    // Order direction
			'show_title' => '1',                  // Show product title
			'show_desc' => '1',                   // Show product description
			'show_price' => '1',                  // Show product price
			'show_button' => '1',                 // Show add to cart button
			'show_tag' => '0',                    // Show "Hot" tag for first item
			'button_text' => 'Add to Cart',       // Button text
			'card_style' => 'light',               // Card style: dark or light
			'pause_on_hover' => '1',              // Pause animation on hover
		), $atts, 'shortcodeglut_conveyor_belt' );

		// Sanitize attributes
		$atts['limit'] = absint( $atts['limit'] );
		$atts['columns'] = absint( $atts['columns'] );
		$atts['speed'] = sanitize_text_field( $atts['speed'] );
		$atts['direction'] = sanitize_text_field( $atts['direction'] );
		$atts['card_width'] = absint( $atts['card_width'] );
		$atts['card_height'] = absint( $atts['card_height'] );
		$atts['gap'] = absint( $atts['gap'] );
		$atts['border_radius'] = absint( $atts['border_radius'] );
		$atts['template'] = sanitize_text_field( $atts['template'] );
		$atts['category'] = sanitize_text_field( $atts['category'] );
		$atts['exclude'] = sanitize_text_field( $atts['exclude'] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitization only.
		$atts['order_by'] = sanitize_text_field( $atts['order_by'] );
		$atts['order'] = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['show_title'] = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_desc'] = filter_var( $atts['show_desc'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_price'] = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_button'] = filter_var( $atts['show_button'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_tag'] = filter_var( $atts['show_tag'], FILTER_VALIDATE_BOOLEAN );
		$atts['button_text'] = sanitize_text_field( $atts['button_text'] );
		$atts['card_style'] = sanitize_text_field( $atts['card_style'] );
		$atts['pause_on_hover'] = filter_var( $atts['pause_on_hover'], FILTER_VALIDATE_BOOLEAN );

		// Enqueue required assets
		$this->enqueue_assets( $atts );

		// Start output buffering
		ob_start();

		// Render the shortcode output
		$this->render_output( $unique_id, $atts );

		return ob_get_clean();
	}

	/**
	 * Enqueue required CSS and JS
	 */
	private function enqueue_assets( $atts ) {
		// Enqueue conveyor belt styles
		wp_enqueue_style( 'shortcodeglut-conveyor-belt' );

		// Enqueue JavaScript
		wp_enqueue_script( 'shortcodeglut-conveyor-belt' );

		// Localize script with settings
		wp_localize_script( 'shortcodeglut-conveyor-belt', 'shortcodeglutConveyorAjax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shortcodeglut_conveyor_nonce' ),
		) );
	}

	/**
	 * Render the complete shortcode output
	 */
	private function render_output( $unique_id, $atts ) {
		$card_style_class = 'shortcodeglut-conveyor-style-' . esc_attr( $atts['card_style'] );
		$pause_class = $atts['pause_on_hover'] ? 'pause-on-hover' : '';

		echo '<div class="shortcodeglut-conveyor-wrapper ' . esc_attr( $card_style_class ) . ' ' . esc_attr( $pause_class ) . '" id="' . esc_attr( $unique_id ) . '_wrapper">';

		// Render conveyor
		$this->render_conveyor( $unique_id, $atts );

		echo '</div>';
	}

	/**
	 * Render the conveyor belt
	 */
	private function render_conveyor( $unique_id, $atts ) {
		echo '<div class="shortcodeglut-conveyor">';

		// Get products for rendering
		$products = $this->get_products( $atts );

		if ( empty( $products ) ) {
			echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</p>';
			echo '</div>';
			return;
		}

		// Get template
		$template = null;
		$template_id = ! empty( $atts['template'] ) ? $atts['template'] : 'product_card_basic';
		$template = WooTemplatesEntity::get_template_by_template_id( $template_id );

		// Gradients for icons
		$gradients = array(
			'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
			'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
			'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
			'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
			'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
			'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
			'linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%)',
			'linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%)',
		);

		echo '<div class="shortcodeglut-conveyor-track" style="gap: ' . esc_attr( (string) $atts['gap'] ) . 'px; animation-duration: ' . esc_attr( $this->get_animation_duration( $atts['speed'] ) ) . '; animation-direction: ' . esc_attr( $atts['direction'] === 'right' ? 'reverse' : 'normal' ) . ';">';

		// Render items twice for seamless infinite loop
		for ( $loop = 0; $loop < 2; $loop++ ) {
			$item_index = 0;
			foreach ( $products as $product ) {
				$gradient = $gradients[ $item_index % count( $gradients ) ];
				$is_first = ( $loop === 0 && $item_index === 0 );

				if ( $template ) {
					// Use WooTemplate
					$this->render_with_template( $product, $template, $atts, $gradient, $is_first, $item_index );
				} else {
					// Use default template
					$this->render_conveyor_item( $product, $atts, $gradient, $is_first, $item_index );
				}

				$item_index++;
			}
		}

		echo '</div>'; // End track
		echo '</div>'; // End conveyor
	}

	/**
	 * Get animation duration based on speed setting
	 */
	private function get_animation_duration( $speed ) {
		$durations = array(
			'slow' => '60s',
			'continuous' => '40s',
			'fast' => '20s',
			'paused' => '0s',
		);

		return isset( $durations[ $speed ] ) ? $durations[ $speed ] : '40s';
	}

	/**
	 * Get products for the conveyor
	 */
	private function get_products( $atts ) {
		$query_args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => $atts['limit'],
			'orderby' => $atts['order_by'],
			'order' => $atts['order'],
		);

		// Handle different sorting options
		switch ( $atts['order_by'] ) {
			case 'price':
				$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby'] = 'meta_value_num';
				break;
			case 'popularity':
				$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby'] = 'meta_value_num';
				break;
			case 'rating':
				$query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby'] = 'meta_value_num';
				break;
		}

		// Filter by category if specified
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
			$query_args['post__not_in'] = $exclude_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Intentional exclusion of user-specified product IDs.
		}

		// Add tax query to only show visible products
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
		if ( ! empty( $product_visibility_term_ids['exclude-from-catalog'] ) ) {
			if ( ! isset( $query_args['tax_query'] ) ) {
				$query_args['tax_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for WooCommerce product visibility filtering.
			}
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			);
		}

		$products_query = new \WP_Query( $query_args );

		$products = array();
		if ( $products_query->have_posts() ) {
			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( $product ) {
					$products[] = $product;
				}
			}
		}

		wp_reset_postdata();

		return $products;
	}

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template, $atts, $gradient, $is_first, $item_index ) {
		// Check if this is a file-based template (has template_id but no template_html)
		$is_file_template = isset( $template['template_id'] ) && empty( $template['template_html'] );

		if ( $is_file_template && ! empty( $template['template_id'] ) ) {
			// Render file-based PHP template
			$this->render_file_template( $product, $template['template_id'], $atts, $gradient, $is_first, $item_index );
		} elseif ( ! empty( $template['template_html'] ) ) {
			// Render database template with tag replacement
			$html = $template['template_html'];
			$processed_html = $this->process_template_tags( $html, $product );

			// Generate unique ID for this template instance
			$template_instance_id = 'shortcodeglut-template-' . ( isset( $template['id'] ) ? $template['id'] : 'unknown' ) . '-' . uniqid();

			// Get gradient class
			$gradient_class = 'shortcodeglut-conveyor-' . ( ( $item_index % 8 ) + 1 );

			// Build inline styles
			$inline_style = 'width: ' . esc_attr( (string) $atts['card_width'] ) . 'px; height: ' . esc_attr( (string) $atts['card_height'] ) . 'px; border-radius: ' . esc_attr( (string) $atts['border_radius'] ) . 'px;';

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
				'<div id="%s" class="shortcodeglut-conveyor-item %s" style="%s">',
				esc_attr( $template_instance_id ),
				esc_attr( $gradient_class ),
				esc_attr( $inline_style )
			);
			echo wp_kses_post( $processed_html );
			echo '</div>';
		} else {
			// Fallback to default rendering
			$this->render_conveyor_item( $product, $atts, $gradient, $is_first, $item_index );
		}
	}

	/**
	 * Render file-based PHP template
	 */
	private function render_file_template( $product, $template_id, $atts, $gradient, $is_first, $item_index ) {
		$template_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/template.php';
		$css_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/style.css';

		// Check if template file exists
		if ( ! file_exists( $template_path ) ) {
			$this->render_conveyor_item( $product, $atts, $gradient, $is_first, $item_index );
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

		// Get gradient class
		$gradient_class = 'shortcodeglut-conveyor-' . ( ( $item_index % 8 ) + 1 );

		// Build inline styles
		$inline_style = 'width: ' . esc_attr( (string) $atts['card_width'] ) . 'px; height: ' . esc_attr( (string) $atts['card_height'] ) . 'px; border-radius: ' . esc_attr( (string) $atts['border_radius'] ) . 'px;';

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
		$GLOBALS['shortcodeglut_atts'] = $atts;
		$GLOBALS['shortcodeglut_is_first'] = $is_first;
		$GLOBALS['shortcodeglut_item_index'] = $item_index;
		$GLOBALS['shortcodeglut_gradient_class'] = $gradient_class;
		$GLOBALS['shortcodeglut_inline_style'] = $inline_style;
		$GLOBALS['shortcodeglut_template_id'] = $template_instance_id;

		// Include the template file
		include $template_path;

		// Restore the previous global product
		if ( $old_global_product !== null ) {
			$GLOBALS['shortcodeglut_product'] = $old_global_product;
		} else {
			unset( $GLOBALS['shortcodeglut_product'] );
		}
		unset( $GLOBALS['shortcodeglut_gradient'] );
		unset( $GLOBALS['shortcodeglut_atts'] );
		unset( $GLOBALS['shortcodeglut_is_first'] );
		unset( $GLOBALS['shortcodeglut_item_index'] );
		unset( $GLOBALS['shortcodeglut_gradient_class'] );
		unset( $GLOBALS['shortcodeglut_inline_style'] );
		unset( $GLOBALS['shortcodeglut_template_id'] );

		// Get the output and clean the buffer
		$template_output = ob_get_clean();

		// Process template tags and replace with actual product data
		$template_output = $this->process_template_tags( $template_output, $product );

		// Output with wrapper
		echo sprintf(
			'<div id="%s" class="shortcodeglut-conveyor-item %s" style="%s">',
			esc_attr( $template_instance_id ),
			esc_attr( $gradient_class ),
			esc_attr( $inline_style )
		);
		echo wp_kses_post( $template_output );
		echo '</div>';
	}

	/**
	 * Process template tags and replace with actual product data
	 */
	private function process_template_tags( $html, $product ) {
		// Use ConditionalTagProcessor to handle all template tags including conditionals
		return ConditionalTagProcessor::process_with_image_size( $html, $product, 'woocommerce_thumbnail' );
	}

	/**
	 * Render a single conveyor item (default template)
	 */
	private function render_conveyor_item( $product, $atts, $gradient, $is_first, $item_index ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );
		$image_id = $product->get_image_id();

		// Get product excerpt
		$excerpt = '';
		if ( $atts['show_desc'] ) {
			$excerpt = $product->get_short_description();
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( $product->get_description(), 10 );
			}
		}

		// Get gradient class
		$gradient_class = 'shortcodeglut-conveyor-' . ( ( $item_index % 8 ) + 1 );

		echo '<div class="shortcodeglut-conveyor-item ' . esc_attr( $gradient_class ) . '" style="width: ' . esc_attr( (string) $atts['card_width'] ) . 'px; height: ' . esc_attr( (string) $atts['card_height'] ) . 'px; border-radius: ' . esc_attr( (string) $atts['border_radius'] ) . 'px;">';

		// Show tag for first item if enabled
		if ( $is_first && $atts['show_tag'] ) {
			echo '<span class="shortcodeglut-conveyor-tag">' . esc_html__( 'Hot', 'shortcodeglut' ) . '</span>';
		}

		// Icon
		echo '<div class="shortcodeglut-conveyor-icon">';
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, array( 80, 80 ), false, array( 'style' => 'width: 80px; height: 80px; border-radius: 20px;' ) );
		} else {
			echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		}
		echo '</div>';

		// Title
		if ( $atts['show_title'] ) {
			echo '<div class="shortcodeglut-conveyor-title">';
			echo '<a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a>';
			echo '</div>';
		}

		// Description
		if ( $atts['show_desc'] && ! empty( $excerpt ) ) {
			echo '<div class="shortcodeglut-conveyor-desc">' . wp_kses_post( $excerpt ) . '</div>';
		}

		// Price
		if ( $atts['show_price'] ) {
			echo '<div class="shortcodeglut-conveyor-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		// Button
		if ( $atts['show_button'] ) {
			if ( $product->is_purchasable() && $product->is_in_stock() ) {
				echo '<button class="shortcodeglut-conveyor-btn ajax_add_to_cart" data-product_id="' . esc_attr( (string) $product_id ) . '">' . esc_html( $atts['button_text'] ) . '</button>';
			} else {
				echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-conveyor-btn">' . esc_html__( 'View', 'shortcodeglut' ) . '</a>';
			}
		}

		echo '</div>'; // End item
	}

	/**
	 * AJAX handler for loading products
	 */
	public function ajax_load_products() {
		check_ajax_referer( 'shortcodeglut_conveyor_nonce', 'nonce' );

		// Get attributes from AJAX request
		$atts = array(
			'limit' => isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 16,
			'columns' => isset( $_POST['columns'] ) ? absint( wp_unslash( $_POST['columns'] ) ) : 4,
			'speed' => isset( $_POST['speed'] ) ? sanitize_text_field( wp_unslash( $_POST['speed'] ) ) : 'continuous',
			'direction' => isset( $_POST['direction'] ) ? sanitize_text_field( wp_unslash( $_POST['direction'] ) ) : 'left',
			'card_width' => isset( $_POST['card_width'] ) ? absint( wp_unslash( $_POST['card_width'] ) ) : 300,
			'card_height' => isset( $_POST['card_height'] ) ? absint( wp_unslash( $_POST['card_height'] ) ) : 400,
			'gap' => isset( $_POST['gap'] ) ? absint( wp_unslash( $_POST['gap'] ) ) : 30,
			'border_radius' => isset( $_POST['border_radius'] ) ? absint( wp_unslash( $_POST['border_radius'] ) ) : 20,
			'template' => isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'product_card_basic',
			'category' => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'exclude' => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitization only.
			'order_by' => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'date',
			'order' => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'show_title' => isset( $_POST['show_title'] ) ? filter_var( wp_unslash( $_POST['show_title'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_desc' => isset( $_POST['show_desc'] ) ? filter_var( wp_unslash( $_POST['show_desc'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_price' => isset( $_POST['show_price'] ) ? filter_var( wp_unslash( $_POST['show_price'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_button' => isset( $_POST['show_button'] ) ? filter_var( wp_unslash( $_POST['show_button'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_tag' => isset( $_POST['show_tag'] ) ? filter_var( wp_unslash( $_POST['show_tag'] ), FILTER_VALIDATE_BOOLEAN ) : false,
			'button_text' => isset( $_POST['button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['button_text'] ) ) : 'Add to Cart',
			'card_style' => isset( $_POST['card_style'] ) ? sanitize_text_field( wp_unslash( $_POST['card_style'] ) ) : 'dark',
			'pause_on_hover' => isset( $_POST['pause_on_hover'] ) ? filter_var( wp_unslash( $_POST['pause_on_hover'] ), FILTER_VALIDATE_BOOLEAN ) : true,
		);

		// Render products
		ob_start();
		$this->render_conveyor( 'ajax_conveyor', $atts );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
