<?php
/**
 * WooCommerce Product Table Shortcode Handler
 *
 * Similar to WPDM's [wpdm_all_packages] shortcode, this handles [shopglut_product_table] shortcode
 * to display products in a responsive, sortable, searchable data table
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\ProductTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProductTableShortcode {
	private static $instance = null;
	private $shortcode_counter = 0;

	public function __construct() {
		// Register shortcode
		add_shortcode( 'shopglut_product_table', array( $this, 'render_product_table_shortcode' ) );
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
	public function render_product_table_shortcode( $atts ) {
		// Skip rendering during REST API requests (block editor validation)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<div class="shopglut-product-table-placeholder">[ShopGlut Product Table]</div>';
		}

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '<p class="shopglut-error">' . esc_html__( 'WooCommerce is required for this shortcode to work.', 'shortcodeglut' ) . '</p>';
		}

		// Increment counter for unique IDs
		$this->shortcode_counter++;
		$unique_id = 'shopglut_product_table_' . $this->shortcode_counter;

		// Parse shortcode attributes with defaults
		$atts = shortcode_atts( array(
			// Basic options
			'cols' => 'title|price|stock|categories|date|add_to_cart',
			'colheads' => 'Product|Price|Stock|Categories|Date|Action',
			'design' => 'classic',
			'title' => '',
			'description' => '',
			// Display options
			'show_items_per_page' => '1',           // Show items per page dropdown
			'items_per_page' => '10',               // Default items per page (10, 25, 50, 100)
			'show_search' => '1',                   // Show search field
			'show_category_filter' => '1',          // Show category filter dropdown
			'show_tag_filter' => '0',               // Show tag filter dropdown
			'show_stock_filter' => '0',             // Show stock filter dropdown
			// Other options
			'orderby' => 'date',
			'order' => 'DESC',
			'categories' => '',
			'exclude' => '',                    // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Properly sanitized
			'include' => '',
			'thumb' => 0,
			'thumb_width' => 48,
			'template' => '',
			'login' => 0,
			'sorting' => 1,
			'responsive' => 1,
		), $atts, 'shopglut_product_table' );

		// Sanitize attributes
		$atts['cols'] = sanitize_text_field( $atts['cols'] );
		$atts['colheads'] = sanitize_text_field( $atts['colheads'] );
		$atts['design'] = sanitize_text_field( $atts['design'] );
		$atts['title'] = sanitize_text_field( $atts['title'] );
		$atts['description'] = sanitize_text_field( $atts['description'] );
		$atts['items_per_page'] = absint( $atts['items_per_page'] );
		$atts['orderby'] = sanitize_text_field( $atts['orderby'] );
		$atts['order'] = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['categories'] = sanitize_text_field( $atts['categories'] );
		$atts['exclude'] = sanitize_text_field( $atts['exclude'] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Properly sanitized before use
		$atts['include'] = sanitize_text_field( $atts['include'] );
		$atts['thumb'] = absint( $atts['thumb'] );
		$atts['thumb_width'] = absint( $atts['thumb_width'] );
		$atts['template'] = sanitize_text_field( $atts['template'] );
		$atts['login'] = filter_var( $atts['login'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_items_per_page'] = filter_var( $atts['show_items_per_page'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_search'] = filter_var( $atts['show_search'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_category_filter'] = filter_var( $atts['show_category_filter'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_tag_filter'] = filter_var( $atts['show_tag_filter'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_stock_filter'] = filter_var( $atts['show_stock_filter'], FILTER_VALIDATE_BOOLEAN );
		$atts['sorting'] = filter_var( $atts['sorting'], FILTER_VALIDATE_BOOLEAN );
		$atts['responsive'] = filter_var( $atts['responsive'], FILTER_VALIDATE_BOOLEAN );

		// Check login requirement
		if ( $atts['login'] && ! is_user_logged_in() ) {
			return $this->get_login_message();
		}

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
		// Enqueue product table styles
		wp_enqueue_style( 'shopglut-product-table', SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/css/style.css', array(), SHORTCODEGLUT_VERSION );

		// Enqueue product table script
		wp_enqueue_script( 'shopglut-product-table', SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/js/script.js', array( 'jquery' ), SHORTCODEGLUT_VERSION, true );

		// Localize script
		wp_localize_script( 'shopglut-product-table', 'shopglutTableParams', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shopglut_table_nonce' ),
		));
	}

	/**
	 * Render the complete shortcode output
	 */
	private function render_output( $unique_id, $atts ) {
		// Parse column definitions
		$columns = $this->parse_columns( $atts['cols'] );
		$headers = $this->parse_headers( $atts['colheads'], count( $columns ) );

		// Get products
		$products = $this->get_products( $atts );

		// Default title and description
		$title = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Product Table', 'shortcodeglut' );
		$description = ! empty( $atts['description'] ) ? $atts['description'] : __( 'Search, Filter, and Sort Products', 'shortcodeglut' );

		// Generate data-column attributes for sorting
		$column_data_attrs = array();
		foreach ( $headers as $index => $header ) {
			$column_data_attrs[] = $this->slugify( $header );
		}

		// Add design class to wrapper
		$design_class = 'sgpt-design-' . sanitize_html_class( $atts['design'] );

		echo '<div class="shopglut-product-table-wrapper ' . esc_attr( $design_class ) . '" id="' . esc_attr( $unique_id ) . '_wrapper" data-table-id="' . esc_attr( $unique_id ) . '">';

		// Header section - only for modern design
		if ( $atts['design'] === 'modern' ) {
			echo '<div class="sgpt-header">';
			echo '<h2>' . esc_html( $title ) . '</h2>';
			if ( ! empty( $description ) ) {
				echo '<p>' . esc_html( $description ) . '</p>';
			}
			echo '</div>';
		}

		// Start table container
		echo '<div class="sgpt-table-container">';

		// Filter/Controls section
		$this->render_controls( $unique_id, $atts );

		// Results count (modern only)
		if ( $atts['design'] === 'modern' ) {
			echo '<div class="sgpt-results-count" id="sgpt-results-count-' . esc_attr( $unique_id ) . '"></div>';
		}

		// Table wrapper (modern only)
		if ( $atts['design'] === 'modern' ) {
			echo '<div class="sgpt-table-wrapper">';
		}

		echo '<table id="' . esc_attr( $unique_id ) . '" class="shopglut-product-table' . ( $atts['responsive'] ? ' responsive' : '' ) . '" data-items-per-page="' . esc_attr( $atts['items_per_page'] ) . '">';

		// Table header
		echo '<thead>';
		echo '<tr>';
		foreach ( $headers as $index => $header ) {
			$no_sort_class = ( ! $atts['sorting'] ) ? 'no-sort' : '';
					$data_column = $column_data_attrs[ $index ];

					echo '<th class="' . esc_attr( $no_sort_class ) . '" data-column="' . esc_attr( $data_column ) . '">' . esc_html( $header ) . '</th>';
		}
		echo '</tr>';
		echo '</thead>';

		// Table body
		echo '<tbody>';
		if ( $products->have_posts() ) {
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				// Get product categories and tags for filtering
				$product_categories = get_the_terms( $product->get_id(), 'product_cat' );
				$category_slugs = is_array( $product_categories ) ? wp_list_pluck( $product_categories, 'slug' ) : array();
				$category_names = is_array( $product_categories ) ? wp_list_pluck( $product_categories, 'name' ) : array();

				$product_tags = get_the_terms( $product->get_id(), 'product_tag' );
				$tag_slugs = is_array( $product_tags ) ? wp_list_pluck( $product_tags, 'slug' ) : array();
				$tag_names = is_array( $product_tags ) ? wp_list_pluck( $product_tags, 'name' ) : array();

				// Determine stock status
				$stock_status = 'in-stock';
				$stock_text = __( 'In Stock', 'shortcodeglut' );
				if ( ! $product->is_in_stock() ) {
					$stock_status = 'out-of-stock';
					$stock_text = __( 'Out of Stock', 'shortcodeglut' );
				} elseif ( $product->is_on_backorder() ) {
					$stock_status = 'on-backorder';
					$stock_text = __( 'On Backorder', 'shortcodeglut' );
				}

				$row_data_attrs = array(
					'data-categories' => implode( ',', $category_slugs ),
					'data-category-names' => implode( ',', $category_names ),
					'data-tags' => implode( ',', $tag_slugs ),
					'data-tag-names' => implode( ',', $tag_names ),
					'data-stock' => $stock_status,
				);

				// Build data attributes string - each key/value already escaped with esc_attr()
				$attrs_array = array();
				foreach ( $row_data_attrs as $key => $value ) {
					$attrs_array[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output properly escaped, each attribute value escaped with esc_attr() above
				echo '<tr ' . implode( ' ', $attrs_array ) . '>';
				foreach ( $columns as $col_index => $column_fields ) {
					echo '<td data-label="' . esc_attr( $headers[ $col_index ] ) . '">';
					foreach ( $column_fields as $field ) {
						echo wp_kses_post( $this->get_field_value( $field, $product, $atts, $unique_id ) );
					}
					echo '</td>';
				}
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="' . esc_attr( count( $columns ) ) . '">' . esc_html__( 'No products found', 'shortcodeglut' ) . '</td></tr>';
		}
		echo '</tbody>';

		echo '</table>';

		// Close table wrapper (only for modern design)
		if ( $atts['design'] === 'modern' ) {
			echo '</div>'; // Close sgpt-table-wrapper
		}

		echo '</div>'; // Close sgpt-table-container
		echo '</div>'; // Close shopglut-product-table-wrapper

		wp_reset_postdata();
	}

	/**
	 * Render controls/filter section
	 */
	private function render_controls( $unique_id, $atts ) {
		$page_lengths = array( 10, 25, 50, 100 );
		$current_length = $atts['items_per_page'];

		if ( $atts['design'] === 'modern' ) {
			// Modern design layout
			echo '<div class="sgpt-controls">';

			// Items per page
			if ( $atts['show_items_per_page'] ) {
				echo '<div class="sgpt-items-per-page">';
				echo '<label for="sgpt-length-' . esc_attr( $unique_id ) . '">' . esc_html__( 'Show', 'shortcodeglut' ) . '</label>';
				echo '<select id="sgpt-length-' . esc_attr( $unique_id ) . '" class="sgpt-length-select">';
				foreach ( $page_lengths as $length ) {
					echo '<option value="' . esc_attr( $length ) . '" ' . selected( $current_length, $length, false ) . '>' . esc_html( $length ) . '</option>';
				}
				echo '</select>';
				echo '<span>' . esc_html__( 'entries', 'shortcodeglut' ) . '</span>';
				echo '</div>';
			}

			// Category filter
			if ( $atts['show_category_filter'] ) {
				echo '<div class="sgpt-filter-group">';
				echo '<label for="sgpt-category-filter-' . esc_attr( $unique_id ) . '">' . esc_html__( 'Category:', 'shortcodeglut' ) . '</label>';
				echo '<select id="sgpt-category-filter-' . esc_attr( $unique_id ) . '" class="sgpt-category-filter">';
				echo '<option value="">' . esc_html__( 'All Categories', 'shortcodeglut' ) . '</option>';
				echo '</select>';
				echo '</div>';
			}

			// Tag filter
			if ( $atts['show_tag_filter'] ) {
				echo '<div class="sgpt-filter-group">';
				echo '<label for="sgpt-tag-filter-' . esc_attr( $unique_id ) . '">' . esc_html__( 'Tag:', 'shortcodeglut' ) . '</label>';
				echo '<select id="sgpt-tag-filter-' . esc_attr( $unique_id ) . '" class="sgpt-tag-filter">';
				echo '<option value="">' . esc_html__( 'All Tags', 'shortcodeglut' ) . '</option>';
				echo '</select>';
				echo '</div>';
			}

			// Stock filter
			if ( $atts['show_stock_filter'] ) {
				echo '<div class="sgpt-filter-group">';
				echo '<label for="sgpt-stock-filter-' . esc_attr( $unique_id ) . '">' . esc_html__( 'Stock:', 'shortcodeglut' ) . '</label>';
				echo '<select id="sgpt-stock-filter-' . esc_attr( $unique_id ) . '" class="sgpt-stock-filter">';
				echo '<option value="">' . esc_html__( 'All Stock', 'shortcodeglut' ) . '</option>';
				echo '<option value="in-stock">' . esc_html__( 'In Stock', 'shortcodeglut' ) . '</option>';
				echo '<option value="out-of-stock">' . esc_html__( 'Out of Stock', 'shortcodeglut' ) . '</option>';
				echo '</select>';
				echo '</div>';
			}

			// Search box (always last on right)
			if ( $atts['show_search'] ) {
				echo '<div class="sgpt-search-box">';
				echo '<input type="search" class="sgpt-search-input" id="sgpt-search-' . esc_attr( $unique_id ) . '" placeholder="' . esc_attr__( 'Search...', 'shortcodeglut' ) . '" />';
				echo '</div>';
			}

			echo '</div>'; // End sgpt-controls
		} else {
			// Classic design layout
			echo '<div class="sgpt-filter-row">';

			echo '<div class="sgpt-filter-cell sgpt-filter-start">';

			// Items per page
			if ( $atts['show_items_per_page'] ) {
				echo '<div class="sgpt-length-control">';
				echo '<select id="sgpt-length-' . esc_attr( $unique_id ) . '" class="sgpt-length-select">';
				foreach ( $page_lengths as $length ) {
					echo '<option value="' . esc_attr( $length ) . '" ' . selected( $current_length, $length, false ) . '>' . esc_html( $length ) . '</option>';
				}
				echo '</select>';
				echo '<label for="sgpt-length-' . esc_attr( $unique_id ) . '">' . esc_html__( 'per page', 'shortcodeglut' ) . '</label>';
				echo '</div>';
			}

			// Category filter
			if ( $atts['show_category_filter'] ) {
				echo '<div class="sgpt-category-wrap">';
				echo '<select id="sgpt-category-filter-' . esc_attr( $unique_id ) . '" class="sgpt-category-select sgpt-category-filter">';
				echo '<option value="">' . esc_html__( 'All Categories', 'shortcodeglut' ) . '</option>';
				echo '</select>';
				echo '</div>';
			}

			// Tag filter
			if ( $atts['show_tag_filter'] ) {
				echo '<div class="sgpt-tag-wrap">';
				echo '<select id="sgpt-tag-filter-' . esc_attr( $unique_id ) . '" class="sgpt-tag-select sgpt-tag-filter">';
				echo '<option value="">' . esc_html__( 'All Tags', 'shortcodeglut' ) . '</option>';
				echo '</select>';
				echo '</div>';
			}

			// Stock filter
			if ( $atts['show_stock_filter'] ) {
				echo '<div class="sgpt-stock-wrap">';
				echo '<select id="sgpt-stock-filter-' . esc_attr( $unique_id ) . '" class="sgpt-stock-select sgpt-stock-filter">';
				echo '<option value="">' . esc_html__( 'All Stock', 'shortcodeglut' ) . '</option>';
				echo '<option value="in-stock">' . esc_html__( 'In Stock', 'shortcodeglut' ) . '</option>';
				echo '<option value="out-of-stock">' . esc_html__( 'Out of Stock', 'shortcodeglut' ) . '</option>';
				echo '</select>';
				echo '</div>';
			}

			echo '</div>'; // End sgpt-filter-start

			// Search box (always last on right)
			if ( $atts['show_search'] ) {
				echo '<div class="sgpt-filter-cell sgpt-filter-end">';
				echo '<div class="sgpt-search-control">';
				echo '<input type="search" class="sgpt-search-input" id="sgpt-search-' . esc_attr( $unique_id ) . '" placeholder="' . esc_attr__( 'Search...', 'shortcodeglut' ) . '" />';
				echo '</div>';
				echo '</div>';
			}

			echo '</div>'; // End sgpt-filter-row
		}
	}

	/**
	 * Parse column definitions
	 */
	private function parse_columns( $cols_string ) {
		$columns = array();
		$col_parts = explode( '|', $cols_string );

		foreach ( $col_parts as $part ) {
			$fields = explode( ',', $part );
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
	 * Convert string to slug for data-column attribute
	 */
	private function slugify( $text ) {
		$text = preg_replace( '~[^\pL\d]+~u', '-', $text );
		$text = preg_replace( '~[^-\w]+~', '', $text );
		$text = trim( $text, '-' );
		$text = preg_replace( '~-+~', '-', $text );
		$text = strtolower( $text );
		return $text ?: 'column';
	}

	/**
	 * Get products based on attributes
	 */
	private function get_products( $atts ) {
		$args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => $atts['orderby'],
			'order' => $atts['order'],
		);

		if ( ! empty( $atts['categories'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy query required for product filtering
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => array_map( 'trim', explode( ',', $atts['categories'] ) ),
				),
			);
		}

		if ( ! empty( $atts['include'] ) ) {
			$args['post__in'] = array_map( 'absint', explode( ',', $atts['include'] ) );
		}

		if ( ! empty( $atts['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Properly sanitized with absint
			$args['post__not_in'] = array_map( 'absint', explode( ',', $atts['exclude'] ) );
		}

		return new \WP_Query( $args );
	}

	/**
	 * Get field value for display
	 */
	private function get_field_value( $field, $product, $atts, $table_id ) {
		$value = '';

		switch ( $field ) {
			case 'ID':
				$value = $product->get_id();
				break;

			case 'title':
				$value = '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a>';
				break;

			case 'page_link':
				$value = '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html__( 'View', 'shortcodeglut' ) . '</a>';
				break;

			case 'description':
			case 'post_content':
				$value = wp_kses_post( wp_trim_words( $product->get_description(), 10 ) );
				break;

			case 'excerpt':
				$value = wp_kses_post( $product->get_short_description() );
				break;

			case 'price':
				$value = $product->get_price_html();
				break;

			case 'regular_price':
				$value = wc_price( $product->get_regular_price() );
				break;

			case 'sale_price':
				$value = $product->is_on_sale() ? wc_price( $product->get_sale_price() ) : '';
				break;

			case 'stock':
				$stock_status = 'in-stock';
				$stock_text = __( 'In Stock', 'shortcodeglut' );
				if ( ! $product->is_in_stock() ) {
					$stock_status = 'out-of-stock';
					$stock_text = __( 'Out of Stock', 'shortcodeglut' );
				} elseif ( $product->is_on_backorder() ) {
					$stock_status = 'on-backorder';
					$stock_text = __( 'On Backorder', 'shortcodeglut' );
				}
				// Use badge style for modern, text for classic
				$badge_class = ( $atts['design'] === 'modern' ) ? 'sgpt-status' : '';
				$value = '<span class="' . esc_attr( $badge_class ) . ' ' . esc_attr( $stock_status ) . '">' . esc_html( $stock_text ) . '</span>';
				break;

			case 'stock_quantity':
				$value = $product->managing_stock() ? $product->get_stock_quantity() : '';
				break;

			case 'categories':
				$categories = get_the_terms( $product->get_id(), 'product_cat' );
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$cat_links = array();
					foreach ( $categories as $category ) {
						$cat_links[] = '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
					}
					$value = implode( ', ', $cat_links );
				}
				break;

			case 'tags':
				$tags = get_the_terms( $product->get_id(), 'product_tag' );
				if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
					$tag_links = array();
					foreach ( $tags as $tag ) {
						$tag_links[] = '<a href="' . esc_url( get_term_link( $tag ) ) . '">' . esc_html( $tag->name ) . '</a>';
					}
					$value = implode( ', ', $tag_links );
				}
				break;

			case 'sku':
				$value = $product->get_sku();
				break;

			case 'date':
				$value = get_the_date( get_option( 'date_format' ), $product->get_id() );
				break;

			case 'modified_date':
			case 'update_date':
				$value = get_the_modified_date( get_option( 'date_format' ), $product->get_id() );
				break;

			case 'thumbnail':
			case 'thumb':
				if ( $atts['thumb'] && has_post_thumbnail( $product->get_id() ) ) {
					$value = get_the_post_thumbnail( $product->get_id(), array( $atts['thumb_width'], $atts['thumb_width'] ) );
				} else {
					$value = wc_placeholder_img( array( $atts['thumb_width'], $atts['thumb_width'] ) );
				}
				break;

			case 'add_to_cart':
				$add_to_cart_class = 'shopglut-table-add-to-cart';
				$product_id = $product->get_id();
				$is_purchasable = $product->is_purchasable() && $product->is_in_stock();
				$is_variable = $product->is_type( 'variable' );

				// Variable products - show "Select Options" button
				if ( $is_variable ) {
					$value = sprintf(
						'<a href="%s" class="button">%s</a>',
						esc_url( get_permalink( $product->get_id() ) ),
						esc_html__( 'Select Options', 'shortcodeglut' )
					);
				} elseif ( $is_purchasable ) {
					// Simple products - AJAX add to cart
					$value = sprintf(
						'<a href="%s" class="button %s" data-product_id="%s">%s</a>',
						esc_url( $product->add_to_cart_url() ),
						esc_attr( $add_to_cart_class . ' ajax_add_to_cart' ),
						esc_attr( $product_id ),
						esc_html__( 'Add to Cart', 'shortcodeglut' )
					);
				} else {
					// Not purchasable or out of stock
					$value = sprintf(
						'<a href="%s" class="button">%s</a>',
						esc_url( get_permalink( $product->get_id() ) ),
						esc_html__( 'Read More', 'shortcodeglut' )
					);
				}
				break;

			case 'view':
				$value = '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" class="button shopglut-table-view-product">' . esc_html__( 'View', 'shortcodeglut' ) . '</a>';
				break;

			case 'rating':
				$value = wc_get_rating_html( $product->get_average_rating() );
				break;

			case 'rating_count':
				$value = $product->get_rating_count();
				break;

			case 'sales':
			case 'total_sales':
				$value = number_format( $product->get_total_sales() );
				break;

			case 'weight':
				$value = $product->get_weight() ? wc_format_weight( $product->get_weight() ) : '';
				break;

			case 'dimensions':
				$value = $product->has_dimensions() ? wc_format_dimensions( $product->get_dimensions() ) : '';
				break;
		}

		return $value;
	}

	/**
	 * Get login message
	 */
	private function get_login_message() {
		ob_start();
		?>
		<div class="shopglut-product-table-login-message woocommerce-info">
			<?php echo esc_html__( 'Please login to view products.', 'shortcodeglut' ); ?>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="button"><?php echo esc_html__( 'Login', 'shortcodeglut' ); ?></a>
		</div>
		<?php
		return ob_get_clean();
	}
}
