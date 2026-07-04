<?php
/**
 * WooCommerce Book Flip Shortcode Handler
 *
 * Handles [shortcodeglut_book_flip] shortcode to display products
 * as a 3D flip book with two-page spread and page-turn animations
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\BookFlip;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

class BookFlipShortcode {

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		add_shortcode('shortcodeglut_book_flip', array($this, 'render_shortcode'));
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_assets() {
		wp_register_style(
			'shortcodeglut-book-flip',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/book-flip/assets/css/book-flip.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		wp_register_script(
			'shortcodeglut-book-flip',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/book-flip/assets/js/book-flip.js',
			array('jquery'),
			SHORTCODEGLUT_VERSION,
			true
		);
	}

	/**
	 * Render shortcode
	 */
	public function render_shortcode($atts) {
		if (defined('REST_REQUEST') && REST_REQUEST) {
			return '<div class="shortcodeglut-book-flip-placeholder">[Shortcodeglut Book Flip]</div>';
		}

		if (!function_exists('wc_get_product')) {
			return '<p class="shortcodeglut-error">' . esc_html__('WooCommerce is required.', 'shortcodeglut') . '</p>';
		}

		$atts = shortcode_atts(array(
			'limit'          => 6,
			'category'       => '',
			'exclude_ids'    => '',
			'order_by'       => 'date-desc',
			'show_price'     => '1',
			'show_button'    => '1',
			'button_text'     => 'View Details',
		), $atts, 'shortcodeglut_book_flip');

		// Sanitize attributes
		$atts['limit']          = absint($atts['limit']);
		$atts['category']       = sanitize_text_field($atts['category']);
		$atts['exclude_ids']        = sanitize_text_field($atts['exclude_ids']);
		$atts['order_by']       = sanitize_text_field($atts['order_by']);
		$atts['show_price']     = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
		$atts['show_button']    = filter_var($atts['show_button'], FILTER_VALIDATE_BOOLEAN);
		$atts['button_text']    = sanitize_text_field($atts['button_text']);

		// Enqueue assets
		wp_enqueue_style('shortcodeglut-book-flip');
		wp_enqueue_script('shortcodeglut-book-flip');

		// Get products
		$products = $this->get_products($atts);

		if (empty($products)) {
			return '<div class="shortcodeglut-book-flip-empty">' . esc_html__('No products found.', 'shortcodeglut') . '</div>';
		}

		$total_pages = count($products);
		$unique_id = 'book_flip_' . wp_rand(1000, 9999);

		ob_start();
		$this->render_output($unique_id, $atts, $products, $total_pages);
		return ob_get_clean();
	}

	/**
	 * Get products
	 */
	private function get_products($atts) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['limit'],
		);

		// Handle order_by
		switch ($atts['order_by']) {
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting functionality
				$query_args['meta_key'] = '_price';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'ASC';
				break;
			case 'price-desc':
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price sorting functionality
				$query_args['meta_key'] = '_price';
				$query_args['orderby'] = 'meta_value_num';
				$query_args['order'] = 'DESC';
				break;
			default:
				$query_args['orderby'] = 'date';
				$query_args['order'] = 'DESC';
				break;
		}

		// Filter by category
		if (!empty($atts['category'])) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering functionality
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		// Exclude products
		if (!empty($atts['exclude_ids'])) {
			$exclude_ids = array_map('absint', explode(',', $atts['exclude_ids']));
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Required for excluding specific products, user-controlled limit
			$query_args['post__not_in'] = $exclude_ids;
		}

		$query = new \WP_Query($query_args);
		$products = array();

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$product = wc_get_product(get_the_ID());
				if ($product) {
					$products[] = $product;
				}
			}
			wp_reset_postdata();
		}

		return $products;
	}

	/**
	 * Render output
	 */
	private function render_output($unique_id, $atts, $products, $total_pages) {
		$gradients = array(
			'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
			'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
			'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
			'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
			'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
			'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
		);
		?>
		<div class="shortcodeglut-book-flip-wrapper" id="<?php echo esc_attr($unique_id); ?>" data-total-pages="<?php echo esc_attr($total_pages); ?>">
			<div class="shortcodeglut-book-container">
				<!-- Left Intro Page (shown initially) -->
				<div class="shortcodeglut-left-intro">
					<div class="shortcodeglut-intro-content">
						<div class="shortcodeglut-intro-icon">📖</div>
						<div class="shortcodeglut-intro-title">Product Catalog</div>
						<div class="shortcodeglut-intro-subtitle">Click Next to browse products</div>
					</div>
				</div>

				<!-- Book Spine -->
				<div class="shortcodeglut-book-spine">
					<div class="spine-text">PRODUCTS</div>
				</div>

				<!-- Product Pages (stacked on right) -->
				<?php foreach ($products as $index => $product) : ?>
					<?php
					$page_num = $index + 1;
					$gradient = $gradients[$index % count($gradients)];
					?>
					<?php $this->render_product_page($product, $atts, $page_num, $gradient); ?>
				<?php endforeach; ?>
			</div>

			<div class="shortcodeglut-book-nav">
				<button class="shortcodeglut-nav-btn shortcodeglut-nav-prev" disabled>
					<span class="nav-arrow">←</span>
					<span class="nav-text">Previous</span>
				</button>
				<div class="shortcodeglut-page-indicator">
					<span class="current-page">1</span> / <span class="total-pages"><?php echo esc_html($total_pages); ?></span>
				</div>
				<button class="shortcodeglut-nav-btn shortcodeglut-nav-next">
					<span class="nav-text">Next</span>
					<span class="nav-arrow">→</span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single product page (both sides show details)
	 */
	private function render_product_page($product, $atts, $page_num, $gradient) {
		$product_id = $product->get_id();
		$permalink = get_permalink($product_id);
		?>
		<div class="shortcodeglut-page-right" data-page="<?php echo esc_attr($page_num); ?>" style="z-index: <?php echo esc_attr(100 - $page_num); ?>;">
			<!-- Front (shown on right - with product details) -->
			<div class="shortcodeglut-page-front">
				<div class="shortcodeglut-page-cover" style="background: <?php echo esc_attr($gradient); ?>;">
					<div class="shortcodeglut-page-number"><?php echo esc_html($page_num); ?></div>
					<div class="shortcodeglut-page-title"><?php echo esc_html($product->get_name()); ?></div>
					<div class="shortcodeglut-page-icon">
						<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
					</div>
					<?php if ($atts['show_price']): ?>
						<div class="shortcodeglut-page-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
					<?php endif; ?>
					<div class="shortcodeglut-page-desc"><?php echo esc_html($this->get_product_description($product)); ?></div>
					<?php if ($atts['show_button']): ?>
						<a href="<?php echo esc_url($permalink); ?>" class="shortcodeglut-page-btn"><?php echo esc_html($atts['button_text']); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Back (shown on left after flip - with product details) -->
			<div class="shortcodeglut-page-back">
				<div class="shortcodeglut-page-content">
					<div class="shortcodeglut-back-number"><?php echo esc_html($page_num); ?></div>
					<div class="shortcodeglut-back-title"><?php echo esc_html($product->get_name()); ?></div>
					<div class="shortcodeglut-back-icon">
						<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
					</div>
					<?php if ($atts['show_price']): ?>
						<div class="shortcodeglut-page-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
					<?php endif; ?>
					<div class="shortcodeglut-page-desc"><?php echo esc_html($this->get_product_description($product)); ?></div>
					<?php if ($atts['show_button']): ?>
						<a href="<?php echo esc_url($permalink); ?>" class="shortcodeglut-page-btn"><?php echo esc_html($atts['button_text']); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get product description
	 */
	private function get_product_description($product) {
		$description = $product->get_short_description();
		if (empty($description)) {
			$description = $product->get_description();
		}
		if (empty($description)) {
			$description = __('Premium product for your creative projects.', 'shortcodeglut');
		}
		return wp_trim_words($description, 20, '...');
	}
}
