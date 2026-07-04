<?php
/**
 * WooCommerce Radial Circle Shortcode Handler
 *
 * Handles [shortcodeglut_radial] shortcode to display products
 * in a circular/orbit layout with a center featured product
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\RadialCircle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RadialCircleShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	/**
	 * Product gradients for orbit items
	 */
	private $gradients = array(
		'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',  // Pink-Red
		'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',  // Green-Teal
		'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',  // Pink-Yellow
		'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',  // Blue-Cyan
		'linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%)',  // Blue
		'linear-gradient(135deg, #ff9a56 0%, #ff6a95 100%)',  // Orange-Pink
		'linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%)',  // Purple
		'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)',  // Cyan-Blue
		'linear-gradient(135deg, #f97794 0%, #c64b6e 100%)',  // Rose
		'linear-gradient(135deg, #cd7f32 0%, #8b4513 100%)',  // Bronze
		'linear-gradient(135deg, #b8a9c9 0%, #6d5c7e 100%)',  // Purple
		'linear-gradient(135deg, #8fd3f4 0%, #84fab0 100%)',  // Aqua
	);

	public function __construct() {
		add_shortcode( 'shortcodeglut_radial', array( $this, 'render_radial_shortcode' ) );
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
			'shortcodeglut-radial-circle',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/radial-circle/assets/css/radial-circle.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-radial-circle',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/radial-circle/assets/js/radial-circle.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);

		wp_localize_script( 'shortcodeglut-radial-circle', 'shortcodeglutRadial', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shortcodeglut_radial_nonce' ),
		) );
	}

	public function render_radial_shortcode( $atts ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-radial-placeholder">[Shortcodeglut Radial Circle]</div>';
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
		}

		$atts = shortcode_atts( array(
			'orbit'          => 8,
			'category'       => '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Exclude parameter is user-configurable for hiding specific products.
			'exclude'        => '',
			'order_by'       => 'date-desc',
			'distance'       => 285,
			'show_price'     => '1',
			'show_tags'      => '1',
			'center_product' => '0',
		), $atts, 'shortcodeglut_radial' );

		// Sanitize attributes
		$atts['orbit']          = absint( $atts['orbit'] );
		$atts['category']       = sanitize_text_field( $atts['category'] );
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Exclude parameter is user-configurable for hiding specific products.
		$atts['exclude']        = sanitize_text_field( $atts['exclude'] );
		$atts['order_by']       = sanitize_text_field( $atts['order_by'] );
		$atts['distance']       = absint( $atts['distance'] );
		$atts['show_price']     = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_tags']      = filter_var( $atts['show_tags'], FILTER_VALIDATE_BOOLEAN );
		$atts['center_product'] = absint( $atts['center_product'] );

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shortcodeglut_radial_' . $this->shortcode_counter;

		// Enqueue assets
		wp_enqueue_style( 'shortcodeglut-radial-circle' );
		wp_enqueue_script( 'shortcodeglut-radial-circle' );

		// Get products
		$products = $this->get_products( $atts );

		if ( empty( $products ) ) {
			return '<div class="shortcodeglut-radial-empty">' . esc_html__( 'No products found.', 'shortcodeglut' ) . '</div>';
		}

		// Separate center product from orbit products
		$center_product = null;
		$orbit_products = array();

		if ( $atts['center_product'] > 0 ) {
			// Find specified center product
			foreach ( $products as $product ) {
				if ( $product->get_id() === $atts['center_product'] ) {
					$center_product = $product;
					break;
				}
			}
			// Remaining products go to orbit
			foreach ( $products as $product ) {
				if ( $product->get_id() !== $atts['center_product'] ) {
					$orbit_products[] = $product;
				}
			}
		} else {
			// Auto-select: first featured or first product as center
			foreach ( $products as $product ) {
				if ( $center_product === null && $product->is_featured() ) {
					$center_product = $product;
				} else {
					$orbit_products[] = $product;
				}
			}
			// If no featured product, use first product as center
			if ( $center_product === null && ! empty( $products ) ) {
				$center_product = array_shift( $orbit_products );
			}
		}

		// Limit orbit products
		$orbit_products = array_slice( $orbit_products, 0, $atts['orbit'] );

		ob_start();
		$this->render_output( $unique_id, $atts, $center_product, $orbit_products );
		return ob_get_clean();
	}

	private function get_products( $atts ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['orbit'] + 1, // +1 for potential center product
		);

		// Handle order_by
		switch ( $atts['order_by'] ) {
			case 'date-asc':
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'ASC';
				break;
			case 'date-desc':
				$query_args['orderby'] = 'date';
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
			case 'price-asc':
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_price';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'ASC';
				break;
				case 'price-desc':
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_price';
				$query_args['order'] = 'DESC';
				break;
			case 'rating-desc':
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for rating sorting, WooCommerce indexes this meta key.
				$query_args['meta_key'] = '_wc_average_rating';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'DESC';
				break;
			default:
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'DESC';
				break;
		}

		// Filter by category
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

		// Exclude products
		if ( ! empty( $atts['exclude'] ) ) {
			$exclude_ids = array_map( 'absint', explode( ',', $atts['exclude'] ) );
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Exclude is user-configurable for hiding specific products.
			$query_args['post__not_in'] = $exclude_ids;
		}

		// Only show visible products
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
		if ( ! empty( $product_visibility_term_ids['exclude-from-catalog'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			);
		}

		$query = new \WP_Query( $query_args );
		$products = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( $product ) {
					$products[] = $product;
				}
			}
			wp_reset_postdata();
		}

		return $products;
	}

	private function render_output( $unique_id, $atts, $center_product, $orbit_products ) {
		echo '<div class="shortcodeglut-radial-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper">';
		echo '<div class="shortcodeglut-radial-container">';

		// Center product
		if ( $center_product ) {
			$this->render_center_product( $center_product, $atts );
		}

		// Orbit products
		$total_orbit = count( $orbit_products );
		foreach ( $orbit_products as $index => $product ) {
			$rotation = ( 360 / $total_orbit ) * $index;
			$this->render_orbit_product( $product, $rotation, $atts, $index );
		}

		// Detail panel
		echo '<div class="shortcodeglut-detail-panel">';
		echo '<div class="shortcodeglut-detail-title"></div>';
		echo '<div class="shortcodeglut-detail-desc"></div>';
		echo '<div class="shortcodeglut-detail-price"></div>';
		echo '<button class="shortcodeglut-detail-btn">' . esc_html__( 'Add to Cart', 'shortcodeglut' ) . '</button>';
		echo '</div>';

		echo '</div>'; // End container
		echo '</div>'; // End wrapper
	}

	private function render_center_product( $product, $atts ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );
		$badge = $this->get_product_badge( $product, $atts );

		echo '<div class="shortcodeglut-center-product" data-product-id="' . esc_attr( $product_id ) . '"';
		echo ' data-title="' . esc_attr( $product->get_name() ) . '"';
		echo ' data-desc="' . esc_attr( $this->get_product_description( $product ) ) . '"';
		echo ' data-price="' . esc_attr( $product->get_price_html() ) . '"';
		echo ' data-url="' . esc_url( $permalink ) . '">';

		if ( $badge && $atts['show_tags'] ) {
			echo '<span class="shortcodeglut-center-product-badge">' . esc_html( $badge ) . '</span>';
		}

		echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';

		echo '<div class="shortcodeglut-center-product-title">' . esc_html( $product->get_name() ) . '</div>';

		if ( $atts['show_price'] ) {
			echo '<div class="shortcodeglut-center-product-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		echo '</div>';
	}

	private function render_orbit_product( $product, $rotation, $atts, $index ) {
		$product_id = $product->get_id();
		$permalink = get_permalink( $product_id );
		$badge = $this->get_product_badge( $product, $atts );
		$gradient = $this->gradients[ $index % count( $this->gradients ) ];

		echo '<div class="shortcodeglut-orbit-item" style="--rotation: ' . esc_attr( $rotation ) . 'deg; --distance: ' . esc_attr( $atts['distance'] ) . 'px;">';
		echo '<div class="shortcodeglut-orbit-rotator">';
		echo '<div class="shortcodeglut-orbit-line"></div>';
		echo '<div class="shortcodeglut-orbit-spacer"></div>';
		echo '<div class="shortcodeglut-orbit-inner">';

		echo '<div class="shortcodeglut-orbit-product" style="background: ' . esc_attr( $gradient ) . ';"';
		echo ' data-product-id="' . esc_attr( $product_id ) . '"';
		echo ' data-title="' . esc_attr( $product->get_name() ) . '"';
		echo ' data-desc="' . esc_attr( $this->get_product_description( $product ) ) . '"';
		echo ' data-price="' . esc_attr( $product->get_price_html() ) . '"';
		echo ' data-url="' . esc_url( $permalink ) . '">';

		if ( $badge && $atts['show_tags'] ) {
			echo '<span class="shortcodeglut-orbit-product-tag">' . esc_html( $badge ) . '</span>';
		}

		echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';

		echo '<div class="shortcodeglut-orbit-product-title">' . esc_html( $product->get_name() ) . '</div>';

		if ( $atts['show_price'] ) {
			echo '<div class="shortcodeglut-orbit-product-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		echo '</div>'; // End orbit-product
		echo '</div>'; // End orbit-inner
		echo '</div>'; // End orbit-rotator
		echo '</div>'; // End orbit-item
	}

	private function get_product_badge( $product, $atts ) {
		if ( ! $atts['show_tags'] ) {
			return '';
		}

		if ( $product->is_on_sale() ) {
			return esc_html__( 'Sale', 'shortcodeglut' );
		} elseif ( $product->is_featured() ) {
			return esc_html__( 'Featured', 'shortcodeglut' );
		} else {
			$created_date = get_the_time( 'U', $product->get_id() );
			$days_since = ( time() - $created_date ) / DAY_IN_SECONDS;

			if ( $days_since <= 30 ) {
				return esc_html__( 'New', 'shortcodeglut' );
			}
		}

		return '';
	}

	private function get_product_description( $product ) {
		$description = $product->get_short_description();
		if ( empty( $description ) ) {
			$description = $product->get_description();
		}
		if ( empty( $description ) ) {
			$description = __( 'Premium product for your creative projects.', 'shortcodeglut' );
		}
		return wp_trim_words( $description, 15, '...' );
	}
}
