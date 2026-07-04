<?php
/**
 * Timeline Layout Shortcode Handler
 *
 * Handles [shortcodeglut_timeline] shortcode to display products
 * in a vertical timeline with alternate, left, or right layout options.
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Timeline;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TimelineShortcode extends ShortcodeBase {

	private static $instance = null;

	protected $shortcode_slug = 'shortcodeglut_timeline';
	protected $shortcode_name = 'WooCommerce Timeline Layout';

	public function __construct() {
		parent::__construct();

		add_action( 'wp_ajax_shortcodeglut_timeline_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_timeline_load', array( $this, 'ajax_load_products' ) );
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
			'shortcodeglut-timeline',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/timeline/assets/css/timeline.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-timeline',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/timeline/assets/js/timeline.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		wp_localize_script( 'shortcodeglut-timeline', 'shortcodeglutTimeline', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'shortcodeglut_timeline_nonce' ),
		) );

		wp_register_script(
			'shortcodeglut-timeline-add-to-cart',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/timeline/assets/js/timeline-add-to-cart.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);
	}

	protected function get_default_atts() {
		return array(
			'layout'          => 'alternate',
			'group_by'        => 'month',
			'title'           => '',
			'category'        => '',
			'exclude'         => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Default value, not a query param.
			'items_per_page'  => 10,
			'order_by'        => 'date',
			'order'           => 'DESC',
			'show_price'      => 'true',
			'show_excerpt'    => 'true',
			'show_date'       => 'true',
			'show_meta'       => 'true',
			'show_breadcrumb' => '0',
			'paging'          => '1',
			'ajax'            => 'off',
			'accent_color'    => '#667eea',
		);
	}

	private function sanitize_atts( $atts ) {
		$atts['layout']          = sanitize_text_field( $atts['layout'] );
		$atts['group_by']        = sanitize_text_field( $atts['group_by'] );
		$atts['title']           = sanitize_text_field( $atts['title'] );
		$atts['category']        = sanitize_text_field( $atts['category'] );
		$atts['exclude']         = sanitize_text_field( $atts['exclude'] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitizing string value.
		$atts['items_per_page']  = absint( $atts['items_per_page'] );
		$atts['order_by']        = sanitize_text_field( $atts['order_by'] );
		$atts['order']           = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['show_price']      = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_excerpt']    = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_date']       = filter_var( $atts['show_date'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_meta']       = filter_var( $atts['show_meta'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		$atts['paging']          = filter_var( $atts['paging'], FILTER_VALIDATE_BOOLEAN );
		$atts['ajax']            = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['accent_color']    = sanitize_hex_color( $atts['accent_color'] );

		if ( ! in_array( $atts['layout'], array( 'alternate', 'left', 'right' ), true ) ) {
			$atts['layout'] = 'alternate';
		}

		if ( ! in_array( $atts['order'], array( 'ASC', 'DESC' ), true ) ) {
			$atts['order'] = 'DESC';
		}

		return $atts;
	}

	public function render_shortcode( $atts ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-timeline-placeholder">[ShortcodeGlut Timeline]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
		}

		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
		$atts = $this->sanitize_atts( $atts );

		$unique_id     = 'shortcodeglut_timeline_' . $this->shortcode_counter;
		$ajax_enabled  = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );

		wp_enqueue_style( 'shortcodeglut-timeline' );
		wp_enqueue_script( 'shortcodeglut-timeline' );
		wp_enqueue_script( 'shortcodeglut-timeline-add-to-cart' );

		ob_start();
		$this->render_output( $unique_id, $atts, $ajax_enabled );
		return ob_get_clean();
	}

	private function render_output( $unique_id, $atts, $ajax_enabled ) {
		$content_id = 'content_' . $unique_id;

		$data_atts = array(
			'layout'         => $atts['layout'],
			'group_by'       => $atts['group_by'],
			'category'       => $atts['category'],
			'exclude'        => $atts['exclude'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Data attribute only.
			'items_per_page' => $atts['items_per_page'],
			'order_by'       => $atts['order_by'],
			'order'          => $atts['order'],
			'show_price'     => $atts['show_price'] ? '1' : '0',
			'show_excerpt'   => $atts['show_excerpt'] ? '1' : '0',
			'show_date'      => $atts['show_date'] ? '1' : '0',
			'show_meta'      => $atts['show_meta'] ? '1' : '0',
			'paging'         => $atts['paging'] ? '1' : '0',
			'ajax'           => $atts['ajax'],
			'accent_color'   => $atts['accent_color'],
		);
		$data_json = htmlspecialchars( wp_json_encode( $data_atts ), ENT_QUOTES, 'UTF-8' );

		echo '<div class="shortcodeglut-archive-wrapper shortcodeglut-timeline-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper"';
		echo ' data-shortcode-id="' . esc_attr( $unique_id ) . '"';
		echo ' data-layout="' . esc_attr( $atts['layout'] ) . '"';
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
		$this->render_timeline( $atts, $current_paged );

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

	private function get_date_format( $group_by ) {
		switch ( $group_by ) {
			case 'year':
				return 'Y';
			case 'date':
				return get_option( 'date_format' );
			case 'month':
			default:
				return 'F Y';
		}
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

	private function render_timeline( $atts, $paged = 1 ) {
		$products    = $this->get_products( $atts, $paged );
		$date_format = $this->get_date_format( $atts['group_by'] );
		$accent      = ! empty( $atts['accent_color'] ) ? $atts['accent_color'] : '#667eea';

		$layout_class = 'shortcodeglut-timeline';
		if ( $atts['layout'] !== 'alternate' ) {
			$layout_class .= ' layout-' . esc_attr( $atts['layout'] );
		}

		echo '<div class="' . esc_attr( $layout_class ) . '" style="--sg-timeline-accent: ' . esc_attr( $accent ) . ';">';

		if ( $products->have_posts() ) {
			$item_index = 0;
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$this->render_timeline_item( $product, $item_index, $atts, $date_format );
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

	private function render_timeline_item( $product, $index, $atts, $date_format ) {
		$product_id = $product->get_id();
		$permalink  = get_permalink( $product_id );

		if ( $atts['layout'] === 'alternate' ) {
			$side_class = ( $index % 2 === 0 ) ? 'sg-timeline-left' : 'sg-timeline-right';
		} elseif ( $atts['layout'] === 'left' ) {
			$side_class = 'sg-timeline-left';
		} else {
			$side_class = 'sg-timeline-right';
		}

		echo '<div class="shortcodeglut-timeline-item ' . esc_attr( $side_class ) . '">';
		echo '<div class="shortcodeglut-timeline-dot"></div>';
		echo '<div class="shortcodeglut-timeline-content">';
		echo '<div class="shortcodeglut-timeline-card">';

		if ( $atts['show_date'] ) {
			$post_date      = get_the_date( $date_format, $product_id );
			$dot_colors     = array( '#667eea', '#764ba2', '#f093fb' );
			$gradient_start = $dot_colors[ $index % 3 ];
			$gradient_end   = $dot_colors[ ( $index + 1 ) % 3 ];
			echo '<span class="shortcodeglut-timeline-date" style="background: linear-gradient(135deg, ' . esc_attr( $gradient_start ) . ' 0%, ' . esc_attr( $gradient_end ) . ' 100%);">';
			echo esc_html( $post_date );
			echo '</span>';
		}

		echo '<h3 class="shortcodeglut-timeline-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a></h3>';

		if ( $atts['show_excerpt'] ) {
			$excerpt = $product->get_short_description();
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( get_the_content( null, false, $product_id ), 20, '...' );
			}
			if ( ! empty( $excerpt ) ) {
				echo '<p class="shortcodeglut-timeline-excerpt">' . wp_kses_post( $excerpt ) . '</p>';
			}
		}

		if ( $atts['show_meta'] ) {
			$this->render_product_meta( $product );
		}

		echo '<div class="shortcodeglut-timeline-footer">';

		if ( $atts['show_price'] ) {
			echo '<div class="shortcodeglut-timeline-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$cart_url = wc_get_cart_url();
			echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
					class="shortcodeglut-timeline-btn shortcodeglut-add-to-cart-btn ajax_add_to_cart"
					data-product_id="' . esc_attr( $product_id ) . '"
					data-product-url="' . esc_url( $permalink ) . '"
					data-cart-url="' . esc_url( $cart_url ) . '">';
			echo '<i class="fa-solid fa-cart-shopping"></i> ';
			echo esc_html__( 'Add to Cart', 'shortcodeglut' );
			echo '</a>';
		} else {
			echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-timeline-btn">';
			echo esc_html__( 'View Product', 'shortcodeglut' );
			echo '</a>';
		}

		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	private function render_product_meta( $product ) {
		$meta_parts = array();

		$categories = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$cat_names     = wp_list_pluck( $categories, 'name' );
			$meta_parts[]  = implode( ', ', $cat_names );
		}

		$sku = $product->get_sku();
		if ( $sku ) {
			$meta_parts[] = 'SKU: ' . $sku;
		}

		$meta_parts[] = $product->is_in_stock()
			? esc_html__( 'In Stock', 'shortcodeglut' )
			: esc_html__( 'Out of Stock', 'shortcodeglut' );

		echo '<div class="shortcodeglut-timeline-meta">';
		foreach ( $meta_parts as $part ) {
			echo '<span>' . esc_html( $part ) . '</span>';
		}
		echo '</div>';
	}

	private function render_empty_state() {
		echo '<div class="shortcodeglut-timeline-empty">';
		echo '<p>' . esc_html__( 'No products found.', 'shortcodeglut' ) . '</p>';
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
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'shortcodeglut_timeline_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$paged = isset( $_POST['paged'] ) ? absint( wp_unslash( $_POST['paged'] ) ) : 1;

		$atts = array(
			'layout'         => isset( $_POST['layout'] ) ? sanitize_text_field( wp_unslash( $_POST['layout'] ) ) : 'alternate',
			'group_by'       => isset( $_POST['group_by'] ) ? sanitize_text_field( wp_unslash( $_POST['group_by'] ) ) : 'month',
			'category'       => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'exclude'        => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitizing POST value.
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 10,
			'order_by'       => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'date',
			'order'          => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'show_price'     => isset( $_POST['show_price'] ) ? filter_var( wp_unslash( $_POST['show_price'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_excerpt'   => isset( $_POST['show_excerpt'] ) ? filter_var( wp_unslash( $_POST['show_excerpt'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_date'      => isset( $_POST['show_date'] ) ? filter_var( wp_unslash( $_POST['show_date'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_meta'      => isset( $_POST['show_meta'] ) ? filter_var( wp_unslash( $_POST['show_meta'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'paging'         => isset( $_POST['paging'] ) ? filter_var( wp_unslash( $_POST['paging'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'ajax'           => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
			'accent_color'   => isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '#667eea',
		);

		ob_start();
		$this->render_timeline( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
