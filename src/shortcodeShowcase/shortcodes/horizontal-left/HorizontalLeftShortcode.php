<?php
/**
 * WooCommerce Horizontal Image Left Shortcode Handler
 *
 * Handles [shortcodeglut_horizontal_left] shortcode to display products
 * in a horizontal list layout with image on the left side
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\HorizontalLeft;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;
use Shortcodeglut\wooTemplates\ConditionalTagProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HorizontalLeftShortcode extends ShortcodeBase {

	private static $instance = null;

	protected $shortcode_slug = 'shortcodeglut_horizontal_left';
	protected $shortcode_name = 'WooCommerce Horizontal Image Left';

	public function __construct() {
		parent::__construct();

		add_action( 'wp_ajax_shortcodeglut_horizontal_left_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_horizontal_left_load', array( $this, 'ajax_load_products' ) );
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
			'shortcodeglut-horizontal-left',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/horizontal-left/assets/css/horizontal-left.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-horizontal-left',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/horizontal-left/assets/js/horizontal-left-add-to-cart.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		wp_localize_script( 'shortcodeglut-horizontal-left', 'shortcodeglutHorizontalLeft', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'shortcodeglut_horizontal_left_nonce' ),
		) );
	}

	protected function get_default_atts() {
		return array(
			'title'           => '',
			'category'        => '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Exclude parameter is user-configurable for hiding specific products.
			'exclude'         => '',
			'items_per_page'  => 10,
			'order_by'        => 'date-desc',
			'order'           => 'DESC',
			'paging'          => '1',
			'ajax'            => 'off',
			'show_breadcrumb' => '0',
			'show_rating'     => '1',
			'show_category'   => '1',
			'show_description' => '1',
			'image_width'     => '200',
			'image_height'    => '160',
			'badge_type'      => 'auto',
		);
	}

	private function sanitize_atts( $atts ) {
		$atts['title']           = sanitize_text_field( $atts['title'] );
		$atts['category']        = sanitize_text_field( $atts['category'] );
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Exclude parameter is user-configurable for hiding specific products.
		$atts['exclude']         = sanitize_text_field( $atts['exclude'] );
		$atts['items_per_page']  = absint( $atts['items_per_page'] );
		$atts['order_by']        = sanitize_text_field( $atts['order_by'] );
		$atts['order']           = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['paging']          = filter_var( $atts['paging'], FILTER_VALIDATE_BOOLEAN );
		$atts['ajax']            = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_rating']     = filter_var( $atts['show_rating'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_category']   = filter_var( $atts['show_category'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_description'] = filter_var( $atts['show_description'], FILTER_VALIDATE_BOOLEAN );
		$atts['image_width']     = absint( $atts['image_width'] );
		$atts['image_height']    = absint( $atts['image_height'] );
		$atts['badge_type']      = sanitize_text_field( $atts['badge_type'] );

		if ( ! in_array( $atts['order'], array( 'ASC', 'DESC' ), true ) ) {
			$atts['order'] = 'DESC';
		}

		return $atts;
	}

	public function render_shortcode( $atts ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-horizontal-left-placeholder">[ShortcodeGlut Horizontal Left]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
		}

		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
		$atts = $this->sanitize_atts( $atts );

		$unique_id     = 'shortcodeglut_horizontal_left_' . $this->shortcode_counter;
		$ajax_enabled  = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );

		wp_enqueue_style( 'shortcodeglut-horizontal-left' );
		wp_enqueue_script( 'shortcodeglut-horizontal-left' );

		ob_start();
		$this->render_output( $unique_id, $atts, $ajax_enabled );
		return ob_get_clean();
	}

	private function render_output( $unique_id, $atts, $ajax_enabled ) {
		$content_id = 'content_' . $unique_id;

		$data_atts = array(
			'category'          => $atts['category'],
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Exclude parameter is user-configurable for hiding specific products.
			'exclude'           => $atts['exclude'],
			'items_per_page'    => $atts['items_per_page'],
			'order_by'          => $atts['order_by'],
			'order'             => $atts['order'],
			'paging'            => $atts['paging'] ? '1' : '0',
			'ajax'              => $atts['ajax'],
			'show_rating'       => $atts['show_rating'] ? '1' : '0',
			'show_category'     => $atts['show_category'] ? '1' : '0',
			'show_description'  => $atts['show_description'] ? '1' : '0',
			'image_width'       => $atts['image_width'],
			'image_height'      => $atts['image_height'],
			'badge_type'        => $atts['badge_type'],
		);
		$data_json = htmlspecialchars( wp_json_encode( $data_atts ), ENT_QUOTES, 'UTF-8' );

		echo '<div class="shortcodeglut-horizontal-left-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper"';
		echo ' data-shortcode-id="' . esc_attr( $unique_id ) . '"';
		echo ' data-atts=\'' . esc_attr( $data_json ) . '\'>';

		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		if ( ! empty( $atts['title'] ) ) {
			echo '<div class="shortcodeglut-horizontal-left-header">';
			echo '<h1>' . esc_html( $atts['title'] ) . '</h1>';
			echo '</div>';
		}

		echo '<div id="' . esc_attr( $content_id ) . '" class="shortcodeglut-horizontal-left-content">';

		$current_paged = $ajax_enabled ? 1 : max( 1, get_query_var( 'paged' ) );
		$this->render_horizontal_left_list( $atts, $current_paged );

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
			case 'title-asc':
				$query_args['orderby'] = 'title';
				$query_args['order'] = 'ASC';
				break;
			case 'title-desc':
				$query_args['orderby'] = 'title';
				$query_args['order'] = 'DESC';
				break;
			case 'price-asc':
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_price';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'ASC';
				break;
			case 'price-desc':
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_price';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'DESC';
				break;
			case 'rating-desc':
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for rating sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_wc_average_rating';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'DESC';
				break;
			case 'rating-asc':
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for rating sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_wc_average_rating';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'ASC';
				break;
			case 'date-desc':
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'DESC';
				break;
			case 'date-asc':
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'ASC';
				break;
			default:
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'DESC';
				break;
		}

		if ( ! empty( $atts['category'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering, query is optimized with indexed fields.
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		if ( ! empty( $atts['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Exclude is user-configurable for hiding specific products.
			$query_args['post__not_in'] = $exclude_ids;
		}

		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
		if ( ! empty( $product_visibility_term_ids['exclude-from-catalog'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			);
		}

		return new \WP_Query( $query_args );
	}

	private function get_product_badge( $product ) {
		$badge = '';

		if ( $product->is_on_sale() ) {
			$badge = esc_html__( 'Sale', 'shortcodeglut' );
		} elseif ( $product->is_featured() ) {
			$badge = esc_html__( 'Best Seller', 'shortcodeglut' );
		} else {
			$created_date = get_the_time( 'U', $product->get_id() );
			$days_since   = ( time() - $created_date ) / DAY_IN_SECONDS;

			if ( $days_since <= 30 ) {
				$badge = esc_html__( 'New', 'shortcodeglut' );
			}
		}

		return $badge;
	}

	private function render_horizontal_left_list( $atts, $paged = 1 ) {
		$products = $this->get_products( $atts, $paged );

		echo '<div class="shortcodeglut-horizontal-left">';

		if ( $products->have_posts() ) {
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$this->render_horizontal_left_card( $product, $atts );
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

	private function render_horizontal_left_card( $product, $atts ) {
		$product_id   = $product->get_id();
		$permalink    = get_permalink( $product_id );
		$image_width  = $atts['image_width'];
		$image_height = $atts['image_height'];

		$image_id = $product->get_image_id();
		$image_html = '';

		if ( $image_id ) {
			$image_src = wp_get_attachment_image_url( $image_id, 'medium' );
			if ( $image_src ) {
				$image_html = '<img src="' . esc_url( $image_src ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
			}
		}

		if ( empty( $image_html ) ) {
			$image_html = '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		}

		$badge = '';
		if ( $atts['badge_type'] !== 'none' ) {
			$badge = $this->get_product_badge( $product );
			if ( $badge ) {
				$badge = '<span class="shortcodeglut-horizontal-left-badge">' . esc_html( $badge ) . '</span>';
			}
		}

		$category_html = '';
		if ( $atts['show_category'] ) {
			$categories = get_the_terms( $product_id, 'product_cat' );
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$cat = reset( $categories );
				$category_html = '<div class="shortcodeglut-horizontal-left-category">' . esc_html( $cat->name ) . '</div>';
			}
		}

		$description_html = '';
		if ( $atts['show_description'] ) {
			$description = $product->get_short_description();
			if ( empty( $description ) ) {
				$description = wp_trim_words( $product->get_description(), 15 );
			}
			if ( ! empty( $description ) ) {
				$description_html = '<p class="shortcodeglut-horizontal-left-description">' . wp_kses_post( $description ) . '</p>';
			}
		}

		$rating_html = '';
		if ( $atts['show_rating'] ) {
			$rating = $product->get_average_rating();
			$review_count = $product->get_review_count();
			if ( $rating > 0 ) {
				$stars = '';
				for ( $i = 1; $i <= 5; $i++ ) {
					if ( $i <= floor( $rating ) ) {
						$stars .= '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
					} elseif ( $i <= $rating ) {
						$stars .= '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
					} else {
						$stars .= '<svg viewBox="0 0 24 24" style="fill:#e5e7eb"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
					}
				}
				$rating_html = '<div class="shortcodeglut-horizontal-left-rating">' . $stars;
				if ( $review_count > 0 ) {
					$rating_html .= '<span>' . esc_html( number_format( $rating, 1 ) ) . ' (' . esc_html( $review_count ) . ' reviews)</span>';
				}
				$rating_html .= '</div>';
			}
		}

		$price_html = '<div class="shortcodeglut-horizontal-left-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';

		$add_to_cart_button = '';
		$view_details_button = '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-horizontal-left-btn-secondary">' . esc_html__( 'View Details', 'shortcodeglut' ) . '</a>';

		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$cart_url = wc_get_cart_url();
			$add_to_cart_button = '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
					class="shortcodeglut-horizontal-left-btn-primary ajax_add_to_cart"
					data-product_id="' . esc_attr( $product_id ) . '"
					data-product_url="' . esc_url( $permalink ) . '"
					data-cart_url="' . esc_url( $cart_url ) . '">'
					. esc_html__( 'Add to Cart', 'shortcodeglut' ) . '</a>';
		} else {
			$view_details_button = '<a href="' . esc_url( $permalink ) . '" class="shortcodeglut-horizontal-left-btn-primary">' . esc_html__( 'View Details', 'shortcodeglut' ) . '</a>';
		}

		echo '<div class="shortcodeglut-horizontal-left-card">';
		echo '<div class="shortcodeglut-horizontal-left-card-image" style="width:' . esc_attr( $image_width ) . 'px;height:' . esc_attr( $image_height ) . 'px;">';
		echo '<a href="' . esc_url( $permalink ) . '">' . wp_kses( $image_html, array(
			'img' => array(
				'src' => true,
				'alt' => true,
			),
			'svg' => array(
				'viewBox' => true,
				'style' => true,
				'path' => array(
					'd' => true,
				),
			),
		) ) . '</a>';
		echo wp_kses( $badge, array( 'span' => array( 'class' => true ) ) );
		echo '</div>';
		echo '<div class="shortcodeglut-horizontal-left-card-content">';
		echo wp_kses( $category_html, array( 'div' => array( 'class' => true ) ) );
		echo '<h3 class="shortcodeglut-horizontal-left-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $product->get_name() ) . '</a></h3>';
		echo wp_kses( $description_html, array( 'p' => array( 'class' => true ) ) );
		echo '<div class="shortcodeglut-horizontal-left-meta">';
		echo wp_kses_post( $price_html );
		echo wp_kses( $rating_html, array(
			'div' => array( 'class' => true ),
			'span' => true,
			'svg' => array(
				'viewBox' => true,
				'style' => true,
				'path' => array( 'd' => true ),
			),
		) );
		echo '</div>';
		echo '<div class="shortcodeglut-horizontal-left-actions">';
		echo wp_kses( $add_to_cart_button, array(
			'a' => array(
				'href' => true,
				'class' => true,
				'data-product_id' => true,
				'data-product_url' => true,
				'data-cart_url' => true,
			),
		) );
		echo wp_kses( $view_details_button, array(
			'a' => array(
				'href' => true,
				'class' => true,
			),
		) );
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	private function render_empty_state() {
		echo '<div class="shortcodeglut-horizontal-left-empty">';
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

		$current_page = max( 1, $current_page );

		echo '<div class="shortcodeglut-pagination' . esc_attr( $ajax_class ) . '">';
		echo '<ul class="page-numbers">';

		if ( $current_page > 1 ) {
			$prev_link = $is_ajax ? '#' : get_pagenum_link( $current_page - 1 );
			$data_page = $is_ajax ? ' data-page="' . esc_attr( (string) ( $current_page - 1 ) ) . '"' : '';
			echo '<li><a class="prev page-numbers" href="' . esc_url( $prev_link ) . '"' . wp_kses( $data_page, array( 'data-page' => true ) ) . '>&laquo;</a></li>';
		}

		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';
			$page_link    = $is_ajax ? '#' : get_pagenum_link( $i );
			$data_page    = $is_ajax ? ' data-page="' . esc_attr( (string) $i ) . '"' : '';
			echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $page_link ) . '"' . wp_kses( $data_page, array( 'data-page' => true ) ) . '>' . esc_html( $i ) . '</a></li>';
		}

		if ( $current_page < $max_pages ) {
			$next_link = $is_ajax ? '#' : get_pagenum_link( $current_page + 1 );
			$data_page = $is_ajax ? ' data-page="' . esc_attr( (string) ( $current_page + 1 ) ) . '"' : '';
			echo '<li><a class="next page-numbers" href="' . esc_url( $next_link ) . '"' . wp_kses( $data_page, array( 'data-page' => true ) ) . '>&raquo;</a></li>';
		}

		echo '</ul>';
		echo '</div>';
	}

	public function ajax_load_products() {
		check_ajax_referer( 'shortcodeglut_horizontal_left_nonce', 'nonce' );

		$paged = isset( $_POST['paged'] ) ? absint( wp_unslash( $_POST['paged'] ) ) : 1;

		$atts = array(
			'category'          => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Exclude parameter is user-configurable for hiding specific products.
			'exclude'           => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
			'items_per_page'    => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 10,
			'order_by'          => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'date-desc',
			'order'             => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'paging'            => isset( $_POST['paging'] ) ? filter_var( wp_unslash( $_POST['paging'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'ajax'              => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
			'show_rating'       => isset( $_POST['show_rating'] ) ? filter_var( wp_unslash( $_POST['show_rating'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_category'     => isset( $_POST['show_category'] ) ? filter_var( wp_unslash( $_POST['show_category'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_description'  => isset( $_POST['show_description'] ) ? filter_var( wp_unslash( $_POST['show_description'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'image_width'       => isset( $_POST['image_width'] ) ? absint( wp_unslash( $_POST['image_width'] ) ) : 200,
			'image_height'      => isset( $_POST['image_height'] ) ? absint( wp_unslash( $_POST['image_height'] ) ) : 160,
			'badge_type'        => isset( $_POST['badge_type'] ) ? sanitize_text_field( wp_unslash( $_POST['badge_type'] ) ) : 'auto',
		);

		ob_start();
		$this->render_horizontal_left_list( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
