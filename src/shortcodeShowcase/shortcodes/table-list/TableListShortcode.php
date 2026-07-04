<?php
/**
 * WooCommerce Table List Shortcode Handler
 *
 * Handles [shortcodeglut_archive_table] shortcode to display products
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\TableList;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TableListShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shortcodeglut_archive_table', array( $this, 'render_table_list_shortcode' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_shortcodeglut_table_list_load', array( $this, 'ajax_load_products' ) );
		add_action( 'wp_ajax_nopriv_shortcodeglut_table_list_load', array( $this, 'ajax_load_products' ) );
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
	public function render_table_list_shortcode( $atts ) {
		// Skip rendering during REST API requests (block editor validation)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shortcodeglut-table-list-placeholder">[Shortcodeglut Table List]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shortcodeglut_table_list_' . $this->shortcode_counter;

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			'order_by'       => 'title',                          // Order field: title, date, price, etc.
			'order'          => 'ASC',                            // Order direction: ASC or DESC
			'items_per_page' => 9,                              // Products per page
			'columns'        => 'icon|title|price|date|actions', // Table columns
			'colheads'       => 'Product|Price|Date|Actions',     // Column headers
			'paging'         => '1',                              // Show pagination: 1 or 0
			'ajax'           => 'off',                            // Enable AJAX pagination: on or off
			'category'       => '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- string default, not a query param
			'exclude'        => '',                               // Exclude product IDs
			'show_icon'      => '1',                              // Show product icon/image
			'icon_width'     => 44,                               // Icon width in pixels
			'show_sku'       => '1',                              // Show SKU
			'show_breadcrumb' => '0',                             // Show breadcrumb
			'title'          => '',                               // Section title
		), $atts, 'shortcodeglut_archive_table' );

		// Sanitize attributes
		$atts['order_by']        = sanitize_text_field( $atts['order_by'] );
		$atts['order']           = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['items_per_page']  = absint( $atts['items_per_page'] );
		$atts['columns']         = sanitize_text_field( $atts['columns'] );
		$atts['colheads']        = sanitize_text_field( $atts['colheads'] );
		$atts['paging']          = absint( $atts['paging'] );
		$atts['ajax']            = strtolower( sanitize_text_field( $atts['ajax'] ) );
		$atts['category']        = sanitize_text_field( $atts['category'] );
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- string default, not a query param
		$atts['exclude']         = sanitize_text_field( $atts['exclude'] );
		$atts['show_icon']       = filter_var( $atts['show_icon'], FILTER_VALIDATE_BOOLEAN );
		$atts['icon_width']      = absint( $atts['icon_width'] );
		$atts['show_sku']        = filter_var( $atts['show_sku'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_breadcrumb'] = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
		$atts['title']           = sanitize_text_field( $atts['title'] );

		// Convert ajax to boolean
		$ajax_enabled = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );

		// Enqueue and localize assets
		$this->enqueue_assets( $ajax_enabled );

		// Start output buffering
		ob_start();

		// Render the shortcode output
		$this->render_output( $unique_id, $atts );

		return ob_get_clean();
	}

	/**
	 * Enqueue and localize assets
	 */
	private function enqueue_assets( $ajax_enabled ) {
		wp_enqueue_style( 'shortcodeglut-table-list' );
		wp_enqueue_script( 'shortcodeglut-table-list' );
		wp_enqueue_script( 'shortcodeglut-table-list-add-to-cart' );

		wp_localize_script( 'shortcodeglut-table-list', 'shortcodeglutTableAjax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'shortcodeglut_table_list_nonce' ),
		) );
	}

	/**
	 * Render the complete shortcode output
	 */
	private function render_output( $unique_id, $atts ) {
		$content_id = 'content_' . $unique_id;

		// Prepare attributes for JavaScript - escape for HTML attribute
		$atts_for_js = array(
			'order_by'       => $atts['order_by'],
			'order'          => $atts['order'],
			'items_per_page' => $atts['items_per_page'],
			'columns'        => $atts['columns'],
			'colheads'       => $atts['colheads'],
			'paging'         => $atts['paging'],
			'ajax'           => $atts['ajax'],
			'category'       => $atts['category'],
			'exclude'        => $atts['exclude'],		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Documentation string only; no WP_Query constructed here.                                                                                                    
			'show_icon'      => $atts['show_icon'] ? '1' : '0',
			'icon_width'     => $atts['icon_width'],
			'show_sku'       => $atts['show_sku'] ? '1' : '0',
		);
		$atts_json = htmlspecialchars( wp_json_encode( $atts_for_js ), ENT_QUOTES, 'UTF-8' );

		echo '<div class="shortcodeglut-archive-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper"
		        data-shortcode-id="' . esc_attr( $unique_id ) . '"
		        data-order-by="' . esc_attr( $atts['order_by'] ) . '"
		        data-order="' . esc_attr( $atts['order'] ) . '"
		        data-atts=\'' . esc_attr($atts_json) . '\'>';

		// Optional breadcrumb
		if ( $atts['show_breadcrumb'] ) {
			$this->render_breadcrumb();
		}

		// Optional title
		if ( ! empty( $atts['title'] ) ) {
			echo '<div class="shortcodeglut-header">';
			echo '<h1 class="shortcodeglut-title">' . esc_html( $atts['title'] ) . '</h1>';
			echo '</div>';
		}

		// Products content area
		echo '<div id="' . esc_attr( $content_id ) . '" class="shortcodeglut-table-list-content shortcodeglut-table-list-cart">';

		// Load products
		 $current_paged = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' ) ? 1 : max( 1, get_query_var( 'paged' ) );
         $this->render_products( $atts, $current_paged );

		echo '</div>'; // End content area

		echo '</div>';
	}

	/**
	 * Render breadcrumb
	 */
	private function render_breadcrumb() {
		echo '<nav class="shortcodeglut-breadcrumb">';
		echo '<a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Home', 'shortcodeglut' ) . '</a>';
		echo '<span>/</span>';
		echo '<span>' . esc_html__( 'All Products', 'shortcodeglut' ) . '</span>';
		echo '</nav>';
	}

	/**
	 * Parse column definitions
	 */
	private function parse_columns( $cols_string ) {
		$columns   = array();
		$col_parts = explode( '|', $cols_string );

		foreach ( $col_parts as $part ) {
			$fields    = explode( ',', $part );
			$columns[] = array_map( 'trim', $fields );
		}

		return $columns;
	}

	/**
	 * Parse column headers
	 */
	private function parse_headers( $headers_string, $count ) {
		$headers = explode( '|', $headers_string );

		while ( count( $headers ) < $count ) {
			$headers[] = '';
		}

		return array_map( 'trim', $headers );
	}

	/**
	 * Render products
	 */
	private function render_products( $atts, $paged = 1 ) {
		// Build WP_Query arguments
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['items_per_page'],
			'paged'          => $paged,
			'orderby'        => $atts['order_by'],
			'order'          => $atts['order'],
		);

		// Filter by category if specified.
		// tax_query is required here as it is the only way to filter by taxonomy term.
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

		// Exclude specific products if requested via shortcode attribute.
		// The exclude list is user-bounded (comma-separated IDs), so performance
		// impact is accepted and intentional for this shortcode feature.
		if ( ! empty( $atts['exclude'] ) ) {
			$exclude_ids = array_map( 'absint', explode( ',', $atts['exclude'] ) );
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			$query_args['post__not_in'] = $exclude_ids;
		}

		// Price ordering requires meta_key; _price is indexed by WooCommerce.
		if ( $atts['order_by'] === 'price' ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['meta_key'] = '_price';
			$query_args['orderby']  = 'meta_value_num';
		}

		// Query products
		$products_query = new \WP_Query( $query_args );


		// Parse columns
		$columns = $this->parse_columns( $atts['columns'] );


		// Filter out 'icon' from columns for header display since it's rendered separately
		$filtered_columns = array_filter( $columns, function( $column_fields ) {
			return ! in_array( 'icon', $column_fields, true );
		} );


			// Calculate dynamic grid template
			$grid_parts = array();
			if ( $atts['show_icon'] ) {
				$grid_parts[] = $atts['icon_width'] . 'px';
			}
			foreach ( $filtered_columns as $column_group ) {
				$grid_parts[] = '1fr';
			}
			$grid_template = implode( ' ', $grid_parts );

			$headers = $this->parse_headers( $atts['colheads'], count( $filtered_columns ) );

			// Render table
			echo '<div class="shortcodeglut-table">';

		// Table header
		if ( ! empty( $headers ) ) {
			echo '<div class="shortcodeglut-table-header" style="display: grid; grid-template-columns: ' . esc_attr( $grid_template ) . ';">';
			// Add empty cell for icon column if showing icon
			if ( $atts['show_icon'] ) {
				echo '<span></span>';
			}
			$header_index = 0;
			foreach ( $filtered_columns as $column_group ) {
				$column_name = isset( $column_group[0] ) ? $column_group[0] : '';
				$header_text = isset( $headers[ $header_index ] ) ? $headers[ $header_index ] : '';

				// Always show sort indicators
				$sort_icon = ' &#8645;'; // Default neutral arrows (up/down)

				echo '<span class="shortcodeglut-table-sortable" data-sort="' . esc_attr( $column_name ) . '">' . esc_html( $header_text ) . esc_html($sort_icon) . '</span>';
				$header_index++;
				}
			echo '</div>';
		}

		// Render products
		if ( $products_query->have_posts() ) {
			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$this->render_product_row( $product, $columns, $atts, $grid_template );
			}
		} else {
			echo '<div class="shortcodeglut-table-item">';
			echo '<span>' . esc_html__( 'No products found', 'shortcodeglut' ) . '</span>';
			echo '</div>';
		}

		echo '</div>'; // End table

		 // Pagination
		if ( $atts['paging'] ) {
			$this->render_pagination( $products_query, $atts, $paged );
		}

		wp_reset_postdata();
	}

	/**
	 * Render a single product row
	 */
	private function render_product_row( $product, $columns, $atts, $grid_template = '' ) {
		echo '<div class="shortcodeglut-table-item" style="display: grid; grid-template-columns: ' . esc_attr( $grid_template ) . ';">';

		// Product icon
		if ( $atts['show_icon'] ) {
			echo '<div class="shortcodeglut-product-icon" style="width:' . esc_attr( $atts['icon_width'] ) . 'px;height:' . esc_attr( $atts['icon_width'] ) . 'px;">';

			$image_id = $product->get_image_id();
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, array( $atts['icon_width'], $atts['icon_width'] ) );
			} else {
				echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
			}

			echo '</div>';
		}

		// Filter out 'icon' from columns since it's rendered separately above
		$filtered_columns = array_filter( $columns, function( $column_fields ) {
			return ! in_array( 'icon', $column_fields, true );
		} );

		// Product columns
		foreach ( $filtered_columns as $column_fields ) {
			echo '<div class="shortcodeglut-product-info">';
			foreach ( $column_fields as $field ) {
				$this->render_field_value( $field, $product, $atts );
			}
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Render field value
	 */
	private function render_field_value( $field, $product, $atts ) {
		switch ( $field ) {
			case 'title':
				echo '<div class="shortcodeglut-product-title">';
				echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a>';
				if ( $atts['show_sku'] && $product->get_sku() ) {
					echo '<div class="shortcodeglut-product-sku">SKU: ' . esc_html( $product->get_sku() ) . '</div>';
				}
				echo '</div>';
				break;

			case 'price':
				echo '<div class="shortcodeglut-product-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
				break;

			case 'date':
				$date = get_the_date( 'M j, Y', $product->get_id() );
				echo '<div class="shortcodeglut-product-date">' . esc_html( $date ) . '</div>';
				break;

			 case 'actions':
				echo '<div class="shortcodeglut-product-actions">';
				if ( $product->is_purchasable() && $product->is_in_stock() ) {
					$cart_url = wc_get_cart_url();
					echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '"
							class="shortcodeglut-btn shortcodeglut-btn-primary shortcodeglut-add-to-cart-btn ajax_add_to_cart"
							data-product_id="' . esc_attr( $product->get_id() ) . '"
							data-product-url="' . esc_url( get_permalink( $product->get_id() ) ) . '"
							data-cart-url="' . esc_url( $cart_url ) . '">';
					echo '<i class="fa-solid fa-cart-shopping"></i> <span>' . esc_html__( 'Add to Cart', 'shortcodeglut' ) . '</span>';
					echo '</a>';
				}
				echo '</div>';
				break;

			case 'stock':
				$stock_status = $product->is_in_stock() ?
					'<span class="in-stock">' . esc_html__( 'In Stock', 'shortcodeglut' ) . '</span>' :
					'<span class="out-of-stock">' . esc_html__( 'Out of Stock', 'shortcodeglut' ) . '</span>';
				echo '<div class="shortcodeglut-product-stock">' . wp_kses_post( $stock_status ) . '</div>';
				break;
		    case 'category':
			$categories = get_the_terms( $product->get_id(), 'product_cat' );
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$category_links = array();
				foreach ( $categories as $category ) {
					$category_links[] = '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
				}
				echo '<div class="shortcodeglut-product-category">' . wp_kses_post(implode( ', ', $category_links )) . '</div>';
			}
			break;

		case 'excerpt':
			$excerpt = $product->get_short_description();
			if ( ! empty( $excerpt ) ) {
				echo '<div class="shortcodeglut-product-excerpt">' . wp_kses_post( wp_trim_words( $excerpt, 15, '...' ) ) . '</div>';
			}
			break;

		case 'rating':
			$average = $product->get_average_rating();
			if ( $average > 0 ) {
				$rating_html = wc_get_rating_html( $average, $product->get_rating_count() );
				echo '<div class="shortcodeglut-product-rating">' . wp_kses_post( $rating_html ) . '</div>';
			} else {
				echo '<div class="shortcodeglut-product-rating">' . esc_html__( 'Not yet rated', 'shortcodeglut' ) . '</div>';
			}
			break;

		case 'sales':
			$sales = $product->get_total_sales();
			  /* translators: %d: number of products sold */
			echo '<div class="shortcodeglut-product-sales">' . sprintf( esc_html__( '%d sold', 'shortcodeglut' ), absint( $sales ) ) . '</div>';
			break;

		case 'sku':
			if ( $product->get_sku() ) {
				echo '<div class="shortcodeglut-product-sku-only">' . esc_html( $product->get_sku() ) . '</div>';
			} else {
				echo '<div class="shortcodeglut-product-sku-only">' . esc_html__( 'N/A', 'shortcodeglut' ) . '</div>';
			}
			break;

		case 'attributes':
			$attributes = $product->get_attributes();
			if ( ! empty( $attributes ) ) {
				echo '<div class="shortcodeglut-product-attributes">';
				$attr_strings = array();
				foreach ( $attributes as $attribute ) {
					if ( $attribute->get_visible() ) {
						$name = wc_attribute_label( $attribute->get_name() );
						$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
						if ( ! empty( $values ) ) {
							$attr_strings[] = esc_html( $name ) . ': ' . esc_html( implode( ', ', $values ) );
						}
					}
				}
				echo wp_kses_post(implode( '<br>', $attr_strings ));
				echo '</div>';
			}
			break;

			default:
				do_action( 'shortcodeglut_table_list_field', $field, $product, $atts );
				break;
		}
	}

	/**
	 * Render pagination
	 */
	private function render_pagination( $query, $atts, $current_page = 1 ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		// For non-AJAX, get page from URL. For AJAX, use the passed parameter.
		if ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' ) {
			$current_page = max( 1, $current_page );                                                                                                          
		} else {
			$current_page = max( 1, get_query_var( 'paged' ) );                                                                                               
		}		
		$max_pages    = $query->max_num_pages;
		$is_ajax      = ( $atts['ajax'] === 'on' || $atts['ajax'] === '1' || $atts['ajax'] === 'true' );
		$ajax_class   = $is_ajax ? ' async-pagination' : '';

		echo '<div class="shortcodeglut-pagination' . esc_attr( $ajax_class ) . '">';
		echo '<ul class="page-numbers">';

		// Previous button
		if ( $current_page > 1 ) {
			$prev_link = $is_ajax ? '#' : get_pagenum_link( $current_page - 1 );
            $data_page = $is_ajax ? ' data-page="' . esc_attr( (string) $current_page - 1 ) . '"' : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_page is pre-escaped when assigned.
			echo '<li><a class="prev page-numbers" href="' . esc_url( $prev_link ) . '"' . $data_page . '>&laquo;</a></li>';		
          }

		// Page numbers
		for ( $i = 1; $i <= $max_pages; $i++ ) {
			$active_class = ( $i === $current_page ) ? ' current' : '';
			  $page_link    = $is_ajax ? '#' : get_pagenum_link( $i );
  echo '<li><a class="page-numbers' . esc_attr( $active_class ) . '" href="' . esc_url( $page_link ) . '" data-page="' . esc_attr( $i ) . '">' .        
  esc_html( $i ) . '</a></li>';

		}

		// Next button
		if ( $current_page < $max_pages ) {
			$next_link = $is_ajax ? '#' : get_pagenum_link( $current_page + 1 );
            $data_page = $is_ajax ? ' data-page="' . esc_attr( (string) $current_page + 1 ) . '"' : '';
			 echo '<li><a class="next page-numbers" href="' . esc_url( $next_link ) . '"' . ( $is_ajax ? ' data-page="' . esc_attr( $current_page + 1 ) . '"' : ''
  ) . '>&raquo;</a></li>';                      
		}

		echo '</ul>';
		echo '</div>';
	}

	/**
	 * AJAX handler for loading products
	 */
	public function ajax_load_products() {
		check_ajax_referer( 'shortcodeglut_table_list_nonce', 'nonce', false );

		  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Nonce verification handles security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'shortcodeglut_table_list_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		// Get attributes from AJAX request.
		// The exclude attribute is intentionally passed through for consistency with
		// the shortcode render; post__not_in performance impact is user-bounded.
		$atts = array(
			'order_by'       => isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'title',
			'order'          => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'ASC',
			'items_per_page' => isset( $_POST['items_per_page'] ) ? absint( wp_unslash( $_POST['items_per_page'] ) ) : 9,
			'columns'        => isset( $_POST['columns'] ) ? sanitize_text_field( wp_unslash( $_POST['columns'] ) ) : 'icon|title|price|date|actions',
			'colheads'       => isset( $_POST['colheads'] ) ? sanitize_text_field( wp_unslash( $_POST['colheads'] ) ) : 'Product|Price|Date|Actions',
			'paging'         => isset( $_POST['paging'] ) ? absint( wp_unslash( $_POST['paging'] ) ) : 1,
			'ajax'           => isset( $_POST['ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax'] ) ) : 'off',
			'category'       => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			'exclude'        => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
			'show_icon'      => isset( $_POST['show_icon'] ) ? filter_var( wp_unslash( $_POST['show_icon'] ), FILTER_VALIDATE_BOOLEAN ) : true,
			'icon_width'     => isset( $_POST['icon_width'] ) ? absint( wp_unslash( $_POST['icon_width'] ) ) : 44,
			'show_sku'       => isset( $_POST['show_sku'] ) ? filter_var( wp_unslash( $_POST['show_sku'] ), FILTER_VALIDATE_BOOLEAN ) : true,
		);

		// Render products
		ob_start();
		$this->render_products( $atts, $paged );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
