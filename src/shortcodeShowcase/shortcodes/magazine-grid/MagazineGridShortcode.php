<?php
/**
 * WooCommerce Magazine Grid Shortcode Handler
 *
 * Handles [shortcodeglut_magazine] shortcode to display products
 * in a magazine-style grid layout with varying item sizes
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\MagazineGrid;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MagazineGridShortcode {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Shortcode slug
     */
    const SHORTCODE_SLUG = 'shortcodeglut_magazine';

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
        add_shortcode(self::SHORTCODE_SLUG, array($this, 'render_shortcode'));
        add_action('wp_ajax_shortcodeglut_magazine_load', array($this, 'ajax_load_products'));
        add_action('wp_ajax_nopriv_shortcodeglut_magazine_load', array($this, 'ajax_load_products'));
        add_action('wp_ajax_shortcodeglut_magazine_load_more', array($this, 'ajax_load_more'));
        add_action('wp_ajax_nopriv_shortcodeglut_magazine_load_more', array($this, 'ajax_load_more'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only enqueue if shortcode is present on page
        global $post;
        $should_enqueue = false;

        // Check post content for shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, self::SHORTCODE_SLUG)) {
            $should_enqueue = true;
        }

        // Also check if we're in an AJAX request for this shortcode
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in AJAX handlers, this is just for enqueuing assets
        if (!$should_enqueue && isset($_POST['action'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Simple comparison for asset enqueueing
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in AJAX handlers
            $action = sanitize_text_field(wp_unslash($_POST['action']));
            if ($action === 'shortcodeglut_magazine_load_more' || $action === 'shortcodeglut_magazine_load') {
                $should_enqueue = true;
            }
        }

        if ($should_enqueue) {
            wp_enqueue_style(
                'shortcodeglut-magazine',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/magazine-grid/assets/css/magazine-grid.css',
                array(),
                defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0'
            );

            wp_enqueue_script(
                'shortcodeglut-magazine',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/magazine-grid/assets/js/magazine-grid.js',
                array('jquery'),
                defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0',
                true
            );

            wp_localize_script('shortcodeglut-magazine', 'shortcodeglut_magazine', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shortcodeglut_magazine_nonce'),
                'strings' => array(
                    'load_more' => __('Load More', 'shortcodeglut'),
                    'loading' => __('Loading...', 'shortcodeglut'),
                )
            ));
        }
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_shortcode($atts) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return $this->render_woocommerce_inactive();
        }

        // Check if this is a REST request
        if ($this->is_rest_request()) {
            return $this->render_rest_placeholder();
        }

        $atts = shortcode_atts($this->get_default_atts(), $atts, self::SHORTCODE_SLUG);

        // Sanitize attributes
        $atts = $this->sanitize_atts($atts);

        // Get products
        $products = $this->get_products($atts);

        ob_start();
        $this->render_output($atts, $products);
        return ob_get_clean();
    }

    /**
     * Get default attributes
     */
    private function get_default_atts() {
        return array(
            'title' => '',
            'limit' => '12',
            'category' => '',
            'ids' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'columns' => '4',
            'gap' => '20',
            'layout' => 'masonry',
            'show_overlay' => 'true',
            'show_price' => 'true',
            'show_category' => 'true',
            'pagination' => 'false',
            'hover_effect' => 'zoom',
        );
    }

    /**
     * Sanitize attributes
     */
    private function sanitize_atts($atts) {
        $sanitized = array();

        foreach ($atts as $key => $value) {
            switch ($key) {
                case 'limit':
                case 'columns':
                case 'gap':
                    $sanitized[$key] = absint($value);
                    break;
                case 'show_overlay':
                case 'show_price':
                case 'show_category':
                case 'pagination':
                    $sanitized[$key] = rest_sanitize_boolean($value);
                    break;
                case 'layout':
                    $sanitized[$key] = in_array($value, array('masonry', 'grid', 'balanced')) ? $value : 'masonry';
                    break;
                case 'hover_effect':
                    $sanitized[$key] = in_array($value, array('zoom', 'slide', 'fade', 'none')) ? $value : 'zoom';
                    break;
                case 'orderby':
                    $sanitized[$key] = in_array($value, array('date', 'price', 'popularity', 'rating', 'rand', 'title')) ? $value : 'date';
                    break;
                case 'order':
                    $sanitized[$key] = in_array($value, array('ASC', 'DESC')) ? $value : 'DESC';
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Get products query
     */
    private function get_products($atts) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
        );

        // Filter by specific IDs
        if (!empty($atts['ids'])) {
            $ids = array_map('trim', explode(',', $atts['ids']));
            $ids = array_filter($ids, 'is_numeric');
            if (!empty($ids)) {
                $args['post__in'] = $ids;
                $args['posts_per_page'] = count($ids);
            }
        }

        // Filter by category
        if (!empty($atts['category'])) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering, query is optimized with indexed fields.
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['category']),
                )
            );
        }

        $products = wc_get_products($args);

        // Assign item sizes based on layout
        $sized_products = array();
        foreach ($products as $index => $product) {
            $sized_products[] = array(
                'product' => $product,
                'size' => $this->get_item_size($index, $atts['layout'])
            );
        }

        return $sized_products;
    }

    /**
     * Get item size based on position and layout
     */
    private function get_item_size($index, $layout) {
        switch ($layout) {
            case 'masonry':
                if ($index === 0) {
                    return 'hero'; // First item is large
                } elseif (in_array($index, array(1, 2))) {
                    return 'large';
                } elseif (in_array($index, array(3, 4, 5, 6))) {
                    return 'medium';
                }
                return 'small';

            case 'balanced':
                if ($index === 0) {
                    return 'hero';
                } elseif (in_array($index, array(1, 4, 5))) {
                    return 'large';
                }
                return 'medium';

            case 'grid':
            default:
                return 'medium';
        }
    }

    /**
     * Render output HTML
     */
    private function render_output($atts, $products) {
        $wrapper_classes = array(
            'shortcodeglut-magazine-wrapper',
            'shortcodeglut-magazine-' . $atts['layout'],
            'shortcodeglut-magazine-' . $atts['columns'] . '-cols',
            'shortcodeglut-magazine-hover-' . $atts['hover_effect'],
        );

        $container_style = '';
        if ($atts['gap']) {
            $container_style = sprintf('gap: %dpx;', $atts['gap']);
        }

        $data_attrs = array(
            'data-layout' => $atts['layout'],
            'data-columns' => $atts['columns'],
            'data-pagination' => $atts['pagination'] ? 'true' : 'false',
            'data-show-overlay' => $atts['show_overlay'] ? 'true' : 'false',
            'data-show-price' => $atts['show_price'] ? 'true' : 'false',
            'data-show-category' => $atts['show_category'] ? 'true' : 'false',
            'data-limit' => $atts['limit'],
        );
        ?>

        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
             style="<?php echo esc_attr($container_style); ?>"
             <?php echo wp_kses($this->build_data_attributes($data_attrs), array(
                 'data-layout' => true,
                 'data-columns' => true,
                 'data-pagination' => true,
                 'data-show-overlay' => true,
                 'data-show-price' => true,
                 'data-show-category' => true,
                 'data-limit' => true,
             )); ?>>
            <?php if (!empty($atts['title'])) : ?>
                <h3 class="shortcodeglut-magazine-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <div class="shortcodeglut-magazine-grid">
                <?php foreach ($products as $item) : ?>
                    <?php $this->render_grid_item($item['product'], $item['size'], $atts); ?>
                <?php endforeach; ?>
            </div>

            <?php if ($atts['pagination'] === true) : ?>
                <div class="shortcodeglut-magazine-pagination">
                    <a href="#" class="shortcodeglut-magazine-load-more">
                        <?php esc_html_e('Load More', 'shortcodeglut'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Modal for product details -->
            <div class="shortcodeglut-magazine-modal">
                <div class="shortcodeglut-magazine-modal-backdrop"></div>
                <div class="shortcodeglut-magazine-modal-inner">
                    <button class="shortcodeglut-magazine-modal-close">&times;</button>
                    <div class="shortcodeglut-magazine-modal-content"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render single grid item
     */
    private function render_grid_item($product, $size, $atts) {
        $item_classes = array(
            'shortcodeglut-magazine-item',
            'shortcodeglut-magazine-item-' . $size,
        );

        $product_id = $product ? $product->get_id() : 0;
        ?>

        <div class="<?php echo esc_attr(implode(' ', $item_classes)); ?>"
             data-product-id="<?php echo esc_attr($product_id); ?>">

            <?php if ($product) : ?>
                <div class="shortcodeglut-magazine-item-inner">
                    <?php echo wp_kses_post($this->get_product_image($product, $size)); ?>

                    <?php if ($atts['show_overlay'] === true) : ?>
                        <div class="shortcodeglut-magazine-overlay">
                            <?php if ($atts['show_category'] === true) : ?>
                                <?php echo wp_kses_post($this->get_product_categories($product)); ?>
                            <?php endif; ?>

                            <div class="shortcodeglut-magazine-title">
                                <?php echo esc_html($this->truncate_text($product->get_title(), 60)); ?>
                            </div>

                            <?php if ($atts['show_price'] === true) : ?>
                                <div class="shortcodeglut-magazine-price">
                                    <?php echo wp_kses_post($product->get_price_html()); ?>
                                </div>
                            <?php endif; ?>

                            <a href="<?php echo esc_url($product->get_permalink()); ?>"
                               class="shortcodeglut-magazine-link">
                                <span class="screen-reader-text"><?php esc_html_e('View Product', 'shortcodeglut'); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="shortcodeglut-magazine-placeholder">
                    <span class="placeholder-text">No Product</span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get product image HTML based on size
     */
    private function get_product_image($product, $size) {
        if (!$product) {
            return '';
        }

        $image_size = 'medium';
        switch ($size) {
            case 'hero':
                $image_size = 'large';
                break;
            case 'large':
                $image_size = 'medium_large';
                break;
            case 'small':
                $image_size = 'thumbnail';
                break;
        }

        $image_id = $product->get_image_id();
        if ($image_id) {
            return wp_get_attachment_image($image_id, $image_size, false, array(
                'class' => 'shortcodeglut-magazine-img',
                'loading' => 'lazy'
            ));
        }

        // Fallback to placeholder
        return '<img src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr($product->get_title()) . '" class="shortcodeglut-magazine-img placeholder-img" />';
    }

    /**
     * Get product categories
     */
    private function get_product_categories($product) {
        $cats = get_the_terms($product->get_id(), 'product_cat');
        if ($cats && !is_wp_error($cats)) {
            $cat = array_shift($cats);
            return '<span class="shortcodeglut-magazine-category">' . esc_html($cat->name) . '</span>';
        }
        return '';
    }

    /**
     * Truncate text
     */
    private function truncate_text($text, $max_length) {
        if (mb_strlen($text) <= $max_length) {
            return $text;
        }
        return mb_substr($text, 0, $max_length) . '...';
    }

    /**
     * AJAX: Load product details
     */
    public function ajax_load_products() {
        check_ajax_referer('shortcodeglut_magazine_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error('Product not found');
        }

        ob_start();
        ?>
        <div class="shortcodeglut-magazine-modal-product">
            <?php echo wp_kses_post($this->get_product_image($product, 'large')); ?>
            <h4><?php echo esc_html($product->get_title()); ?></h4>
            <?php echo wp_kses_post($this->get_product_categories($product)); ?>
            <p class="price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
            <p><?php echo wp_kses_post($product->get_short_description()); ?></p>
            <a href="<?php echo esc_url($product->get_permalink()); ?>" class="button">
                <?php esc_html_e('View Product', 'shortcodeglut'); ?>
            </a>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Load more products (pagination)
     */
    public function ajax_load_more() {
        check_ajax_referer('shortcodeglut_magazine_nonce', 'nonce');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 2;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $layout = isset($_POST['layout']) ? sanitize_text_field(wp_unslash($_POST['layout'])) : 'masonry';
        $show_overlay = isset($_POST['show_overlay']) ? rest_sanitize_boolean($_POST['show_overlay']) : true;
        $show_price = isset($_POST['show_price']) ? rest_sanitize_boolean($_POST['show_price']) : true;
        $show_category = isset($_POST['show_category']) ? rest_sanitize_boolean($_POST['show_category']) : true;

        $atts = array(
            'limit' => $limit,
            'category' => $category,
            'layout' => $layout,
            'show_overlay' => $show_overlay,
            'show_price' => $show_price,
            'show_category' => $show_category,
        );

        // Get products for this page
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'paged' => $page,
            'post_status' => 'publish',
        );

        if (!empty($category)) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering, query is optimized with indexed fields.
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $category,
                )
            );
        }

        $products = wc_get_products($args);

        $sized_products = array();
        $offset = ($page - 1) * $limit;
        foreach ($products as $index => $product) {
            $sized_products[] = array(
                'product' => $product,
                'size' => $this->get_item_size($offset + $index, $layout)
            );
        }

        ob_start();
        foreach ($sized_products as $item) {
            $this->render_grid_item($item['product'], $item['size'], $atts);
        }
        $html = ob_get_clean();

        $has_more = count($products) >= $limit;

        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more,
            'next_page' => $page + 1
        ));
    }

    /**
     * Render WooCommerce inactive message
     */
    private function render_woocommerce_inactive() {
        return '<div class="shortcodeglut-error">' . esc_html__('WooCommerce is not active. Please activate WooCommerce to use this shortcode.', 'shortcodeglut') . '</div>';
    }

    /**
     * Render REST API placeholder
     */
    private function render_rest_placeholder() {
        return '<div class="shortcodeglut-rest-placeholder">' .
               esc_html__('[Magazine Grid Shortcode - Product Grid]',
               'shortcodeglut') .
               '</div>';
    }

    /**
     * Check if current request is REST API
     */
    private function is_rest_request() {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized with sanitize_text_field() on next line.
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_uri = sanitize_text_field($request_uri);

        if (!empty($request_uri) && strpos($request_uri, '/wp-json/') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Build data attributes string
     */
    private function build_data_attributes($attrs) {
        $output = array();
        foreach ($attrs as $key => $value) {
            $output[] = sprintf('%s="%s"', $key, esc_attr($value));
        }
        return implode(' ', $output);
    }
}
