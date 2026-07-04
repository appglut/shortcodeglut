<?php
/**
 * WooCommerce Accordion List Shortcode Handler
 *
 * Handles [shortcodeglut_accordion] shortcode to display products
 * in an expandable accordion layout
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Accordion;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AccordionShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		add_shortcode( 'shortcodeglut_accordion', array( $this, 'render_accordion_shortcode' ) );

		add_action( 'wp_ajax_shortcodeglut_accordion_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_accordion_load', array( $this, 'ajax_load_products' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render_accordion_shortcode( $atts ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-accordion-placeholder">[Shortcodeglut Accordion]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		$this->shortcode_counter++;
		$unique_id = 'shortcodeglut_accordion_' . $this->shortcode_counter;

		$atts = shortcode_atts( array(
			'expand'          => 'single',
			'show_price'      => 'true',
			'show_excerpt'    => 'true',
			'show_features'   => 'true',
			'show_breadcrumb' => '0',
			'title'           => '',
			'items_per_page'  => 10,
			'paging'          => '1',
			'ajax'            => 'off',
			'order_by'        => 'title',
			'order'           => 'ASC',
			'category'        => '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- string default, not a query param
			'exclude'         => '',
			'icon_width'      => 56,
		), $atts, 'shortcodeglut_accordion' );

		$atts['expand']          = sanitize_text_field( $atts['expand'] );
		$atts['show_price']      = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_excerpt']    = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_features']   = filter_var( $atts['show_features'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		$atts['title']           = sanitize_text_field( $atts['title'] );
		$atts['items_per_page']  = absint( $atts['items_per_page'] );
		$atts['paging']          = absint( $atts['paging'] );
		$atts['ajax']            = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['order_by']        = sanitize_text_field( $atts['order_by'] );
		$atts['order']           = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['category']        = sanitize_text_field( $atts['category'] );
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- string default, not a query param
		$atts['exclude']         = sanitize_text_field( $atts['exclude'] );
		$atts['icon_width']      = absint( $atts['icon_width'] );

		$ajax_enabled = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );

		$this->enqueue_assets( $ajax_enabled );

		ob_start();

		$this->render_output( $unique_id, $atts, $ajax_enabled );

		return ob_get_clean();
	}

	private function enqueue_assets( $ajax_enabled ) {
		wp_enqueue_style( 'shortcodeglut-accordion' );
		wp_enqueue_script( 'shortcodeglut-accordion' );
		wp_enqueue_script( 'shortcodeglut-accordion-add-to-cart' );

		wp_localize_script( 'shortcodeglut-accordion', 'shortcodeglutAccordionAjax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'shortcodeglut_accordion_nonce' ),
		) );
	}

	private function render_output( $unique_id, $atts, $ajax_enabled ) {
		$content_id = 'content_' . $unique_id;

		$atts_for_js = array(
			'expand'          => $atts['expand'],
			'show_price'      => $atts['show_price'] ? '1' : '0',
			'show_excerpt'    => $atts['show_excerpt'] ? '1' : '0',
			'show_features'   => $atts['show_features'] ? '1' : '0',
			'items_per_page'  => $atts['items_per_page'],
			'paging'          => $atts['paging'],
			'ajax'            => $atts['ajax'],
			'order_by'        => $atts['order_by'],
			'order'           => $atts['order'],
			'category'        => $atts['category'],
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- data attribute only
			'exclude'         => $atts['exclude'],
			'icon_width'      => $atts['icon_width'],
		);
		$atts_json = htmlspecialchars( wp_json_encode( $atts_for_js ), ENT_QUOTES, 'UTF-8' );

		echo '<div class="shortcodeglut-archive-wrapper shortcodeglut-accordion-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper"
		        data-shortcode-id="' . esc_attr( $unique_id ) . '"
		        data-expand="' . esc_attr( $atts['expand'] ) . '"
		        data-atts=\'' . esc_attr( $atts_json ) . '\'>';

		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		if ( ! empty( $atts['title'] ) ) {
			echo '<div class="shortcodeglut-header">';
			echo '<h1 class="shortcodeglut-title">' . esc_html( $atts['title'] ) . '</h1>';
			echo '</div>';
		}

		echo '<div id="' . esc_attr( $content_id ) . '" class="shortcodeglut-accordion-content shortcodeglut-accordion-cart">';

		$current_paged = $ajax_enabled ? 1 : max( 1, get_query_var( 'paged' ) );
		$this->render_products( $atts, $current_paged );

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

	private function render_products( $atts, $paged = 1 ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['items_per_page'],
			'paged'          => $paged,
			'orderby'        => $atts['order_by'],
			'order'          => $atts['order'],
		);

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

		if ( $atts['order_by'] === 'price' ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['meta_key'] = '_price';
			$query_args['orderby']  = 'meta_value_num';
		}

		$products_query = new \WP_Query( $query_args );

		$gradients = array(
			'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
			'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
			'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
			'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
			'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
			'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
			'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
			'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
		);

		echo '<div class="shortcodeglut-accordion" data-expand="' . esc_attr( $atts['expand'] ) . '">';

		if ( $products_query->have_posts() ) {
			$item_index = 0;
			$is_first = true;
			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$gradient = $gradients[ $item_index % count( $gradients ) ];
				$active_class = $is_first ? ' active' : '';

				$this->render_accordion_item( $product, $atts, $gradient, $active_class );

				$is_first = false;
				$item_index++;
			}
		} else {
			echo '<div class="shortcodeglut-accordion-item">';
			echo '<div class="shortcodeglut-accordion-header">';
			echo '<div class="shortcodeglut-accordion-info">';
			echo '<div class="shortcodeglut-accordion-title">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';

		if ( $atts['paging'] ) {
			$this->render_pagination( $products_query, $atts, $paged );
		}

		wp_reset_postdata();
	}

	private function render_accordion_item( $product, $atts, $gradient, $active_class ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );

		echo '<div class="shortcodeglut-accordion-item' . esc_attr( $active_class ) . '">';
		echo '<div class="shortcodeglut-accordion-header">';
		echo '<div class="shortcodeglut-accordion-icon" style="background: ' . esc_attr( $gradient ) . '; width: ' . esc_attr( $atts['icon_width'] ) . 'px; height: ' . esc_attr( $atts['icon_width'] ) . 'px;">';

		$image_id = $product->get_image_id();
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, array( $atts['icon_width'], $atts['icon_width'] ), false, array( 'style' => 'border-radius: 14px;' ) );
		} else {
			echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		}

		echo '</div>';

		echo '<div class="shortcodeglut-accordion-info">';
		echo '<div class="shortcodeglut-accordion-title">' . esc_html( $product->get_name() ) . '</div>';
		echo '<div class="shortcodeglut-accordion-meta">' . wp_kses_post( $this->get_product_meta( $product ) ) . '</div>';
		echo '</div>';

		if ( $atts['show_price'] ) {
			echo '<div class="shortcodeglut-accordion-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		echo '<div class="shortcodeglut-accordion-toggle">';
		echo '<svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>';
		echo '</div>';
		echo '</div>';

		echo '<div class="shortcodeglut-accordion-body">';
		echo '<div class="shortcodeglut-accordion-content-inner">';

		if ( $atts['show_excerpt'] ) {
			$excerpt = $product->get_short_description();
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( get_the_content( null, false, $product_id ), 30, '...' );
			}
			if ( ! empty( $excerpt ) ) {
				echo '<p class="shortcodeglut-accordion-excerpt">' . wp_kses_post( $excerpt ) . '</p>';
			}
		}

		if ( $atts['show_features'] ) {
			$this->render_features( $product );
		}

		echo '<div class="shortcodeglut-accordion-actions">';
		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$cart_url = wc_get_cart_url();
			echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
					class="shortcodeglut-btn-primary shortcodeglut-add-to-cart-btn ajax_add_to_cart"
					data-product_id="' . esc_attr( $product_id ) . '"
					data-product-url="' . esc_url( $permalink ) . '"
					data-cart-url="' . esc_url( $cart_url ) . '">';
			echo esc_html__( 'Add to Cart', 'shortcodeglut' );
			echo '</a>';
		}
		echo '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-btn-secondary">' . esc_html__( 'View Product', 'shortcodeglut' ) . '</a>';
		echo '</div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	private function get_product_meta( $product ) {
		$meta_parts = array();

		$categories = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$cat_names = wp_list_pluck( $categories, 'name' );
			$meta_parts[] = implode( ', ', $cat_names );
		}

		$sku = $product->get_sku();
		if ( $sku ) {
			$meta_parts[] = 'SKU: ' . $sku;
		}

		if ( $product->is_in_stock() ) {
			$meta_parts[] = esc_html__( 'In Stock', 'shortcodeglut' );
		} else {
			$meta_parts[] = esc_html__( 'Out of Stock', 'shortcodeglut' );
		}

		return implode( ' &bull; ', $meta_parts );
	}

	private function render_features( $product ) {
		$tags = get_the_terms( $product->get_id(), 'product_tag' );
		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			$attributes = $product->get_attributes();
			if ( empty( $attributes ) ) {
				return;
			}
			$feature_items = array();
			foreach ( $attributes as $attribute ) {
				if ( $attribute->get_visible() ) {
					$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
					if ( ! empty( $values ) ) {
						$feature_items = array_merge( $feature_items, array_slice( $values, 0, 2 ) );
					}
				}
			}
			if ( empty( $feature_items ) ) {
				return;
			}
		} else {
			$feature_items = wp_list_pluck( array_slice( $tags, 0, 4 ), 'name' );
		}

		echo '<div class="shortcodeglut-accordion-features">';
		foreach ( $feature_items as $feature ) {
			echo '<span class="shortcodeglut-accordion-feature">' . esc_html( $feature ) . '</span>';
		}
		echo '</div>';
	}

	private function render_pagination( $query, $atts, $current_page = 1 ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		if ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' ) {
			$current_page = max( 1, $current_page );
		} else {
			$current_page = max( 1, get_query_var( 'paged' ) );
		}
		$max_pages  = $query->max_num_pages;
		$is_ajax    = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
		$ajax_class = $is_ajax ? ' async-pagination' : '';

		echo '<div class="shortcodeglut-pagination' . esc_attr( $ajax_class ) . '">';
		echo '<ul class="page-numbers">';

		if ( $current_page > 1 ) {
			$prev_link = $is_ajax ? '#' : get_pagenum_link( $current_page - 1 );
			$data_page = $is_ajax ? ' data-page="' . esc_attr( (string) $current_page - 1 ) . '"' : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_page is pre-escaped when assigned.
			echo '<li><a class="prev page-numbers" href="' . esc_url( $prev_link ) . '"' . $data_page . '>&laquo;</a></li>';
		}

		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';
			$page_link    = $is_ajax ? '#' : get_pagenum_link( $i );
			echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $page_link ) . '" data-page="' . esc_attr( $i ) . '">' .
			esc_html( $i ) . '</a></li>';
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
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'shortcodeglut_accordion_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		$atts = array(
			'expand'          => isset( $_POST['expand'] ) ? sanitize_text_field( wp_unslash( $_POST['expand'] ) ) : 'single',
			'show_price'      => isset( $_POST['show_price'] ) ? filter_var( wp_unslash( $_POST['show_price'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_excerpt'    => isset( $_POST['show_excerpt'] ) ? filter_var( wp_unslash( $_POST['show_excerpt'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_features'   => isset( $_POST['show_features'] ) ? filter_var( wp_unslash( $_POST['show_features'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'items_per_page'  => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 10,
			'paging'          => isset( $_POST['paging'] ) ? absint( wp_unslash( $_POST['paging'] ) ) : 1,
			'ajax'            => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
			'order_by'        => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'title',
			'order'           => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'ASC',
			'category'        => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			'exclude'         => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
			'icon_width'      => isset( $_POST['icon_width'] ) ? absint( wp_unslash( $_POST['icon_width'] ) ) : 56,
		);

		ob_start();
		$this->render_products( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
