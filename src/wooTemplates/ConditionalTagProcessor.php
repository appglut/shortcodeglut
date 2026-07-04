<?php
namespace Shortcodeglut\wooTemplates;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conditional Template Tag Processor
 * Processes conditional template tags like [is_new:content]
 */
class ConditionalTagProcessor {

    /**
     * Process conditional template tags in HTML content
     *
     * @param string $content The template content with tags
     * @param \WC_Product $product The WooCommerce product
     * @return string Processed content with tags replaced
     */
    public static function process($content, $product) {
        return self::process_with_image_size($content, $product, 'woocommerce_thumbnail');
    }

    /**
     * Process conditional template tags in HTML content with custom image size
     *
     * @param string $content The template content with tags
     * @param \WC_Product $product The WooCommerce product
     * @param string $image_size The WordPress image size to use
     * @return string Processed content with tags replaced
     */
    public static function process_with_image_size($content, $product, $image_size = 'woocommerce_thumbnail') {
        if (empty($content) || !$product) {
            return $content;
        }

        // Process basic template tags first
        $content = self::process_basic_tags($content, $product, $image_size);

        // Process conditional tags iteratively until no more changes
        // This handles nested conditionals like [is_in_stock: [is_new: ...] ]
        $max_iterations = 10; // Prevent infinite loops
        $iteration = 0;
        $previous_content = '';
        while ($content !== $previous_content && $iteration < $max_iterations) {
            $previous_content = $content;
            $content = self::process_conditional_tags($content, $product);
            $iteration++;
        }

        // Process special tags: categories, rating, discount badge
        $content = self::process_categories($content, $product);
        $content = self::process_rating($content, $product);
        $content = self::process_discount_badge($content, $product);

        // Process translation tags
        $content = self::process_translations($content);

        return $content;
    }

    /**
     * Process basic template tags (non-conditional)
     */
    private static function process_basic_tags($content, $product, $image_size = 'woocommerce_thumbnail') {
        if (!$product) {
            return $content;
        }

        $product_id = $product->get_id();

        // Get placeholder image URL - use the correct WooCommerce function
        $placeholder_url = '';
        if (function_exists('wc_placeholder_img_src')) {
            $placeholder_url = wc_placeholder_img_src();
        }
        if (empty($placeholder_url)) {
            $placeholder_url = WC()->plugin_url() . '/assets/images/placeholder.png';
        }

        // Get image URL with the specified size
        $image_url = get_the_post_thumbnail_url($product_id, $image_size);
        if (empty($image_url)) {
            $image_url = $placeholder_url;
        }

        // Get large image URL for 800x600
        $large_image_url = get_the_post_thumbnail_url($product_id, 'large');
        if (empty($large_image_url)) {
            $large_image_url = $placeholder_url;
        }

        $tags = array(
            '[product_id]' => (string) $product_id,
            '[product_permalink]' => esc_url($product->get_permalink()),
            '[product_title]' => esc_html($product->get_title()),
            '[product_price]' => $product->get_price_html(),
            '[product_sale_price]' => $product->get_sale_price() ? wc_price($product->get_sale_price()) : '',
            '[product_regular_price]' => $product->get_regular_price() ? wc_price($product->get_regular_price()) : '',
            '[product_short_description]' => wp_trim_words($product->get_short_description(), 20, '...'),
            '[product_description]' => wp_trim_words($product->get_short_description(), 20, '...'),
            '[add_to_cart_url]' => esc_url($product->add_to_cart_url()),
            '[cart_url]' => esc_url(wc_get_cart_url()),
            '[product_image_url_800x600]' => esc_url($large_image_url),
            '[product_image_url_full]' => esc_url($large_image_url),
            '[product_image_url]' => esc_url($image_url),
            '[product_image]' => $product->get_image($image_size),
            '[sku]' => $product->get_sku() ?: '',
            '[stock_status]' => $product->get_stock_status() ?: '',
            '[stock_quantity]' => $product->get_stock_quantity() !== null ? (string) $product->get_stock_quantity() : '',
        );

        foreach ($tags as $tag => $value) {
            $content = str_replace($tag, (string) $value, $content);
        }

        return $content;
    }

    /**
     * Process conditional template tags
     * Format: [condition:content]
     */
    private static function process_conditional_tags($content, $product) {
        if (!$product) {
            return $content;
        }

        // Match conditional tags: [condition_name:content]
        // Use lookahead to match until we find a closing bracket not followed by another opening bracket
        // This handles nested tags like [is_on_sale: [product_badge_sale] ]
        $pattern = '/\[([a-zA-Z_][a-zA-Z0-9_]*):((?:[^[\]]|\[[^\]]*\])*)\]/';

        $content = preg_replace_callback($pattern, function($matches) use ($product) {
            $condition = $matches[1];
            $content_to_show = $matches[2]; // Don't trim - preserve spacing

            // Check if condition is met
            if (self::check_condition($condition, $product)) {
                // Process translation tags inside the conditional content
                $content_to_show = preg_replace_callback('/\[t:([^\]]+)\]/', function($t_matches) {
                    return trim($t_matches[1]);
                }, $content_to_show);
                return $content_to_show;
            }

            return ''; // Return empty if condition not met
        }, $content);

        return $content;
    }

