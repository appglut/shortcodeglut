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
			'cols' => 'title,price,stock|categories|date|add_to_cart', // Column definition (| separated, , for multiple fields in same column)
			'colheads' => 'Title|Price|Stock|Categories|Date|Action', // Column headers
			'items_per_page' => 20,              // Items per page
			'orderby' => 'date',                 // Order field
			'order' => 'DESC',                   // Order direction
			'categories' => '',                  // Filter by category slugs (comma-separated)
			'exclude' => '',                     // Exclude product IDs (comma-separated)
			'include' => '',                     // Include only product IDs (comma-separated)
			'thumb' => 0,                       // Show thumbnail (1) or icon (0)
			'thumb_width' => 48,                 // Thumbnail width in pixels
			'template' => '',                    // Template ID from WooTemplates
			'login' => 0,                        // Require login to view
			'jstable' => 1,                      // Enable DataTables.js
			'paging' => 1,                       // Enable pagination
			'searching' => 1,                    // Enable search
			'sorting' => 1,                      // Enable column sorting
			'responsive' => 1,                   // Enable responsive table
		), $atts, 'shopglut_product_table' );

		// Sanitize attributes
		$atts['cols'] = sanitize_text_field( $atts['cols'] );
		$atts['colheads'] = sanitize_text_field( $atts['colheads'] );
		$atts['items_per_page'] = absint( $atts['items_per_page'] );
		$atts['orderby'] = sanitize_text_field( $atts['orderby'] );
		$atts['order'] = strtoupper( sanitize_text_field( $atts['order'] ) );
		$atts['categories'] = sanitize_text_field( $atts['categories'] );
		$atts['exclude'] = sanitize_text_field( $atts['exclude'] );
		$atts['include'] = sanitize_text_field( $atts['include'] );
		$atts['thumb'] = absint( $atts['thumb'] );
		$atts['thumb_width'] = absint( $atts['thumb_width'] );
		$atts['template'] = sanitize_text_field( $atts['template'] );
		$atts['login'] = filter_var( $atts['login'], FILTER_VALIDATE_BOOLEAN );
		$atts['jstable'] = filter_var( $atts['jstable'], FILTER_VALIDATE_BOOLEAN );
		$atts['paging'] = filter_var( $atts['paging'], FILTER_VALIDATE_BOOLEAN );
		$atts['searching'] = filter_var( $atts['searching'], FILTER_VALIDATE_BOOLEAN );
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
		// Enqueue DataTable if enabled
		if ( $atts['jstable'] ) {
			// Enqueue jQuery DataTables CSS
			wp_enqueue_style( 'jquery-datatables', SHORTCODEGLUT_PLUGIN_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/css/datatables.min.css', array(), SHORTCODEGLUT_VERSION );

			// Enqueue jQuery DataTables JS
			wp_enqueue_script( 'jquery-datatables', SHORTCODEGLUT_PLUGIN_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/js/datatables.min.js', array( 'jquery' ), SHORTCODEGLUT_VERSION, true );
		}

		// Enqueue product table styles
		wp_enqueue_style( 'shopglut-product-table', SHORTCODEGLUT_PLUGIN_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/css/style.css', array(), SHORTCODEGLUT_VERSION );

		// Enqueue product table script
		wp_enqueue_script( 'shopglut-product-table', SHORTCODEGLUT_PLUGIN_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/js/script.js', array( 'jquery' ), SHORTCODEGLUT_VERSION, true );
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

		echo '<div class="shopglut-product-table-wrapper">';
		echo '<table id="' . esc_attr( $unique_id ) . '" class="shopglut-product-table display' . ( $atts['responsive'] ? ' responsive' : '' ) . '"';

		// DataTable options
		if ( $atts['jstable'] ) {
			$table_options = array(
				'pageLength' => $atts['items_per_page'],
				'paging' => $atts['paging'] ? 'true' : 'false',
				'searching' => $atts['searching'] ? 'true' : 'false',
				'order' => array(),
				'responsive' => $atts['responsive'] ? 'true' : 'false',
			);
			echo ' data-options="' . esc_attr( wp_json_encode( $table_options ) ) . '"';
		}

		echo '>';

		// Table header
		echo '<thead>';
		echo '<tr>';
		foreach ( $headers as $header ) {
			echo '<th>' . esc_html( $header ) . '</th>';
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

				echo '<tr>';
				foreach ( $columns as $column_fields ) {
					echo '<td>';
					foreach ( $column_fields as $field ) {
						echo $this->get_field_value( $field, $product, $atts );
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
		echo '</div>';

		wp_reset_postdata();
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

		// Pad with empty strings if not enough headers
		while ( count( $headers ) < $count ) {
			$headers[] = '';
		}

		return array_map( 'trim', $headers );
	}

	/**
	 * Get products based on attributes
	 */
	private function get_products( $atts ) {
		$args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1, // Get all products, DataTable handles pagination
			'orderby' => $atts['orderby'],
			'order' => $atts['order'],
		);

		// Filter by categories
		if ( ! empty( $atts['categories'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => array_map( 'trim', explode( ',', $atts['categories'] ) ),
				),
			);
		}

		// Include/exclude specific products
		if ( ! empty( $atts['include'] ) ) {
			$args['post__in'] = array_map( 'absint', explode( ',', $atts['include'] ) );
		}

		if ( ! empty( $atts['exclude'] ) ) {
			$args['post__not_in'] = array_map( 'absint', explode( ',', $atts['exclude'] ) );
		}

		return new \WP_Query( $args );
	}

	/**
	 * Get field value for display
	 */
	private function get_field_value( $field, $product, $atts ) {
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
				$value = $product->is_in_stock()
					? '<span class="in-stock">' . esc_html__( 'In Stock', 'shortcodeglut' ) . '</span>'
					: '<span class="out-of-stock">' . esc_html__( 'Out of Stock', 'shortcodeglut' ) . '</span>';
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
				$value = sprintf(
					'<a href="%s" class="button shopglut-table-add-to-cart %s" %s>%s</a>',
					esc_url( $product->add_to_cart_url() ),
					$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
					$product->is_purchasable() && $product->is_in_stock() ? 'data-product_id="' . esc_attr( $product->get_id() ) . '"' : '',
					esc_html( $product->is_purchasable() && $product->is_in_stock() ? __( 'Add to Cart', 'shortcodeglut' ) : __( 'Read More', 'shortcodeglut' ) )
				);
				break;

			case 'view':
				$value = '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" class="button">' . esc_html__( 'View', 'shortcodeglut' ) . '</a>';
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
