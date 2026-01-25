<?php
/**
 * WooCommerce Sale Products Shortcode Handler
 *
 * Handles [shopglut_sale_products] shortcode to display products on sale
 * with customizable layout, filtering, and pagination
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\SaleProducts;

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SaleProductsShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shopglut_sale_products', array( $this, 'render_sale_products_shortcode' ) );

		// Register AJAX handlers for async pagination
		add_action( 'wp_ajax_shopglut_sale_products_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shopglut_sale_products_load', array( $this, 'ajax_load_products' ) );
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
	public function render_sale_products_shortcode( $atts ) {
		// Skip rendering during REST API requests (block editor validation)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shopglut-sale-products-placeholder">[ShopGlut Sale Products]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shopglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shopglut_sale_products_' . $this->shortcode_counter;

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			'limit' => 12,                       // Number of products to show
			'orderby' => 'date',                 // Order field: date, title, price, popularity, rating
			'order' => 'DESC',                   // Order direction: ASC or DESC
			'category' => '',                    // Filter by category slug
			'exclude' => '',                     // Exclude product IDs (comma-separated)
			'template' => '',                    // Template ID from WooTemplates
			'columns' => 4,                      // Number of columns (1-6)
			'rows' => 1,                         // Number of rows
			'paging' => 0,                       // Enable pagination: 1 or 0
			'items_per_page' => 12,              // Items per page when paging is enabled
			'async' => 0,                        // Load pagination asynchronously
			'show_image' => 1,                   // Show product image
			'show_title' => 1,                   // Show product title
			'show_price' => 1,                   // Show product price
			'show_button' => 1,                  // Show add to cart button
			'show_rating' => 0,                  // Show product rating
			'show_badge' => 1,                   // Show sale badge
		), $atts, 'shopglut_sale_products' );

		// Sanitize attributes
		$atts['limit'] = absint( $atts['limit'] );
		$atts['orderby'] = sanitize_text_field( $atts['orderby'] );
		$atts['order'] = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['category'] = sanitize_text_field( $atts['category'] );
		$atts['exclude'] = sanitize_text_field( $atts['exclude'] );
		$atts['template'] = sanitize_text_field( $atts['template'] );
		$atts['columns'] = absint( $atts['columns'] );
		$atts['rows'] = absint( $atts['rows'] );
		$atts['paging'] = absint( $atts['paging'] );
		$atts['items_per_page'] = absint( $atts['items_per_page'] );
		$atts['async'] = absint( $atts['async'] );
		$atts['show_image'] = filter_var( $atts['show_image'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_title'] = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_price'] = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_button'] = filter_var( $atts['show_button'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_rating'] = filter_var( $atts['show_rating'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_badge'] = filter_var( $atts['show_badge'], FILTER_VALIDATE_BOOLEAN );

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
		// Enqueue sale products styles
		wp_enqueue_style( 'shopglut-sale-products', SHORTCODEGLUT_PLUGIN_URL . 'src/shortcodeShowcase/shortcodes/sale-products/assets/css/style.css', array(), SHORTCODEGLUT_VERSION );

		if ( $atts['async'] ) {
			wp_enqueue_script( 'shopglut-sale-products', SHORTCODEGLUT_PLUGIN_URL . 'src/shortcodeShowcase/shortcodes/sale-products/assets/js/script.js', array( 'jquery' ), SHORTCODEGLUT_VERSION, true );
			wp_localize_script( 'shopglut-sale-products', 'shopglutSaleProductsAjax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'shopglut_sale_products_nonce' )
			) );
		}
	}

	/**
	 * Render the complete shortcode output
	 */
	private function render_output( $unique_id, $atts ) {
		$content_id = 'shopglut_sale_products_content_' . $unique_id;

		echo '<div class="shopglut-sale-products-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper" data-atts="' . esc_attr( wp_json_encode( $atts ) ) . '">';

		// Products content area
		echo '<div id="' . esc_attr( $content_id ) . '" class="shopglut-sale-products-content">';

		// Load products
		$this->render_products( $atts, 1 );

		echo '</div>'; // End content area

		echo '</div>';
	}

	/**
	 * Render sale products
	 */
	private function render_products( $atts, $paged = 1 ) {
		// Build WP_Query arguments for products on sale
		$query_args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => $atts['paging'] ? $atts['items_per_page'] : $atts['limit'],
			'paged' => $paged,
			'orderby' => $atts['orderby'],
			'order' => $atts['order'],
		);

		// Get product IDs on sale
		$product_ids_on_sale = array_filter( array_map( 'absint', wc_get_product_ids_on_sale() ) );

		if ( empty( $product_ids_on_sale ) ) {
			echo '<p class="woocommerce-info">' . esc_html__( 'No products on sale found', 'shortcodeglut' ) . '</p>';
			return;
		}

		$query_args['post__in'] = $product_ids_on_sale;

		// Filter by category if specified
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
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
			$query_args['post__not_in'] = $exclude_ids;
		}

		// Handle special orderby cases
		if ( $atts['orderby'] === 'price' ) {
			$query_args['meta_key'] = '_price';
			$query_args['orderby'] = 'meta_value_num';
		} elseif ( $atts['orderby'] === 'popularity' ) {
			$query_args['meta_key'] = 'total_sales';
			$query_args['orderby'] = 'meta_value_num';
		} elseif ( $atts['orderby'] === 'rating' ) {
			$query_args['meta_key'] = '_wc_average_rating';
			$query_args['orderby'] = 'meta_value_num';
		}

		// Query products
		$products_query = new \WP_Query( $query_args );

		// Get template
		$template = null;
		if ( ! empty( $atts['template'] ) ) {
			$template = WooTemplatesEntity::get_template_by_template_id( $atts['template'] );
		}

		// Calculate column class
		$columns = max( 1, min( 6, $atts['columns'] ) );
		$column_class = 12 / $columns;

		// Render products
		if ( $products_query->have_posts() ) {
			echo '<div class="shopglut-sale-products row">';

			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				echo '<div class="col-lg-' . esc_attr( $column_class ) . ' col-md-6 col-6 shopglut-sale-product-item">';

				if ( $template ) {
					// Use WooTemplate
					$this->render_with_template( $product, $template );
				} else {
					// Use default template
					$this->render_default_product( $product, $atts );
				}

				echo '</div>';
			}

			echo '</div>'; // End row

			// Pagination
			if ( $atts['paging'] ) {
				$this->render_pagination( $products_query, $atts );
			}

		} else {
			echo '<p class="woocommerce-info">' . esc_html__( 'No products on sale found', 'shortcodeglut' ) . '</p>';
		}

		wp_reset_postdata();
	}

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template ) {
		if ( ! is_array( $template ) || empty( $template['template_html'] ) ) {
			$this->render_default_product( $product, array() );
			return;
		}

		$html = isset( $template['template_html'] ) ? $template['template_html'] : '';
		$css = isset( $template['template_css'] ) ? $template['template_css'] : '';

		// Process template tags directly
		$processed_html = $this->process_template_tags( $html, $product );

		// Generate unique ID for this template instance
		$template_instance_id = 'shopglut-template-' . ( isset( $template['id'] ) ? $template['id'] : 'unknown' ) . '-' . uniqid();

		echo sprintf(
			'<style>%s</style><div id="%s" class="shopglut-template">%s</div>',
			esc_html( $css ),
			esc_attr( $template_instance_id ),
			wp_kses_post( $processed_html )
		);
	}

	/**
	 * Process template tags and replace with actual product data
	 */
	private function process_template_tags( $html, $product ) {
		$replacements = array(
			'[product_title]' => $product->get_name(),
			'[product_price]' => $product->get_price_html(),
			'[product_regular_price]' => wc_price( $product->get_regular_price() ),
			'[product_sale_price]' => $product->is_on_sale() ? wc_price( $product->get_sale_price() ) : '',
			'[product_short_description]' => $product->get_short_description(),
			'[product_description]' => $product->get_description(),
			'[product_sku]' => $product->get_sku(),
			'[product_stock]' => $product->is_in_stock() ?
				'<span class="in-stock">' . esc_html__( 'In Stock', 'shortcodeglut' ) . '</span>' :
				'<span class="out-of-stock">' . esc_html__( 'Out of Stock', 'shortcodeglut' ) . '</span>',
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
				$gallery_html .= '<div class="shopglut-product-gallery">';
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
				$categories_html = '<span class="shopglut-product-categories">';
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
				$tags_html = '<span class="shopglut-product-tags">';
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
				'<a href="%s" class="button shopglut-add-to-cart %s" %s>%s</a>',
				esc_url( $product->add_to_cart_url() ),
				$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button ajax_add_to_cart' : '',
				$product->is_purchasable() && $product->is_in_stock() ? 'data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '"' : '',
				esc_html( $product->is_purchasable() && $product->is_in_stock() ? __( 'Add to cart', 'shortcodeglut' ) : __( 'Read more', 'shortcodeglut' ) )
			);
			$replacements['[btn_cart]'] = $cart_button;
		}

		if ( strpos( $html, '[btn_view]' ) !== false ) {
			$view_button = sprintf(
				'<a href="%s" class="button shopglut-view-product">%s</a>',
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
				'<span class="shopglut-rating-count">(' . $rating_count . ')</span>' :
				'';
			$replacements['[product_rating_count]'] = $rating_count_html;
		}

		// Attributes
		if ( strpos( $html, '[product_attributes]' ) !== false ) {
			$attributes = $product->get_attributes();
			$attributes_html = '';

			if ( ! empty( $attributes ) ) {
				$attributes_html = '<div class="shopglut-product-attributes">';

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

						$attributes_html .= '<div class="shopglut-product-attribute">';
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
				'<span class="shopglut-dimensions">' . $dimensions . '</span>' :
				'';
		}

		// Weight
		if ( strpos( $html, '[product_weight]' ) !== false ) {
			$weight = $product->get_weight();
			$replacements['[product_weight]'] = $weight ?
				'<span class="shopglut-weight">' . wc_format_weight( $weight ) . '</span>' :
				'';
		}

		// Replace all tags
		foreach ( $replacements as $tag => $replacement ) {
			$html = str_replace( $tag, $replacement, $html );
		}

		return $html;
	}

	/**
	 * Render product using default template
	 */
	private function render_default_product( $product, $atts ) {
		echo '<div class="shopglut-sale-product-card">';

		// Sale badge
		if ( $atts['show_badge'] && $product->is_on_sale() ) {
			$percentage = 0;
			if ( $product->get_regular_price() && $product->get_sale_price() ) {
				$regular_price = (float) $product->get_regular_price();
				$sale_price = (float) $product->get_sale_price();
				$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
			}

			echo '<span class="shopglut-sale-badge">-' . esc_html( $percentage ) . '%</span>';
		}

		// Product image
		if ( $atts['show_image'] ) {
			echo '<div class="shopglut-sale-product-image">';
			echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">';
			echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) );
			echo '</a>';
			echo '</div>';
		}

		echo '<div class="shopglut-sale-product-details">';

		// Product title
		if ( $atts['show_title'] ) {
			echo '<h3 class="shopglut-sale-product-title">';
			echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a>';
			echo '</h3>';
		}

		// Product rating
		if ( $atts['show_rating'] ) {
			echo '<div class="shopglut-sale-product-rating">';
			echo wc_get_rating_html( $product->get_average_rating() );
			echo '</div>';
		}

		// Product price
		if ( $atts['show_price'] ) {
			echo '<div class="shopglut-sale-product-price">';
			echo wp_kses_post( $product->get_price_html() );
			echo '</div>';
		}

		// Add to cart button
		if ( $atts['show_button'] ) {
			echo '<div class="shopglut-sale-product-button">';
			woocommerce_template_loop_add_to_cart();
			echo '</div>';
		}

		echo '</div>'; // End details
		echo '</div>'; // End card
	}

	/**
	 * Render pagination
	 */
	private function render_pagination( $query, $atts ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		$current_page = max( 1, get_query_var( 'paged' ) );
		$max_pages = $query->max_num_pages;
		$async_class = $atts['async'] ? ' async-pagination' : '';

		echo '<div class="shopglut-sale-products-pagination' . esc_attr( $async_class ) . '">';
		echo '<ul class="page-numbers">';

		// Previous button
		if ( $current_page > 1 ) {
			$prev_link = $atts['async'] ? '#' : get_pagenum_link( $current_page - 1 );
			$data_attr = $atts['async'] ? ' data-page="' . ( $current_page - 1 ) . '"' : '';
			echo '<li><a class="prev page-numbers" href="' . esc_url( $prev_link ) . '"' . $data_attr . '>&laquo;</a></li>';
		}

		// Page numbers
		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';
			$page_link = $atts['async'] ? '#' : get_pagenum_link( $i );
			$data_attr = $atts['async'] ? ' data-page="' . $i . '"' : '';
			echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $page_link ) . '"' . $data_attr . '>' . esc_html( $i ) . '</a></li>';
		}

		// Next button
		if ( $current_page < $max_pages ) {
			$next_link = $atts['async'] ? '#' : get_pagenum_link( $current_page + 1 );
			$data_attr = $atts['async'] ? ' data-page="' . ( $current_page + 1 ) . '"' : '';
			echo '<li><a class="next page-numbers" href="' . esc_url( $next_link ) . '"' . $data_attr . '>&raquo;</a></li>';
		}

		echo '</ul>';
		echo '</div>';
	}

	/**
	 * AJAX handler for loading products
	 */
	public function ajax_load_products() {
		check_ajax_referer( 'shopglut_sale_products_nonce', 'nonce' );

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		// Get attributes from AJAX request
		$atts = array(
			'limit' => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 12,
			'orderby' => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date',
			'order' => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'category' => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'exclude' => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
			'template' => isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : '',
			'columns' => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 4,
			'rows' => isset( $_POST['rows'] ) ? absint( $_POST['rows'] ) : 1,
			'paging' => isset( $_POST['paging'] ) ? absint( $_POST['paging'] ) : 0,
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( $_POST['items_per_page'] ) : 12,
			'show_image' => isset( $_POST['show_image'] ) ? filter_var( $_POST['show_image'], FILTER_VALIDATE_BOOLEAN ) : true,
			'show_title' => isset( $_POST['show_title'] ) ? filter_var( $_POST['show_title'], FILTER_VALIDATE_BOOLEAN ) : true,
			'show_price' => isset( $_POST['show_price'] ) ? filter_var( $_POST['show_price'], FILTER_VALIDATE_BOOLEAN ) : true,
			'show_button' => isset( $_POST['show_button'] ) ? filter_var( $_POST['show_button'], FILTER_VALIDATE_BOOLEAN ) : true,
			'show_rating' => isset( $_POST['show_rating'] ) ? filter_var( $_POST['show_rating'], FILTER_VALIDATE_BOOLEAN ) : false,
			'show_badge' => isset( $_POST['show_badge'] ) ? filter_var( $_POST['show_badge'], FILTER_VALIDATE_BOOLEAN ) : true,
		);

		// Render products
		ob_start();
		$this->render_products( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
