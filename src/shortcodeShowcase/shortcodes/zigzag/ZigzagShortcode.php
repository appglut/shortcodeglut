<?php
/**
 * WooCommerce Zigzag Layout Shortcode Handler
 *
 * Handles [shortcodeglut_zigzag] shortcode to display products
 * in an alternating zigzag layout
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Zigzag;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;
use Shortcodeglut\wooTemplates\WooTemplatesEntity;
use Shortcodeglut\wooTemplates\ConditionalTagProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZigzagShortcode extends ShortcodeBase {

	private static $instance = null;

	protected $shortcode_slug = 'shortcodeglut_zigzag';
	protected $shortcode_name = 'WooCommerce Zigzag Layout';

	public function __construct() {
		parent::__construct();

		add_action( 'wp_ajax_shortcodeglut_zigzag_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_zigzag_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function enqueue_frontend_assets() {
		wp_register_style(
			'shortcodeglut-zigzag',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/zigzag/assets/css/zigzag.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-zigzag',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/zigzag/assets/js/zigzag.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		wp_localize_script( 'shortcodeglut-zigzag', 'shortcodeglutZigzag', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'shortcodeglut_zigzag_nonce' ),
		) );

		wp_register_script(
			'shortcodeglut-zigzag-add-to-cart',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/zigzag/assets/js/zigzag-add-to-cart.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);
	}

	protected function get_default_atts() {
		return array(
			'alternate'       => 'true',
			'title'           => '',
			'category'        => '',
			'exclude'         => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Default value, not a query param.
			'items_per_page'  => 10,
			'order_by'        => 'date',
			'order'           => 'DESC',
			'show_price'      => 'true',
			'show_excerpt'    => 'true',
			'show_features'   => 'true',
			'show_breadcrumb' => '0',
			'paging'          => '1',
			'ajax'            => 'off',
			'accent_color'    => '#667eea',
			'show_tag'        => 'true',
			'template'        => '', // WooTemplate ID from WooTemplates
		);
	}

	private function sanitize_atts( $atts ) {
		$atts['alternate']       = filter_var( $atts['alternate'], FILTER_VALIDATE_BOOLEAN );
		$atts['title']           = sanitize_text_field( $atts['title'] );
		$atts['category']        = sanitize_text_field( $atts['category'] );
		$atts['exclude']         = sanitize_text_field( $atts['exclude'] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitizing string value.
		$atts['items_per_page']  = absint( $atts['items_per_page'] );
		$atts['order_by']        = sanitize_text_field( $atts['order_by'] );
		$atts['order']           = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['show_price']      = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_excerpt']    = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_features']   = filter_var( $atts['show_features'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		$atts['paging']          = filter_var( $atts['paging'], FILTER_VALIDATE_BOOLEAN );
		$atts['ajax']            = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['accent_color']    = sanitize_hex_color( $atts['accent_color'] );
		$atts['show_tag']        = filter_var( $atts['show_tag'], FILTER_VALIDATE_BOOLEAN );
		$atts['template']        = sanitize_text_field( $atts['template'] );

		if ( ! in_array( $atts['order'], array( 'ASC', 'DESC' ), true ) ) {
			$atts['order'] = 'DESC';
		}

		return $atts;
	}

	public function render_shortcode( $atts ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-zigzag-placeholder">[ShortcodeGlut Zigzag]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
		}

		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
		$atts = $this->sanitize_atts( $atts );

		$unique_id     = 'shortcodeglut_zigzag_' . $this->shortcode_counter;
		$ajax_enabled  = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );

		wp_enqueue_style( 'shortcodeglut-zigzag' );
		wp_enqueue_script( 'shortcodeglut-zigzag' );
		wp_enqueue_script( 'shortcodeglut-zigzag-add-to-cart' );

		ob_start();
		$this->render_output( $unique_id, $atts, $ajax_enabled );
		return ob_get_clean();
	}

	private function render_output( $unique_id, $atts, $ajax_enabled ) {
		$content_id = 'content_' . $unique_id;

		$data_atts = array(
			'alternate'      => $atts['alternate'] ? '1' : '0',
			'category'       => $atts['category'],
			'exclude'        => $atts['exclude'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Data attribute only.
			'items_per_page' => $atts['items_per_page'],
			'order_by'       => $atts['order_by'],
			'order'          => $atts['order'],
			'show_price'     => $atts['show_price'] ? '1' : '0',
			'show_excerpt'   => $atts['show_excerpt'] ? '1' : '0',
			'show_features'  => $atts['show_features'] ? '1' : '0',
			'paging'         => $atts['paging'] ? '1' : '0',
			'ajax'           => $atts['ajax'],
			'accent_color'   => $atts['accent_color'],
			'show_tag'       => $atts['show_tag'] ? '1' : '0',
			'template'       => $atts['template'],
		);
		$data_json = htmlspecialchars( wp_json_encode( $data_atts ), ENT_QUOTES, 'UTF-8' );

		echo '<div class="shortcodeglut-archive-wrapper shortcodeglut-zigzag-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper"';
		echo ' data-shortcode-id="' . esc_attr( $unique_id ) . '"';
		echo ' data-alternate="' . esc_attr( $atts['alternate'] ? 'true' : 'false' ) . '"';
		echo ' data-atts=\'' . esc_attr( $data_json ) . '\'>';

		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		if ( ! empty( $atts['title'] ) ) {
			echo '<div class="shortcodeglut-header">';
			echo '<h1 class="shortcodeglut-title">' . esc_html( $atts['title'] ) . '</h1>';
			echo '</div>';
		}

		echo '<div id="' . esc_attr( $content_id ) . '">';

		$current_paged = $ajax_enabled ? 1 : max( 1, get_query_var( 'paged' ) );
		$this->render_zigzag( $atts, $current_paged );

		echo '</div>';
		echo '</div>';
	}

	private function render_breadcrumb() {
		echo '<nav class="shortcodeglut-breadcrumb">';
		echo '<a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Home', 'shortcodeglut' ) . '</a>';
		echo '<span>/</span>';
		echo '<span>' . esc_html__( 'Products', 'shortcodeglut' ) . '</span>';
		echo '</nav>';
	}

	private function get_products( $atts, $paged = 1 ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['items_per_page'],
			'paged'          => $paged,
		);

		switch ( $atts['order_by'] ) {
			case 'title':
				$query_args['orderby'] = 'title';
				break;
			case 'price':
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['meta_key'] = '_price';
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'date':
			default:
				$query_args['orderby'] = 'date';
				break;
		}
		$query_args['order'] = $atts['order'];

		if ( ! empty( $atts['category'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		if ( ! empty( $atts['exclude'] ) ) {
			$exclude_ids = array_map( 'absint', explode( ',', $atts['exclude'] ) );
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			$query_args['post__not_in'] = $exclude_ids;
		}

		return new \WP_Query( $query_args );
	}

	private function get_gradient_class( $index ) {
		$gradients = array( 'zigzag-1', 'zigzag-2', 'zigzag-3', 'zigzag-4' );
		return $gradients[ $index % count( $gradients ) ];
	}

	private function get_product_tag( $product ) {
		if ( ! $product->is_in_stock() ) {
			return 'out-of-stock';
		}

		if ( $product->is_on_sale() ) {
			return 'sale';
		}

		if ( $product->is_featured() ) {
			return 'featured';
		}

		$featured = get_post_meta( $product->get_id(), '_featured', true );
		if ( 'yes' === $featured ) {
			return 'featured';
		}

		$created_date = get_the_time( 'U', $product->get_id() );
		$days_since   = ( time() - $created_date ) / DAY_IN_SECONDS;

		if ( $days_since <= 30 ) {
			return 'new';
		}

		return '';
	}

	private function get_tag_label( $tag ) {
		$labels = array(
			'new'          => esc_html__( 'New', 'shortcodeglut' ),
			'sale'         => esc_html__( 'Sale', 'shortcodeglut' ),
			'featured'     => esc_html__( 'Featured', 'shortcodeglut' ),
			'out-of-stock' => esc_html__( 'Out of Stock', 'shortcodeglut' ),
		);

		return isset( $labels[ $tag ] ) ? $labels[ $tag ] : '';
	}

	private function render_zigzag( $atts, $paged = 1 ) {
		$products = $this->get_products( $atts, $paged );

		// Get template
		$template = null;
		if ( ! empty( $atts['template'] ) ) {
			$template = WooTemplatesEntity::get_template_by_template_id( $atts['template'] );
		}

		echo '<div class="shortcodeglut-zigzag">';

		if ( $products->have_posts() ) {
			$item_index = 0;
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$this->render_zigzag_item( $product, $item_index, $atts, $template );
				$item_index++;
			}
		} else {
			$this->render_empty_state();
		}

		echo '</div>';

		if ( $atts['paging'] ) {
			$this->render_pagination( $products, $atts, $paged );
		}

		wp_reset_postdata();
	}

	private function render_zigzag_item( $product, $index, $atts, $template = null ) {
		$product_id = $product->get_id();
		$permalink  = get_permalink( $product_id );
		$gradient_class = $this->get_gradient_class( $index );

		echo '<div class="shortcodeglut-zigzag-item ' . esc_attr( $gradient_class ) . '">';

		// If template is provided, use it for the content section
		if ( $template ) {
			$this->render_template_visual( $product, $gradient_class );
			echo '<div class="shortcodeglut-zigzag-content">';
			$this->render_with_template( $product, $template, $atts );
			echo '</div>';
		} else {
			// Use default zigzag rendering
			echo '<div class="shortcodeglut-zigzag-visual">';

			$image_id = $product->get_image_id();
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'shortcodeglut-zigzag-product-image' ) );
			} else {
				echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			}

			echo '</div>';

			echo '<div class="shortcodeglut-zigzag-content">';

			if ( $atts['show_tag'] ) {
				$tag = $this->get_product_tag( $product );
				if ( $tag ) {
					$tag_label = $this->get_tag_label( $tag );
					echo '<span class="shortcodeglut-zigzag-tag ' . esc_attr( $tag ) . '">' . esc_html( $tag_label ) . '</span>';
				}
			}

			echo '<h2 class="shortcodeglut-zigzag-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a></h2>';

			if ( $atts['show_excerpt'] ) {
				$excerpt = $product->get_short_description();
				if ( empty( $excerpt ) ) {
					$excerpt = wp_trim_words( get_the_content( null, false, $product_id ), 20, '...' );
				}
				if ( ! empty( $excerpt ) ) {
					echo '<p class="shortcodeglut-zigzag-desc">' . wp_kses_post( $excerpt ) . '</p>';
				}
			}

			if ( $atts['show_features'] ) {
				$this->render_product_features( $product );
			}

			echo '<div class="shortcodeglut-zigzag-footer">';

			if ( $atts['show_price'] ) {
				echo '<span class="shortcodeglut-zigzag-price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
			}

			if ( $product->is_purchasable() && $product->is_in_stock() ) {
				$cart_url = wc_get_cart_url();
				echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
						class="shortcodeglut-zigzag-btn shortcodeglut-add-to-cart-btn ajax_add_to_cart"
						data-product_id="' . esc_attr( $product_id ) . '"
						data-product-url="' . esc_url( $permalink ) . '"
						data-cart-url="' . esc_url( $cart_url ) . '">';
				echo esc_html__( 'Add to Cart', 'shortcodeglut' );
				echo '</a>';
			} else {
				echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-zigzag-btn">';
				echo esc_html__( 'View Product', 'shortcodeglut' );
				echo '</a>';
			}

			echo '</div>';
			echo '</div>';
		}

		echo '</div>';
	}

	private function render_product_features( $product ) {
		$features = array();

		$categories = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$cat_names = wp_list_pluck( $categories, 'name' );
			if ( ! empty( $cat_names ) ) {
				$features[] = $cat_names[0];
			}
		}

		$sku = $product->get_sku();
		if ( $sku ) {
			$features[] = 'SKU: ' . $sku;
		}

		$stock_status = $product->is_in_stock()
			? esc_html__( 'In Stock', 'shortcodeglut' )
			: esc_html__( 'Out of Stock', 'shortcodeglut' );
		$features[] = $stock_status;

		if ( ! empty( $features ) ) {
			echo '<ul class="shortcodeglut-zigzag-features">';
			foreach ( $features as $feature ) {
				echo '<li>' . esc_html( $feature ) . '</li>';
			}
			echo '</ul>';
		}
	}

	private function render_empty_state() {
		echo '<div class="shortcodeglut-zigzag-empty">';
		echo '<p>' . esc_html__( 'No products found.', 'shortcodeglut' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render visual section when using templates
	 */
	private function render_template_visual( $product, $gradient_class ) {
		echo '<div class="shortcodeglut-zigzag-visual">';

		$image_id = $product->get_image_id();
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'shortcodeglut-zigzag-product-image' ) );
		} else {
			echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		}

		echo '</div>';
	}

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template, $atts = array() ) {
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
			$this->render_default_content( $product, $atts );
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
			$this->render_default_content( $product, array() );
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
		wp_enqueue_script( 'shortcodeglut-zigzag-add-to-cart' );
		wp_enqueue_style( 'shortcodeglut-zigzag' );

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
		$template_output = $this->process_template_tags( $template_output, $product );

		// Output with wrapper
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
		// Use ConditionalTagProcessor to handle all template tags including conditionals
		return ConditionalTagProcessor::process_with_image_size( $html, $product, 'large' );
	}

	/**
	 * Render default product content (fallback when template is not available)
	 */
	private function render_default_content( $product, $atts ) {
		$product_id = $product->get_id();
		$permalink  = get_permalink( $product_id );

		if ( $atts['show_tag'] ) {
			$tag = $this->get_product_tag( $product );
			if ( $tag ) {
				$tag_label = $this->get_tag_label( $tag );
				echo '<span class="shortcodeglut-zigzag-tag ' . esc_attr( $tag ) . '">' . esc_html( $tag_label ) . '</span>';
			}
		}

		echo '<h2 class="shortcodeglut-zigzag-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a></h2>';

		if ( $atts['show_excerpt'] ) {
			$excerpt = $product->get_short_description();
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( get_the_content( null, false, $product_id ), 20, '...' );
			}
			if ( ! empty( $excerpt ) ) {
				echo '<p class="shortcodeglut-zigzag-desc">' . wp_kses_post( $excerpt ) . '</p>';
			}
		}

		if ( $atts['show_features'] ) {
			$this->render_product_features( $product );
		}

		echo '<div class="shortcodeglut-zigzag-footer">';

		if ( $atts['show_price'] ) {
			echo '<span class="shortcodeglut-zigzag-price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
		}

		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$cart_url = wc_get_cart_url();
			echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
					class="shortcodeglut-zigzag-btn shortcodeglut-add-to-cart-btn ajax_add_to_cart"
					data-product_id="' . esc_attr( $product_id ) . '"
					data-product-url="' . esc_url( $permalink ) . '"
					data-cart-url="' . esc_url( $cart_url ) . '">';
			echo esc_html__( 'Add to Cart', 'shortcodeglut' );
			echo '</a>';
		} else {
			echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-zigzag-btn">';
			echo esc_html__( 'View Product', 'shortcodeglut' );
			echo '</a>';
		}

		echo '</div>';
	}

	private function render_pagination( $query, $atts, $current_page = 1 ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		$is_ajax    = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
		$ajax_class = $is_ajax ? ' async-pagination' : '';
		$max_pages  = $query->max_num_pages;

		if ( $is_ajax ) {
			$current_page = max( 1, $current_page );
		} else {
			$current_page = max( 1, get_query_var( 'paged' ) );
		}

		echo '<div class="shortcodeglut-pagination' . esc_attr( $ajax_class ) . '">';
		echo '<ul class="page-numbers">';

		if ( $current_page > 1 ) {
			$prev_link = $is_ajax ? '#' : get_pagenum_link( $current_page - 1 );
			$data_page = $is_ajax ? ' data-page="' . esc_attr( (string) ( $current_page - 1 ) ) . '"' : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_page is pre-escaped.
			echo '<li><a class="prev page-numbers" href="' . esc_url( $prev_link ) . '"' . $data_page . '>&laquo;</a></li>';
		}

		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';
			$page_link    = $is_ajax ? '#' : get_pagenum_link( $i );
			echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $page_link ) . '" data-page="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</a></li>';
		}

		if ( $current_page < $max_pages ) {
			$next_link = $is_ajax ? '#' : get_pagenum_link( $current_page + 1 );
			echo '<li><a class="next page-numbers" href="' . esc_url( $next_link ) . '" data-page="' . esc_attr( $current_page + 1 ) . '">&raquo;</a></li>';
		}

		echo '</ul>';
		echo '</div>';
	}

	public function ajax_load_products() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Nonce verification handles security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'shortcodeglut_zigzag_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$paged = isset( $_POST['paged'] ) ? absint( wp_unslash( $_POST['paged'] ) ) : 1;

		$atts = array(
			'alternate'      => isset( $_POST['alternate'] ) ? filter_var( wp_unslash( $_POST['alternate'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'category'       => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'exclude'        => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitizing POST value.
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 10,
			'order_by'       => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'date',
			'order'          => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'show_price'     => isset( $_POST['show_price'] ) ? filter_var( wp_unslash( $_POST['show_price'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_excerpt'   => isset( $_POST['show_excerpt'] ) ? filter_var( wp_unslash( $_POST['show_excerpt'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_features'  => isset( $_POST['show_features'] ) ? filter_var( wp_unslash( $_POST['show_features'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'paging'         => isset( $_POST['paging'] ) ? filter_var( wp_unslash( $_POST['paging'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'ajax'           => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
			'accent_color'   => isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '#667eea',
			'show_tag'       => isset( $_POST['show_tag'] ) ? filter_var( wp_unslash( $_POST['show_tag'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'template'       => isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : '',
		);

		ob_start();
		$this->render_zigzag( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
