<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

class ShortcodeHandler {
    private static $instance = null;
    
    public function __construct() {
        // Register shortcode
        add_shortcode('shopglut_template', array($this, 'renderTemplateShortcode'));
    }
    
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
     * Render template shortcode
     */
    public function renderTemplateShortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'product_id' => get_the_ID(),
        ), $atts, 'shopglut_template');
        
        if (empty($atts['id'])) {
            return '<p class="shopglut-error">' . esc_html__('Template ID is required.', 'shortcodeglut') . '</p>';
        }
        
        // Get template by template_id
        $template = WooTemplatesEntity::get_template_by_template_id($atts['id']);
        
        if (!$template) {
            return '<p class="shopglut-error">' . esc_html__('Template not found.', 'shortcodeglut') . '</p>';
        }
        
        // Get product
        $product_id = absint($atts['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return '<p class="shopglut-error">' . esc_html__('Product not found.', 'shortcodeglut') . '</p>';
        }
        
        // Process template tags
        $html = $this->processTemplateTags($template['template_html'], $product);
        $css = $template['template_css'];
        
        // Generate unique ID for this template instance
        $template_instance_id = 'shopglut-template-' . $template['id'] . '-' . uniqid();
        
        // Return template with CSS
        return sprintf(
            '<style>%s</style><div id="%s" class="shopglut-template">%s</div>',
            $css,
            esc_attr($template_instance_id),
            $html
        );
    }
    
    /**
     * Process template tags and replace with actual product data
     */
    private function processTemplateTags($html, $product) {
        // Product information tags
        $replacements = array(
            '[product_title]' => $product->get_name(),
            '[product_price]' => $product->get_price_html(),
            '[product_regular_price]' => wc_price($product->get_regular_price()),
            '[product_sale_price]' => $product->is_on_sale() ? wc_price($product->get_sale_price()) : '',
            '[product_short_description]' => $product->get_short_description(),
            '[product_description]' => $product->get_description(),
            '[product_sku]' => $product->get_sku(),
            '[product_stock]' => $product->is_in_stock() ? 
                '<span class="in-stock">' . esc_html__('In Stock', 'shortcodeglut') . '</span>' : 
                '<span class="out-of-stock">' . esc_html__('Out of Stock', 'shortcodeglut') . '</span>',
        );
        
        // Product image
        if (strpos($html, '[product_image]') !== false) {
            $image = $product->get_image_id() ? 
                wp_get_attachment_image($product->get_image_id(), 'woocommerce_single') : 
                wc_placeholder_img('woocommerce_single');
            $replacements['[product_image]'] = $image;
        }
        
        // Product gallery
        if (strpos($html, '[product_gallery]') !== false) {
            $gallery_html = '';
            $attachment_ids = $product->get_gallery_image_ids();
            
            if (!empty($attachment_ids)) {
                $gallery_html .= '<div class="shopglut-product-gallery">';
                foreach ($attachment_ids as $attachment_id) {
                    $gallery_html .= wp_get_attachment_image($attachment_id, 'woocommerce_thumbnail');
                }
                $gallery_html .= '</div>';
            }
            
            $replacements['[product_gallery]'] = $gallery_html;
        }
        
        // Categories
        if (strpos($html, '[product_categories]') !== false) {
            $categories = get_the_terms($product->get_id(), 'product_cat');
            $categories_html = '';
            
            if (!empty($categories) && !is_wp_error($categories)) {
                $categories_html = '<span class="shopglut-product-categories">';
                $cat_links = array();
                
                foreach ($categories as $category) {
                    $cat_links[] = '<a href="' . esc_url(get_term_link($category)) . '">' . esc_html($category->name) . '</a>';
                }
                
                $categories_html .= implode(', ', $cat_links);
                $categories_html .= '</span>';
            }
            
            $replacements['[product_categories]'] = $categories_html;
        }
        
        // Tags
        if (strpos($html, '[product_tags]') !== false) {
            $tags = get_the_terms($product->get_id(), 'product_tag');
            $tags_html = '';
            
            if (!empty($tags) && !is_wp_error($tags)) {
                $tags_html = '<span class="shopglut-product-tags">';
                $tag_links = array();
                
                foreach ($tags as $tag) {
                    $tag_links[] = '<a href="' . esc_url(get_term_link($tag)) . '">' . esc_html($tag->name) . '</a>';
                }
                
                $tags_html .= implode(', ', $tag_links);
                $tags_html .= '</span>';
            }
            
            $replacements['[product_tags]'] = $tags_html;
        }
        
        // Buttons
        if (strpos($html, '[btn_cart]') !== false) {
            $cart_button = sprintf(
                '<a href="%s" class="button shopglut-add-to-cart %s" %s>%s</a>',
                esc_url($product->add_to_cart_url()),
                $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button ajax_add_to_cart' : '',
                $product->is_purchasable() && $product->is_in_stock() ? 'data-product_id="' . esc_attr($product->get_id()) . '" data-product_sku="' . esc_attr($product->get_sku()) . '"' : '',
                esc_html($product->is_purchasable() && $product->is_in_stock() ? __('Add to cart', 'shortcodeglut') : __('Read more', 'shortcodeglut'))
            );
            $replacements['[btn_cart]'] = $cart_button;
        }
        
        if (strpos($html, '[btn_view]') !== false) {
            $view_button = sprintf(
                '<a href="%s" class="button shopglut-view-product">%s</a>',
                esc_url(get_permalink($product->get_id())),
                esc_html__('View product', 'shortcodeglut')
            );
            $replacements['[btn_view]'] = $view_button;
        }
        
        // Rating
        if (strpos($html, '[product_rating]') !== false) {
            $rating_html = wc_get_rating_html($product->get_average_rating());
            $replacements['[product_rating]'] = $rating_html;
        }
        
        if (strpos($html, '[product_rating_count]') !== false) {
            $rating_count = $product->get_rating_count();
            $rating_count_html = $rating_count > 0 ? 
                '<span class="shopglut-rating-count">(' . $rating_count . ')</span>' : 
                '';
            $replacements['[product_rating_count]'] = $rating_count_html;
        }
        
        // Attributes
        if (strpos($html, '[product_attributes]') !== false) {
            $attributes = $product->get_attributes();
            $attributes_html = '';
            
            if (!empty($attributes)) {
                $attributes_html = '<div class="shopglut-product-attributes">';
                
                foreach ($attributes as $attribute) {
                    if ($attribute->get_visible()) {
                        $attribute_name = wc_attribute_label($attribute->get_name());
                        $values = array();
                        
                        if ($attribute->is_taxonomy()) {
                            $attribute_terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), array('fields' => 'all'));
                            foreach ($attribute_terms as $term) {
                                $values[] = $term->name;
                            }
                        } else {
                            $values = $attribute->get_options();
                        }
                        
                        $attributes_html .= '<div class="shopglut-product-attribute">';
                        $attributes_html .= '<span class="attribute-label">' . esc_html($attribute_name) . ': </span>';
                        $attributes_html .= '<span class="attribute-value">' . esc_html(implode(', ', $values)) . '</span>';
                        $attributes_html .= '</div>';
                    }
                }
                
                $attributes_html .= '</div>';
            }
            
            $replacements['[product_attributes]'] = $attributes_html;
        }
        
        // Dimensions
        if (strpos($html, '[product_dimensions]') !== false) {
            $dimensions = $product->has_dimensions() ? 
                wc_format_dimensions($product->get_dimensions(false)) : 
                '';
            $replacements['[product_dimensions]'] = $dimensions ? 
                '<span class="shopglut-dimensions">' . $dimensions . '</span>' : 
                '';
        }
        
        // Weight
        if (strpos($html, '[product_weight]') !== false) {
            $weight = $product->get_weight();
            $replacements['[product_weight]'] = $weight ? 
                '<span class="shopglut-weight">' . wc_format_weight($weight) . '</span>' : 
                '';
        }
        
        // Replace all tags
        foreach ($replacements as $tag => $replacement) {
            $html = str_replace($tag, $replacement, $html);
        }
        
        return $html;
    }
}