    /**
     * Check if a condition is met for the given product
     */
    private static function check_condition($condition, $product) {
        if (!$product) {
            return false;
        }

        $product_id = $product->get_id();

        switch ($condition) {
            case 'is_new':
                $is_new = get_post_meta($product_id, '_is_new', true);
                return !empty($is_new);

            case 'is_not_new':
                $is_new = get_post_meta($product_id, '_is_new', true);
                return empty($is_new);

            case 'is_on_sale':
                return $product->is_on_sale();

            case 'is_not_on_sale':
                return !$product->is_on_sale();

            case 'is_in_stock':
                return $product->is_in_stock();

            case 'is_out_of_stock':
                return !$product->is_in_stock();

            case 'is_featured':
                $is_featured = get_post_meta($product_id, '_featured', true);
                return !empty($is_featured);

            case 'is_not_featured':
                $is_featured = get_post_meta($product_id, '_featured', true);
                return empty($is_featured);

            case 'has_categories':
                $categories = get_the_terms($product_id, 'product_cat');
                return !empty($categories) && !is_wp_error($categories);

            case 'has_no_categories':
                $categories = get_the_terms($product_id, 'product_cat');
                return empty($categories) || is_wp_error($categories);

            case 'has_rating':
                return $product->get_average_rating() > 0;

            case 'has_no_rating':
                return $product->get_average_rating() <= 0;

            case 'has_description':
                return !empty($product->get_short_description()) || !empty($product->get_description());

            case 'has_no_description':
                return empty($product->get_short_description()) && empty($product->get_description());

            case 'has_sale_price':
                return $product->get_sale_price() > 0;

            case 'has_regular_price':
                return $product->get_regular_price() > 0;

            default:
                // Allow filtering for custom conditions
                return apply_filters('shortcodeglut_check_condition', false, $condition, $product);
        }
    }

    /**
     * Process product categories tag
     */
    public static function process_categories($content, $product) {
        if (strpos($content, '[product_categories]') === false) {
            return $content;
        }

        $product_id = $product->get_id();
        $categories = get_the_terms($product_id, 'product_cat');

        if (empty($categories) || is_wp_error($categories)) {
            return str_replace('[product_categories]', '', $content);
        }

        $categories_html = '';
        $category_links = array();
        foreach ($categories as $category) {
            $category_links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(get_term_link($category)),
                esc_html($category->name)
            );
        }
        $categories_html = implode(', ', $category_links);


        return str_replace('[product_categories]', $categories_html, $content);
    }

    /**
     * Process rating stars tag
     */
    public static function process_rating($content, $product) {
        if (strpos($content, '[product_rating]') === false) {
            return $content;
        }

        $rating = $product->get_average_rating();
        $rating_count = $product->get_rating_count();

        if ($rating_count <= 0) {
            return str_replace('[product_rating]', '', $content);
        }

        $stars_html = '<div class="ratings">';
        $stars_html .= '<span class="spr-badge" data-rating="' . esc_attr($rating) . '">';
        $stars_html .= '<span class="spr-starrating spr-badge-starrating">';

        for ($i = 1; $i <= 5; $i++) {
            if ($i <= round($rating)) {
                $stars_html .= '<i class="fa-solid fa-star"></i>';
            } else {
                $stars_html .= '<i class="fa-regular fa-star"></i>';
            }
        }

        $stars_html .= '</span></span></div>';

        return str_replace('[product_rating]', $stars_html, $content);
    }

    /**
     * Process discount badge tag
     */
    public static function process_discount_badge($content, $product) {
        if (strpos($content, '[product_badge_sale]') === false) {
            return $content;
        }

        if (!$product->is_on_sale()) {
            return str_replace('[product_badge_sale]', '', $content);
        }

        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();

        if ($regular_price > 0 && $sale_price > 0) {
            $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
            $badge_html = sprintf(
                '<span class="discount-badge product-label">-%d%%</span>',
                $discount
            );
            return str_replace('[product_badge_sale]', $badge_html, $content);
        }

        return str_replace('[product_badge_sale]', '', $content);
    }

    /**
     * Process translation tags
     * Handles [t:text] tags and translates common strings
     *
     * @param string $content The template content
     * @return string Content with translations applied
     */
    public static function process_translations($content) {
        // Process [t:text] tags - always return the text even if translation fails
        $content = preg_replace_callback('/\[t:([^\]]+)\]/', function($matches) {
            $text = trim($matches[1]);
            // Simply return the text directly without translation for now
            // This ensures the text is always displayed
            return $text;
        }, $content);

        return $content;
    }
}
