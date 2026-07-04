<?php
/**
 * WooCommerce Drawer Panels Shortcode Handler
 *
 * Handles [shortcodeglut_drawer] shortcode to display products
 * in an interactive drawer/panel layout
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Drawer;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;
use Shortcodeglut\wooTemplates\WooTemplatesEntity;
use Shortcodeglut\wooTemplates\ConditionalTagProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DrawerShortcode extends ShortcodeBase {

	private static $instance = null;

	protected $shortcode_slug = 'shortcodeglut_drawer';
	protected $shortcode_name = 'WooCommerce Drawer Panels';

	public function __construct() {
		parent::__construct();

		add_action( 'wp_ajax_shortcodeglut_drawer_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_drawer_load', array( $this, 'ajax_load_products' ) );
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
			'shortcodeglut-drawer',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/drawer/assets/css/drawer.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-drawer',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/drawer/assets/js/drawer.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		wp_localize_script( 'shortcodeglut-drawer', 'shortcodeglutDrawer', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'shortcodeglut_drawer_nonce' ),
		) );

		wp_register_script(
			'shortcodeglut-drawer-add-to-cart',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/drawer/assets/js/drawer-add-to-cart.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);
	}

	protected function get_default_atts() {
		return array(
			'nav_title'       => 'Select Product',
			'title'           => '',
			'category'        => '',
			'exclude'         => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Default value, not a query param.
			'items_per_page'  => 10,
			'order_by'        => 'date',
			'order'           => 'DESC',
			'show_price'      => 'true',
			'show_desc'       => 'true',
			'show_features'   => 'true',
			'show_breadcrumb' => '0',
			'accent_color'    => '#0071e3',
			'show_tag'        => 'true',
			'template'        => '', // WooTemplate ID from WooTemplates
		);
	}

	private function sanitize_atts( $atts ) {
		$atts['nav_title']       = sanitize_text_field( $atts['nav_title'] );
		$atts['title']           = sanitize_text_field( $atts['title'] );
		$atts['category']        = sanitize_text_field( $atts['category'] );
		$atts['exclude']         = sanitize_text_field( $atts['exclude'] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitizing string value.
		$atts['items_per_page']  = absint( $atts['items_per_page'] );
		$atts['order_by']        = sanitize_text_field( $atts['order_by'] );
		$atts['order']           = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['show_price']      = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_desc']       = filter_var( $atts['show_desc'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_features']   = filter_var( $atts['show_features'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
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
			return '<div class="shortcodeglut-drawer-placeholder">[ShortcodeGlut Drawer]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
		}

		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
		$atts = $this->sanitize_atts( $atts );

		$unique_id = 'shortcodeglut_drawer_' . $this->shortcode_counter;

		wp_enqueue_style( 'shortcodeglut-drawer' );
		wp_enqueue_script( 'shortcodeglut-drawer' );
		wp_enqueue_script( 'shortcodeglut-drawer-add-to-cart' );

		ob_start();
		$this->render_output( $unique_id, $atts );
		return ob_get_clean();
	}

	private function render_output( $unique_id, $atts ) {
		echo '<div class="shortcodeglut-archive-wrapper shortcodeglut-drawer-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper"';
		echo ' data-unique-id="' . esc_attr( $unique_id ) . '"';
		echo ' data-accent-color="' . esc_attr( $atts['accent_color'] ) . '">';

		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		if ( ! empty( $atts['title'] ) ) {
			echo '<div class="shortcodeglut-header">';
			echo '<h1 class="shortcodeglut-title">' . esc_html( $atts['title'] ) . '</h1>';
			echo '</div>';
		}

		$this->render_drawer( $unique_id, $atts );

		echo '</div>';
	}

	private function render_breadcrumb() {
		echo '<nav class="shortcodeglut-breadcrumb">';
		echo '<a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Home', 'shortcodeglut' ) . '</a>';
		echo '<span>/</span>';
		echo '<span>' . esc_html__( 'Products', 'shortcodeglut' ) . '</span>';
		echo '</nav>';
	}

	private function get_products( $atts ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['items_per_page'],
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

	private function render_drawer( $unique_id, $atts ) {
		$products = $this->get_products( $atts );

		// Get template
		$template = null;
		if ( ! empty( $atts['template'] ) ) {
			$template = WooTemplatesEntity::get_template_by_template_id( $atts['template'] );
		}

		echo '<div class="shortcodeglut-drawer">';

		// Render navigation
		$this->render_navigation( $unique_id, $products, $atts );

		// Render panels container
		echo '<div class="shortcodeglut-drawer-panels">';

		if ( $products->have_posts() ) {
			$panel_index = 0;
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$is_first = ( $panel_index === 0 );
				$this->render_panel( $product, $panel_index, $atts, $template, $is_first );
				$panel_index++;
			}
		} else {
			$this->render_empty_state();
		}

		echo '</div>'; // End panels
		echo '</div>'; // End drawer

		wp_reset_postdata();
	}

	private function render_navigation( $unique_id, $products, $atts ) {
		echo '<div class="shortcodeglut-drawer-nav">';
		echo '<div class="shortcodeglut-drawer-nav-title">' . esc_html( $atts['nav_title'] ) . '</div>';

		if ( $products->have_posts() ) {
			$nav_index = 0;
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$is_active = ( $nav_index === 0 ) ? 'active' : '';
				$product_id = $product->get_id();
				$permalink = get_permalink( $product_id );

				echo '<div class="shortcodeglut-drawer-nav-item ' . esc_attr( $is_active ) . '" data-panel="' . esc_attr( $nav_index ) . '" data-product-id="' . esc_attr( $product_id ) . '">';

				// Nav icon
				echo '<div class="shortcodeglut-drawer-nav-icon">';
				$image_id = $product->get_image_id();
				if ( $image_id ) {
					echo wp_get_attachment_image( $image_id, array( 40, 40 ), false, array( 'style' => 'width:100%;height:100%;object-fit:cover;border-radius:10px;' ) );
				} else {
					echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
				}
				echo '</div>';

				// Nav text
				echo '<div class="shortcodeglut-drawer-nav-text">';
				echo '<div class="shortcodeglut-drawer-nav-title-text">' . esc_html( $product->get_name() ) . '</div>';

				if ( $atts['show_price'] ) {
					echo '<div class="shortcodeglut-drawer-nav-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
				}
				echo '</div>';

				echo '</div>';
				$nav_index++;
			}
			$products->rewind_posts();
		}

		echo '</div>'; // End nav
	}

	private function render_panel( $product, $index, $atts, $template = null, $is_active = false ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );
		$active_class = $is_active ? 'active' : '';

		echo '<div class="shortcodeglut-drawer-panel ' . esc_attr( $active_class ) . '" id="panel-' . esc_attr( $index ) . '">';

		// If template is provided, use it
		if ( $template ) {
			$this->render_with_template( $product, $template, $atts );
		} else {
			// Use default panel rendering
			$this->render_default_panel( $product, $atts );
		}

		echo '</div>';
	}

	private function render_default_panel( $product, $atts ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );

		echo '<div class="shortcodeglut-panel-header">';
		echo '<div class="shortcodeglut-panel-image">';

		$image_id = $product->get_image_id();
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'shortcodeglut-panel-product-image' ) );
		} else {
			echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		}

		echo '</div>';

		echo '<div class="shortcodeglut-panel-info">';

		if ( $atts['show_tag'] ) {
			$tag = $this->get_product_tag( $product );
			if ( $tag ) {
				$tag_label = $this->get_tag_label( $tag );
				echo '<span class="shortcodeglut-panel-tag ' . esc_attr( $tag ) . '">' . esc_html( $tag_label ) . '</span>';
			}
		}

		echo '<h2 class="shortcodeglut-panel-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a></h2>';

		if ( $atts['show_desc'] ) {
			$desc = $product->get_short_description();
			if ( empty( $desc ) ) {
				$desc = wp_trim_words( get_the_content( null, false, $product_id ), 25, '...' );
			}
			if ( ! empty( $desc ) ) {
				echo '<p class="shortcodeglut-panel-desc">' . wp_kses_post( $desc ) . '</p>';
			}
		}

		if ( $atts['show_price'] ) {
			echo '<div class="shortcodeglut-panel-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$cart_url = wc_get_cart_url();
			echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
					class="shortcodeglut-panel-btn shortcodeglut-add-to-cart-btn ajax_add_to_cart"
					data-product_id="' . esc_attr( $product_id ) . '"
					data-product-url="' . esc_url( $permalink ) . '"
					data-cart-url="' . esc_url( $cart_url ) . '">';
			echo esc_html__( 'Add to Cart', 'shortcodeglut' );
			echo '</a>';
		} else {
			echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-panel-btn">';
			echo esc_html__( 'View Product', 'shortcodeglut' );
			echo '</a>';
		}

		echo '</div>';
		echo '</div>';

		if ( $atts['show_features'] ) {
			$this->render_panel_features( $product );
		}
	}

	private function render_panel_features( $product ) {
		$features = array();

		// Get product categories as features
		$categories = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( array_slice( $categories, 0, 4 ) as $category ) {
				$features[] = array(
					'title' => $category->name,
					/* translators: %s: number of products */
					'desc'  => sprintf( __( '%s products available', 'shortcodeglut' ), $category->count ),
				);
			}
		}

		// Add stock status as feature
		$features[] = array(
			'title' => $product->is_in_stock() ? __( 'In Stock', 'shortcodeglut' ) : __( 'Out of Stock', 'shortcodeglut' ),
			'desc'  => $product->is_in_stock() ? __( 'Ready to ship', 'shortcodeglut' ) : __( 'Currently unavailable', 'shortcodeglut' ),
		);

		// Add SKU as feature if available
		$sku = $product->get_sku();
		if ( $sku ) {
			$features[] = array(
				'title' => __( 'SKU', 'shortcodeglut' ),
				'desc'  => $sku,
			);
		}

		// Fill remaining features if needed
		$feature_icons = array(
			'<svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>',
			'<svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>',
			'<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
			'<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>',
		);

		echo '<div class="shortcodeglut-panel-features">';

		foreach ( array_slice( $features, 0, 4 ) as $i => $feature ) {
			$icon = isset( $feature_icons[ $i ] ) ? $feature_icons[ $i ] : $feature_icons[0];

			echo '<div class="shortcodeglut-panel-feature">';
			echo '<div class="shortcodeglut-panel-feature-icon">' . wp_kses_post( $icon ) . '</div>';
			echo '<div class="shortcodeglut-panel-feature-title">' . esc_html( $feature['title'] ) . '</div>';
			echo '<div class="shortcodeglut-panel-feature-desc">' . esc_html( $feature['desc'] ) . '</div>';
			echo '</div>';
		}

		echo '</div>';
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

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template, $atts = array() ) {
		$is_file_template = isset( $template['template_id'] ) && empty( $template['template_html'] );

		if ( $is_file_template && ! empty( $template['template_id'] ) ) {
			$this->render_file_template( $product, $template['template_id'] );
		} elseif ( ! empty( $template['template_html'] ) ) {
			$html = $template['template_html'];
			$processed_html = $this->process_template_tags( $html, $product );

			$template_instance_id = 'shortcodeglut-template-' . ( isset( $template['id'] ) ? $template['id'] : 'unknown' ) . '-' . uniqid();

			if ( ! empty( $template['template_css'] ) ) {
				echo sprintf(
					'<style id="%s-css">%s</style>',
					esc_attr( $template_instance_id ),
					wp_kses_post( $template['template_css'] )
				);
			}

			echo sprintf(
				'<div id="%s" class="shortcodeglut-template">%s</div>',
				esc_attr( $template_instance_id ),
				wp_kses_post( $processed_html )
			);
		} else {
			$this->render_default_panel( $product, $atts );
		}
	}

	/**
	 * Render file-based PHP template
	 */
	private function render_file_template( $product, $template_id ) {
		$template_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/template.php';
		$css_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $template_id . '/style.css';

		if ( ! file_exists( $template_path ) ) {
			$this->render_default_panel( $product, array() );
			return;
		}

		if ( file_exists( $css_path ) ) {
			$css_url = SHORTCODEGLUT_URL . 'src/wooTemplates/templates/' . $template_id . '/style.css';
			wp_enqueue_style( 'shortcodeglut-template-' . $template_id, $css_url, array(), SHORTCODEGLUT_VERSION );
		} elseif ( strpos( $template_id, '_clone_' ) !== false ) {
			$base_template_id = preg_replace( '/_clone_\d+$/', '', $template_id );
			$base_css_path = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/' . $base_template_id . '/style.css';
			if ( file_exists( $base_css_path ) ) {
				$css_url = SHORTCODEGLUT_URL . 'src/wooTemplates/templates/' . $base_template_id . '/style.css';
				wp_enqueue_style( 'shortcodeglut-template-' . $template_id, $css_url, array(), SHORTCODEGLUT_VERSION );
			}
		}

		wp_enqueue_script( 'shortcodeglut-drawer-add-to-cart' );
		wp_enqueue_style( 'shortcodeglut-drawer' );

		$template_instance_id = 'shortcodeglut-template-' . $template_id . '-' . uniqid();

		ob_start();

		$old_global_product = null;
		if ( isset( $GLOBALS['shortcodeglut_product'] ) ) {
			$old_global_product = $GLOBALS['shortcodeglut_product'];
		}

		$GLOBALS['shortcodeglut_product'] = $product;

		include $template_path;

		if ( $old_global_product !== null ) {
			$GLOBALS['shortcodeglut_product'] = $old_global_product;
		} else {
			unset( $GLOBALS['shortcodeglut_product'] );
		}

		$template_output = ob_get_clean();
		$template_output = $this->process_template_tags( $template_output, $product );

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
		return ConditionalTagProcessor::process_with_image_size( $html, $product, 'large' );
	}

	private function render_empty_state() {
		echo '<div class="shortcodeglut-drawer-empty">';
		echo '<p>' . esc_html__( 'No products found.', 'shortcodeglut' ) . '</p>';
		echo '</div>';
	}

	public function ajax_load_products() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Nonce verification handles security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'shortcodeglut_drawer_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		wp_send_json_success( array( 'message' => 'AJAX loaded' ) );
	}
}
