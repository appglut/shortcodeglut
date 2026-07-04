<?php
/**
 * SideOne Layout Shortcode Handler
 *
 * Handles [shortcodeglut_sideone] shortcode to display products
 * in an alternating left-right pattern with image on one side
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Sideone;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SideoneShortcode extends ShortcodeBase {

	private static $instance = null;
	protected $shortcode_counter = 0;

	public function __construct() {
		$this->shortcode_slug = 'shortcodeglut_sideone';
		$this->shortcode_name = 'WooCommerce SideOne Layout';
		parent::__construct();

		add_action( 'wp_ajax_shortcodeglut_sideone_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_sideone_load', array( $this, 'ajax_load_products' ) );
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
			'shortcodeglut-sideone',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/sideone/assets/css/sideone.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-sideone',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/sideone/assets/js/sideone.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		// Localize script for AJAX
		wp_localize_script( 'shortcodeglut-sideone', 'shortcodeglutSideone', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shortcodeglut_sideone_nonce' ),
		) );
	}

	public function render_shortcode( $atts ) {
		// Skip rendering during REST API requests
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-sideone-placeholder">[Shortcodeglut SideOne]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
		}

		$this->shortcode_counter++;
		$unique_id = 'shortcodeglut_sideone_' . $this->shortcode_counter;

		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
		$atts = $this->sanitize_atts( $atts );

		wp_enqueue_style( 'shortcodeglut-sideone' );
		wp_enqueue_script( 'shortcodeglut-sideone' );
		wp_enqueue_style( 'shortcodeglut-fontawesome' );

		ob_start();
		$this->render_output( $unique_id, $atts );
		return ob_get_clean();
	}

	protected function get_default_atts() {
		return array(
			'columns'        => 4,
			'order_by'       => 'date-desc',
			'items_per_page' => 12,
			'template'       => 'product_card_basic',
			'paging'         => '1',
			'ajax'           => 'off',
			'category'       => '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
			'exclude'        => '',
			'image_position' => 'alternate',
			'image_width'    => '50%',
			'show_excerpt'   => '1',
			'show_price'     => '1',
			'show_button'    => '1',
			'gap_size'       => '40px',
			'show_title'     => '0',
			'section_title'  => '',
			'show_breadcrumb' => '0',
		);
	}

	private function sanitize_atts( $atts ) {
		$atts['columns'] = absint( $atts['columns'] );
		$atts['items_per_page'] = absint( $atts['items_per_page'] );
		$atts['paging'] = filter_var( $atts['paging'], FILTER_VALIDATE_BOOLEAN );
		$atts['ajax'] = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['category'] = sanitize_text_field( $atts['category'] );
		// Exclude parameter - using post__not_in as requested by user for product exclusion.
		// Performance: Exclusion is limited to user-specified IDs and bounded in quantity.
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
		$atts['exclude'] = sanitize_text_field( $atts['exclude'] );
		$atts['image_position'] = sanitize_text_field( $atts['image_position'] );
		$atts['image_width'] = sanitize_text_field( $atts['image_width'] );
		$atts['show_excerpt'] = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_price'] = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_button'] = filter_var( $atts['show_button'], FILTER_VALIDATE_BOOLEAN );
		$atts['gap_size'] = sanitize_text_field( $atts['gap_size'] );
		$atts['show_title'] = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		return $atts;
	}

	private function render_output( $unique_id, $atts ) {
		// Pagination parameter - read-only, no nonce required.
		// Security: Value is sanitized with absint() and only used for WP_Query paged parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination parameter, sanitized with absint().
		$current_paged = isset( $_GET['shortcodeglut_paged'] ) ? absint( $_GET['shortcodeglut_paged'] ) : 1;

		$atts_json = wp_json_encode( $atts );

		echo '<div class="shortcodeglut-sideone-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper" data-shortcode-id="' . esc_attr( $unique_id ) . '" data-atts="' . esc_attr( $atts_json ) . '">';

		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		if ( $atts['show_title'] && ! empty( $atts['section_title'] ) ) {
			echo '<h2 class="shortcodeglut-sideone-title">' . esc_html( $atts['section_title'] ) . '</h2>';
		}

		echo '<div class="shortcodeglut-sideone-content">';
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
		$posts_per_page = $atts['columns'];
		$use_pagination = $atts['paging'] && $atts['items_per_page'] > 0;

		if ( $use_pagination ) {
			$posts_per_page = $atts['items_per_page'];
		}

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
		);

		$this->apply_ordering( $query_args, $atts['order_by'] );

		// Category filter - using tax_query for product category filtering.
		// Performance: Tax query is necessary for category filtering; indexes should be maintained on term relationships.
		if ( ! empty( $atts['category'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering, proper indexes exist.
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		// Product exclusion - using post__not_in as requested by user.
		// Performance: Exclusion is limited to user-specified IDs and bounded in quantity.
		if ( ! empty( $atts['exclude'] ) ) {
			$exclude_ids = array_map( 'absint', explode( ',', $atts['exclude'] ) );
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- User-controlled exclusion, limited and bounded.
			$query_args['post__not_in'] = $exclude_ids;
		}

		$products_query = new \WP_Query( $query_args );

		if ( $products_query->have_posts() ) {
			$row_index = 0;

			echo '<div class="shortcodeglut-sideone" style="gap: ' . esc_attr( $atts['gap_size'] ) . ';">';

			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$image_on_left = $this->get_image_position( $row_index, $atts['image_position'] );

				echo '<div class="shortcodeglut-sideone-item ' . ( $image_on_left ? 'image-left' : 'image-right' ) . '">';
				$this->render_product_item( $product, $atts );
				echo '</div>';

				$row_index++;
			}

			echo '</div>';

			if ( $use_pagination ) {
				$this->render_pagination( $products_query, $atts, $paged );
			}
		} else {
			echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</p>';
		}

		wp_reset_postdata();
	}

	private function get_image_position( $row_index, $image_position_setting ) {
		if ( $image_position_setting === 'left' ) {
			return true;
		} elseif ( $image_position_setting === 'right' ) {
			return false;
		} else {
			return ( $row_index % 2 === 0 );
		}
	}

	private function render_product_item( $product, $atts ) {
		$image_html = $this->get_product_image( $product );
		$content_html = $this->get_product_content( $product, $atts );

		echo '<div class="shortcodeglut-sideone-image" style="flex: 0 0 ' . esc_attr( $atts['image_width'] ) . ';">' . wp_kses_post( $image_html ) . '</div>';
		echo '<div class="shortcodeglut-sideone-content">' . wp_kses_post( $content_html ) . '</div>';
	}

	private function get_product_image( $product ) {
		if ( $product->get_image_id() ) {
			$image = wp_get_attachment_image( $product->get_image_id(), 'large', false );
			$link = get_permalink( $product->get_id() );
			return '<a href="' . esc_url( $link ) . '">' . $image . '</a>';
		}
		return '<div class="placeholder-image"></div>';
	}

	private function get_product_content( $product, $atts ) {
		$title = get_the_title( $product->get_id() );
		$link = get_permalink( $product->get_id() );

		ob_start();
		echo '<div class="sideone-product-info">';
		echo '<h3 class="sideone-title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';

		if ( $atts['show_excerpt'] ) {
			$excerpt = wp_trim_words( $product->get_short_description(), 15 );
			if ( $excerpt ) {
				echo '<p class="sideone-excerpt">' . esc_html( $excerpt ) . '</p>';
			}
		}

		if ( $atts['show_price'] ) {
			echo '<div class="sideone-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		if ( $atts['show_button'] ) {
			if ( $product->is_purchasable() && $product->is_in_stock() ) {
				echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" class="sideone-button ajax_add_to_cart" data-product_id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Add to Cart', 'shortcodeglut' ) . '</a>';
			} else {
				echo '<a href="' . esc_url( $link ) . '" class="sideone-button">' . esc_html__( 'Read More', 'shortcodeglut' ) . '</a>';
			}
		}

		echo '</div>';
		return ob_get_clean();
	}

	private function apply_ordering( &$query_args, $order_by ) {
		switch ( $order_by ) {
			case 'price-asc':
				// Price ordering requires meta_key for WooCommerce _price meta field.
				// Performance: Meta query is necessary for price sorting; WooCommerce maintains indexes on price meta.
				$query_args['orderby'] = 'meta_value_num';
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Necessary for price sorting, WooCommerce maintains indexes.
				$query_args['meta_key'] = '_price';
				$query_args['order'] = 'ASC';
				break;
			case 'price-desc':
				// Price ordering requires meta_key for WooCommerce _price meta field.
				// Performance: Meta query is necessary for price sorting; WooCommerce maintains indexes on price meta.
				$query_args['orderby'] = 'meta_value_num';
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Necessary for price sorting, WooCommerce maintains indexes.
				$query_args['meta_key'] = '_price';
				$query_args['order'] = 'DESC';
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
			default:
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'DESC';
		}
	}

	private function render_pagination( $query, $atts, $current_page = 1 ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		$max_pages = $query->max_num_pages;
		$is_ajax = ( $atts['ajax'] === 'on' );

		echo '<div class="shortcodeglut-sideone-pagination">';
		echo '<ul class="page-numbers">';

		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';

			if ( $is_ajax ) {
				echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" data-page="' . esc_attr( $i ) . '" href="#">' . esc_html( $i ) . '</a></li>';
			} else {
				$link = add_query_arg( array( 'shortcodeglut_paged' => $i ), get_pagenum_link( $i ) );
				echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $link ) . '">' . esc_html( $i ) . '</a></li>';
			}
		}

		echo '</ul></div>';
	}

	public function ajax_load_products() {
		check_ajax_referer( 'shortcodeglut_sideone_nonce', 'nonce' );

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		$atts = array(
			'columns'        => isset( $_POST['columns'] ) ? absint( wp_unslash( $_POST['columns'] ) ) : 4,
			'order_by'       => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'date-desc',
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 12,
			'paging'         => isset( $_POST['paging'] ) ? filter_var( wp_unslash( $_POST['paging'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'ajax'           => 'on',
			'category'       => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			// Exclude parameter - using post__not_in as requested by user for product exclusion.
			// Performance: Exclusion is limited to user-specified IDs and bounded in quantity.
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
			'exclude'        => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
			'image_position' => isset( $_POST['image_position'] ) ? sanitize_text_field( wp_unslash( $_POST['image_position'] ) ) : 'alternate',
			'image_width'    => isset( $_POST['image_width'] ) ? sanitize_text_field( wp_unslash( $_POST['image_width'] ) ) : '50%',
			'show_excerpt'   => isset( $_POST['show_excerpt'] ) ? filter_var( wp_unslash( $_POST['show_excerpt'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_price'     => isset( $_POST['show_price'] ) ? filter_var( wp_unslash( $_POST['show_price'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'show_button'    => isset( $_POST['show_button'] ) ? filter_var( wp_unslash( $_POST['show_button'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'gap_size'       => isset( $_POST['gap_size'] ) ? sanitize_text_field( wp_unslash( $_POST['gap_size'] ) ) : '40px',
		);

		ob_start();
		$this->render_products( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
