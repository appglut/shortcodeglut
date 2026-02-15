<?php
/**
 * WooCommerce Category Shortcode Handler
 *
 * Similar to WPDM's [wpdm_category] shortcode, this handles [shopglut_woo_category] shortcode
 * to display products from one or more categories using WooTemplates for rendering
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\WooCategory;

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCategoryShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shopglut_woo_category', array( $this, 'render_category_shortcode' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_shopglut_woo_category_products', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shopglut_woo_category_products', array( $this, 'ajax_load_products' ) );
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
			return '<div class="shopglut-woo-category-placeholder">[ShopGlut WooCommerce Category Products]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shopglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shopglut_woo_category_' . $this->shortcode_counter;

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			'id' => '',                          // Category slug or slugs (comma-separated)
			'cat_field' => 'slug',               // Field to use for category ID (slug or id)
			'operator' => 'IN',                  // Query operator: IN, NOT IN, AND, EXISTS, NOT EXISTS
			'icon' => '',                        // Custom icon URL
			'icon_width' => 64,                  // Icon width in pixels
			'title' => '',                       // Custom title or "1" for category name
			'desc' => '',                        // Custom description or "1" for category description
			'items_per_page' => 10,              // Products per page
			'orderby' => 'date',                 // Order field: id, title, date, modified, price, total_sales
			'order' => 'DESC',                   // Order direction: ASC or DESC
			'template' => '',                    // Template ID from WooTemplates
			'toolbar' => '1',                    // Show toolbar: 1, 0, or "compact"
			'tbgrid' => '6,2,2,2',              // Toolbar grid allocation (12 column system)
			'paging' => '1',                     // Show pagination: 1 or 0
			'cols' => 1,                         // Number of columns for desktop
			'colspad' => 1,                      // Number of columns for tablet
			'colsphone' => 1,                    // Number of columns for mobile
			'async' => 0,                        // Load pagination links asynchronously
		), $atts, 'shopglut_woo_category' );

		// Validate required parameter
		if ( empty( $atts['id'] ) ) {
			return '<p class="shopglut-error">' . esc_html__( 'Category ID/slug is required for [shopglut_woo_category] shortcode.', 'shortcodeglut' ) . '</p>';
		}

		// Sanitize and convert attributes
		$atts['id'] = sanitize_text_field( $atts['id'] );
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
		$atts['tbgrid'] = sanitize_text_field( $atts['tbgrid'] );
		$atts['paging'] = absint( $atts['paging'] );
		$atts['cols'] = absint( $atts['cols'] );
		$atts['colspad'] = absint( $atts['colspad'] );
		$atts['colsphone'] = absint( $atts['colsphone'] );
		$atts['async'] = absint( $atts['async'] );

		// Get category information
		$category_slugs = array_map( 'trim', explode( ',', $atts['id'] ) );
		$categories = $this->get_categories( $category_slugs, $atts['cat_field'] );

		if ( empty( $categories ) ) {
			return '<p class="shopglut-error">' . esc_html__( 'No categories found.', 'shortcodeglut' ) . '</p>';
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
		$this->render_output( $unique_id, $atts, $category_data, $categories );

		return ob_get_clean();
	}

	/**
	 * Enqueue required CSS and JS
	 */
	private function enqueue_assets( $atts ) {
		// Enqueue Bootstrap-like styles
		wp_enqueue_style( 'shopglut-woo-category-shortcode', SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/css/style.css', array(), SHORTCODEGLUT_VERSION );

		if ( $atts['async'] ) {
			wp_enqueue_script( 'shopglut-woo-category-shortcode', SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/js/script.js', array( 'jquery' ), SHORTCODEGLUT_VERSION, true );
			wp_localize_script( 'shopglut-woo-category-shortcode', 'shopglutWooCategoryAjax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'shopglut_woo_category_nonce' )
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

		// Title
		if ( $atts['title'] === '1' ) {
			$data['title'] = $category->name;
		} elseif ( ! empty( $atts['title'] ) ) {
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
	private function render_output( $unique_id, $atts, $category_data, $categories ) {
		$form_id = 'sc_form_' . $unique_id;
		$content_id = 'content_' . $unique_id;

		echo '<div class="w3eden">';
		echo '<div class="">';

		// Toolbar form
		if ( $atts['toolbar'] !== '0' ) {
			$this->render_toolbar( $form_id, $content_id, $atts, $category_data );
		}

		echo '<div class="spacer mb-3 d-block clearfix"></div>';

		// Products content area
		echo '<div id="' . esc_attr( $content_id ) . '">';

		// Load products
		$this->render_products( $atts, $categories, 1 );

		echo '</div>'; // End content area

		echo '<div style="clear:both"></div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the toolbar with search, sort, and filter options
	 */
	private function render_toolbar( $form_id, $content_id, $atts, $category_data ) {
		// Parse toolbar grid allocation
		$grid = array_map( 'intval', explode( ',', $atts['tbgrid'] ) );
		$grid = array_pad( $grid, 4, 3 ); // Default to 3 if not enough values

		$is_compact = ( $atts['toolbar'] === 'compact' );
		$async_class = $atts['async'] ? '__shopglut_submit_async' : '';

		// Get current values from GET parameters or use defaults
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$current_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $atts['orderby'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$current_order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : $atts['order'];

		echo '<form method="get" class="' . esc_attr( $async_class ) . '" data-container="#' . esc_attr( $content_id ) . '" id="' . esc_attr( $form_id ) . '" style="margin-bottom: 15px">';

		// Category panel header
		if ( ! $is_compact ) {
			echo '<div class="panel panel-default card category-panel shopglut-shortcode-toolbar">';

			// Panel body with category info
			if ( ! empty( $category_data['title'] ) || ! empty( $category_data['desc'] ) ) {
				echo '<div class="panel-body card-body">';
				echo '<div class="media">';

				if ( ! empty( $category_data['icon'] ) ) {
					echo '<div class="mr-3">';
					echo '<img src="' . esc_url( $category_data['icon'] ) . '" alt="" width="' . esc_attr( $atts['icon_width'] ) . '">';
					echo '</div>';
				}

				echo '<div class="media-body">';
				if ( ! empty( $category_data['title'] ) ) {
					echo '<h3 style="margin: 0">' . esc_html( $category_data['title'] ) . '</h3>';
				}
				if ( ! empty( $category_data['desc'] ) ) {
					echo '<div>' . wp_kses_post( $category_data['desc'] ) . '</div>';
				}
				echo '</div>';

				echo '</div>'; // End media
				echo '</div>'; // End panel-body
			}

			// Panel footer with filters
			echo '<div class="panel-footer card-footer">';
		} else {
			echo '<div class="panel panel-default card category-panel shopglut-shortcode-toolbar compact">';
			echo '<div class="panel-body card-body">';
		}

		echo '<div class="row">';

		// Search field
		echo '<div class="col-lg-' . esc_attr( $grid[0] ) . ' col-md-12">';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for search filtering
		echo '<input type="text" name="skw" value="' . esc_attr( isset( $_GET['skw'] ) ? sanitize_text_field( wp_unslash( $_GET['skw'] ) ) : '' ) . '" placeholder="' . esc_attr__( 'Search Keyword...', 'shortcodeglut' ) . '" class="form-control">';
		echo '</div>';

		// Order By dropdown
		echo '<div class="col-lg-' . esc_attr( $grid[1] ) . ' col-md-4">';
		echo '<select name="orderby" class="shopglut-custom-select custom-select">';
		echo '<option value="date" disabled="disabled">' . esc_html__( 'Order By:', 'shortcodeglut' ) . '</option>';
		echo '<option value="date"' . selected( $current_orderby, 'date', false ) . '>' . esc_html__( 'Publish Date', 'shortcodeglut' ) . '</option>';
		echo '<option value="title"' . selected( $current_orderby, 'title', false ) . '>' . esc_html__( 'Title', 'shortcodeglut' ) . '</option>';
		echo '<option value="modified"' . selected( $current_orderby, 'modified', false ) . '>' . esc_html__( 'Update Date', 'shortcodeglut' ) . '</option>';
		echo '<option value="total_sales"' . selected( $current_orderby, 'total_sales', false ) . '>' . esc_html__( 'Sales', 'shortcodeglut' ) . '</option>';
		echo '<option value="menu_order"' . selected( $current_orderby, 'menu_order', false ) . '>' . esc_html__( 'Menu Order', 'shortcodeglut' ) . '</option>';
		echo '<option value="price"' . selected( $current_orderby, 'price', false ) . '>' . esc_html__( 'Price', 'shortcodeglut' ) . '</option>';
		echo '</select>';
		echo '</div>';

		// Order direction dropdown
		echo '<div class="col-lg-' . esc_attr( $grid[2] ) . ' col-md-4">';
		echo '<select name="order" class="shopglut-custom-select custom-select">';
		echo '<option value="desc" disabled="disabled">' . esc_html__( 'Order:', 'shortcodeglut' ) . '</option>';
		echo '<option value="DESC"' . selected( $current_order, 'DESC', false ) . '>' . esc_html__( 'Descending', 'shortcodeglut' ) . '</option>';
		echo '<option value="ASC"' . selected( $current_order, 'ASC', false ) . '>' . esc_html__( 'Ascending', 'shortcodeglut' ) . '</option>';
		echo '</select>';
		echo '</div>';

		// Apply button
		echo '<div class="col-lg-' . esc_attr( $grid[3] ) . ' col-md-4">';
		echo '<button type="submit" class="btn btn-secondary btn-block">' . esc_html__( 'Apply Filter', 'shortcodeglut' ) . '</button>';
		echo '</div>';

		echo '</div>'; // End row

		if ( ! $is_compact ) {
			echo '</div>'; // End panel-footer
		} else {
			echo '</div>'; // End panel-body
		}

		echo '</div>'; // End panel
		echo '</form>';
	}

	/**
	 * Render products based on category and filters
	 */
	private function render_products( $atts, $categories, $paged = 1 ) {
		// Get category IDs
		$category_ids = array();
		$category_slugs = array();
		foreach ( $categories as $category ) {
			$category_ids[] = $category->term_id;
			$category_slugs[] = $category->slug;
		}

		// Check for form submissions (read-only GET parameters for filtering)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for search
		$search_keyword = isset( $_GET['skw'] ) ? sanitize_text_field( wp_unslash( $_GET['skw'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $atts['orderby'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for ordering
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : $atts['order'];

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
		}

		// Query products
		$products_query = new \WP_Query( $query_args );

		// Get template
		$template = null;
		if ( ! empty( $atts['template'] ) ) {
			$template = WooTemplatesEntity::get_template_by_template_id( $atts['template'] );
		}

		// Calculate column classes
		$col_classes = array(
			'col-lg-' . ( 12 / max( 1, $atts['cols'] ) ),
			'col-md-' . ( 12 / max( 1, $atts['colspad'] ) ),
			'col-' . ( 12 / max( 1, $atts['colsphone'] ) ),
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
				$this->render_pagination( $products_query, $atts );
			}

		} else {
			echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</p>';
		}

		wp_reset_postdata();
	}

	/**
	 * Render product using WooTemplate
	 */
	private function render_with_template( $product, $template ) {
		// Validate template data
		if ( ! is_array( $template ) || empty( $template['template_html'] ) ) {
			$this->render_default_product( $product );
			return;
		}

		$html = isset( $template['template_html'] ) ? $template['template_html'] : '';
		$css = isset( $template['template_css'] ) ? wp_strip_all_tags( $template['template_css'] ) : '';

		// Process template tags directly
		$processed_html = $this->process_template_tags( $html, $product );

		// Generate unique ID for this template instance
		$template_instance_id = 'shopglut-template-' . ( isset( $template['id'] ) ? $template['id'] : 'unknown' ) . '-' . uniqid();

		echo sprintf(
			'<style>%s</style><div id="%s" class="shopglut-template">%s</div>',
			esc_html( $css ), // CSS already sanitized with wp_strip_all_tags(), escaped for output
			esc_attr( $template_instance_id ),
			wp_kses_post( $processed_html )
		);
	}

	/**
	 * Process template tags and replace with actual product data
	 */
	private function process_template_tags( $html, $product ) {
		// Product information tags
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
	private function render_default_product( $product ) {
		echo '<!-- ShopGlut Default Product Template -->';
		echo '<div class="shopglut-default-product-card card mb-2">';
		echo '<div class="card-body">';
		echo '<div class="media">';

		// Product image
		echo '<div class="mr-3 shopglut-product-img">';
		echo wp_kses_post( $product->get_image( 'thumbnail' ) );
		echo '</div>';

		// Product info
		echo '<div class="media-body">';
		echo '<h3 class="product-title"><a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a></h3>';
		echo '<div class="text-muted text-small">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		echo '</div>';

		// Add to cart button
		echo '<div class="ml-3">';
		echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" class="btn btn-primary shopglut-add-to-cart">' . esc_html__( 'Add to Cart', 'shortcodeglut' ) . '</a>';
		echo '</div>';

		echo '</div>'; // End media
		echo '</div>'; // End card-body
		echo '</div>'; // End card
	}

	/**
	 * Render pagination
	 */
	private function render_pagination( $query, $atts ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		echo '<div style="clear:both"></div>';
		echo '<div class="text-center">';
		echo '<ul class="pagination shopglut-pagination pagination-centered text-center' . ( $atts['async'] ? ' async' : '' ) . '">';

		$current_page = max( 1, get_query_var( 'paged' ) );
		$max_pages = $query->max_num_pages;

		// Previous button
		if ( $current_page > 1 ) {
			echo '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( $current_page - 1 ) ) . '">&laquo;</a></li>';
		}

		// Page numbers
		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' active' : '';
			echo '<li class="page-item' . esc_attr( $active_class ) . '"><a class="page-link" href="' . esc_url( get_pagenum_link( $i ) ) . '">' . esc_html( $i ) . '</a></li>';
		}

		// Next button
		if ( $current_page < $max_pages ) {
			echo '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( $current_page + 1 ) ) . '">&raquo;</a></li>';
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
		check_ajax_referer( 'shopglut_woo_category_nonce', 'nonce' );

		// Get parameters from AJAX request
		$atts = array(
			'id' => isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '',
			'cat_field' => isset( $_POST['cat_field'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_field'] ) ) : 'slug',
			'operator' => isset( $_POST['operator'] ) ? sanitize_text_field( wp_unslash( $_POST['operator'] ) ) : 'IN',
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( $_POST['items_per_page'] ) : 10,
			'orderby' => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date',
			'order' => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
			'template' => isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : '',
			'paging' => isset( $_POST['paging'] ) ? absint( $_POST['paging'] ) : 1,
			'cols' => isset( $_POST['cols'] ) ? absint( $_POST['cols'] ) : 1,
			'colspad' => isset( $_POST['colspad'] ) ? absint( $_POST['colspad'] ) : 1,
			'colsphone' => isset( $_POST['colsphone'] ) ? absint( $_POST['colsphone'] ) : 1,
		);

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		// Get categories
		$category_slugs = array_map( 'trim', explode( ',', $atts['id'] ) );
		$categories = $this->get_categories( $category_slugs, $atts['cat_field'] );

		if ( empty( $categories ) ) {
			wp_send_json_error( array( 'message' => __( 'No categories found.', 'shortcodeglut' ) ) );
		}

		// Render products
		ob_start();
		$this->render_products( $atts, $categories, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
