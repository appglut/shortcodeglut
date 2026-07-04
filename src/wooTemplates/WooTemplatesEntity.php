<?php
namespace Shortcodeglut\wooTemplates;

if (!defined('ABSPATH')) {
    exit;
}

use Shortcodeglut\wooTemplates\TemplateLoader;

class WooTemplatesEntity {

    public static function getDefaultTemplates() {
        TemplateLoader::init();
        return TemplateLoader::get_all_templates();
    }

    public static function getTemplateHtml($template_id) {
        TemplateLoader::init();
        return TemplateLoader::get_template_html($template_id);
    }

    public static function getTemplateCss($template_id) {
        TemplateLoader::init();
        return TemplateLoader::get_template_css($template_id);
    }

    public static function getPreviewHtml($template_id) {
        TemplateLoader::init();
        return TemplateLoader::get_preview_html($template_id);
    }

    public static function getPreviewCss($template_id) {
        TemplateLoader::init();
        return TemplateLoader::get_preview_css($template_id);
    }

    public static function retrieveAllCount() {
        TemplateLoader::init();
        $file_templates = TemplateLoader::get_all_templates();

        // Get custom templates from database
        global $wpdb;
        $table = $wpdb->prefix . 'shortcodeglut_woo_templates';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + fixed suffix)
        $db_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");

        return count($file_templates) + (int) $db_count;
    }

    public static function retrieveAll($per_page = 10, $current_page = 1) {
        TemplateLoader::init();
        $templates = TemplateLoader::get_all_templates();

        // Get custom templates from database
        global $wpdb;
        $table = $wpdb->prefix . 'shortcodeglut_woo_templates';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + fixed suffix)
        $db_templates = $wpdb->get_results("SELECT * FROM `{$table}` ORDER BY template_name ASC", ARRAY_A);

        // Add database ID to each file template for consistency
        foreach ($templates as $key => $template) {
            $templates[$key]['id'] = $template['template_id'];
        }

        // Merge database templates (add ID field)
        foreach ($db_templates as $template) {
            $templates[$template['template_id']] = $template;
        }

        // Sort by is_default DESC, then name ASC
        uasort($templates, function($a, $b) {
            if (($a['is_default'] ?? 0) != ($b['is_default'] ?? 0)) {
                return ($b['is_default'] ?? 0) <=> ($a['is_default'] ?? 0);
            }
            return strcasecmp($a['template_name'] ?? '', $b['template_name'] ?? '');
        });

        // Reset array keys to numeric indices for pagination
        $templates = array_values($templates);

        $offset = ($current_page - 1) * $per_page;
        return array_slice($templates, $offset, $per_page);
    }

    public static function retrieveById($id) {
        TemplateLoader::init();
        $file_templates = TemplateLoader::get_all_templates();

        // Check if $id is a string (template_id) or numeric (database id)
        if (is_string($id)) {
            // Look up by template_id in file templates
            foreach ($file_templates as $template) {
                if (isset($template['template_id']) && $template['template_id'] === $id) {
                    $template['id'] = $template['template_id'];
                    return $template;
                }
            }

            // Check database templates by template_id
            global $wpdb;
            $table = $wpdb->prefix . 'shortcodeglut_woo_templates';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + fixed suffix), values are prepared
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE template_id = %s", $id), ARRAY_A);
        } else {
            // Numeric ID - only check database
            global $wpdb;
            $table = $wpdb->prefix . 'shortcodeglut_woo_templates';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + fixed suffix), values are prepared
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $id), ARRAY_A);
        }
    }

    public static function insertDefaultTemplates() {
        // Templates are now file-based, no database insertion needed
        // This method is kept for backwards compatibility
        return 0;
    }


    /**
     * Convert PHP code to template tags
     * This converts WooCommerce PHP code to shortcode template tags
     */
    private static function convert_php_to_template_tags($html) {
        if (empty($html)) {
            return '';
        }

        $original = $html;

        // Step 1: Remove PHP file header
        $html = preg_replace('/<\?php\s*\/\*\*.*?\*\/\s*/s', '', $html);
        $html = preg_replace('/if\s*\(\s*!defined\([\'"]ABSPATH[\'"]\)\s*\)\s*\{[^}]+\}\s*/s', '', $html);
        $html = preg_replace('/global\s+\$product;[^\n]*/', '', $html);
        $html = preg_replace('/exit;[^\n]*/', '', $html);

        // Step 2: Remove variable assignment lines
        $html = preg_replace('/<\?php\s*\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[^;]+;[^\n]*\n*\s*\?>/s', '', $html);
        $html = preg_replace('/^\s*\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[^;]+;[^\n]*$/m', '', $html);
        $html = preg_replace('/<\?php\s*\/\/[^\n]*\n*\s*\?>/s', '', $html);
        $html = preg_replace('/^\s*\/\/[^\n]*$/m', '', $html);

        // Step 3: Simple echo replacements
        $replacements = array(
            '/<\?php\s+echo\s+esc_html\(\s*\$product->get_title\(\s*\)\s*\);\s*\?>/' => '[product_title]',
            '/<\?php\s+echo\s+esc_html\(\s*\$product_title\s*\);\s*\?>/' => '[product_title]',
            '/<\?php\s+echo\s+\$product->get_price_html\(\s*\);\s*\?>/' => '[product_price]',
            '/<\?php\s+echo\s+\$product_price;\s*\?>/' => '[product_price]',
            '/<\?php\s+echo\s+esc_attr\(\s*\$product_id\s*\);\s*\?>/' => '[product_id]',
            '/<\?php\s+echo\s+\$product_id;\s*\?>/' => '[product_id]',
            '/<\?php\s+echo\s+esc_url\(\s*\$product->get_permalink\(\s*\)\s*\);\s*\?>/' => '[product_permalink]',
            '/<\?php\s+echo\s+esc_url\(\s*\$product_link\s*\);\s*\?>/' => '[product_permalink]',
            '/<\?php\s+echo\s+esc_url\(\s*\$product_image_url\s*\);\s*\?>/' => '[product_image_url_800x600]',
            '/<\?php\s+echo\s+\$rating_html;\s*\?>/' => '[product_rating]',
            '/<\?php\s+echo\s+wc_get_rating_html\([^)]*\);\s*\?>/' => '[product_rating]',
            '/<\?php\s+echo\s+wc_price\(\s*\$sale_price\s*\);\s*\?>/' => '[product_sale_price]',
            '/<\?php\s+echo\s+wc_price\(\s*\$regular_price\s*\);\s*\?>/' => '[product_regular_price]',
            '/<\?php\s+echo\s+wc_price\(\s*\$product->get_sale_price\(\s*\)\s*\);\s*\?>/' => '[product_sale_price]',
            '/<\?php\s+echo\s+wc_price\(\s*\$product->get_regular_price\(\s*\)\s*\);\s*\?>/' => '[product_regular_price]',
            '/<\?php\s+echo\s+esc_url\(\s*\$product->add_to_cart_url\(\s*\)\s*\);\s*\?>/' => '[add_to_cart_url]',
            '/<\?php\s+echo\s+esc_url\(\s*wc_get_cart_url\(\s*\)\s*\);\s*\?>/' => '[cart_url]',
        );

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        // Step 4: Handle complex multi-line blocks using callback for better matching

        // Categories - handle if/foreach/endif blocks
        $html = preg_replace_callback(
            '/<\?php\s+if\s*\(\s*\$product_categories\s*\)\s*:\s*\?>.*?<\?php\s+foreach\s*\([^)]+\)\s*\{?\s*\?>.*?<\?php\s+endforeach;\s*\?>.*?<\?php\s+endif;\s*\?>/s',
            function($m) { return '<p class="product-category">[product_categories]</p>'; },
            $html
        );

        // Price box - handle if/else/endif blocks
        $html = preg_replace_callback(
            '/<\?php\s+if\s*\(\s*\$product->is_on_sale\([^)]*\)[^;]*;[^<]*<\?php\s+else\s*:\s*\?>.*?<\?php\s+endif;\s*\?>/s',
            function($m) {
                if (strpos($m[0], 'sale_price') !== false && strpos($m[0], 'regular_price') !== false) {
                    return '[product_price]';
                }
                return '[product_price]';
            },
            $html
        );

        // Add to cart - handle if/else/endif in cart action div
        $html = preg_replace_callback(
            '/<div[^>]*class="[^"]*product-cart-action[^"]*"[^>]*>[^<]*<\?php\s+if\s*\(\s*\$product->is_in_stock[^)]*\)\s*:\s*\?>.*?<a[^>]*add_to_cart[^>]*>.*?<\/a>.*?<\?php\s+else\s*:\s*\?>.*?<\?php\s+endif;\s*\?<\/div>/s',
            function($m) { return '[btn_cart]'; },
            $html
        );

        // Badges - simpler patterns
        $html = preg_replace('/<\?php\s+if\s*\(\s*\$is_new\s*\)\s*:\s*\?>.*?<\?php\s+esc_html_e\([^)]+\);\s*\?><\?php\s+endif;\s*\?>/s', '[product_badge_new]', $html);
        $html = preg_replace('/<\?php\s+if\s*\(\s*\$is_featured\s*\)\s*:\s*\?>.*?<\?php\s+esc_html_e\([^)]+\);\s*\?><\?php\s+endif;\s*\?>/s', '[product_badge_featured]', $html);
        $html = preg_replace('/<\?php\s+if\s*\(\s*\$sale_price[^;]*;[^<]*<\?php\s+\$discount[^;]*;[^<]*<\?php\s+echo[^<]*<\?php\s+endif;\s*\?>/s', '[product_badge_sale]', $html);

        // New badge - direct HTML text
        $html = preg_replace('/<\?php\s+if\s*\(\s*\$is_new\s*\)\s*:\s*\?>\s*<span[^>]*>New<\/span>\s*<\?php\s+endif;\s*\?>/s', '[product_badge_new]', $html);

        // New badge - empty span (when esc_html_e is removed)
        $html = preg_replace('/<span class="new-badge[^"]*"><\/span>/', '[product_badge_new]', $html);

        // Discount badge - floatval condition
        $html = preg_replace('/floatval\([^)]+\)\s*>\s*floatval\([^)]+\)\):/', '', $html);

        // Categories - with span wrapper
        $html = preg_replace_callback(
            '/<\?php\s+if\s*\(\s*\$product_categories\s*\)\s*:\s*\?>[^<]*<span[^>]*>[^<]*<\?php\s+foreach\s*\([^)]+\)\s*\{?\s*\?>[^<]*<a[^>]*href="[^"]*"[^>]*>[^<]*<\/a>[^<]*<\?php\s+endforeach;\s*\?>[^<]*<\/span>[^<]*<\?php\s+endif;\s*\?>/s',
            function($m) { return '<span class="product-category">[product_categories]</span>'; },
            $html
        );

        // Also handle simpler category pattern that might remain
        $html = preg_replace('/<span class="product-category"><a href="">\s*name\)\;\s*\s*<\/a><\/span>/', '<span class="product-category">[product_categories]</span>', $html);

        // Ratings - with for loop
        $html = preg_replace('/<\?php\s+if\s*\([^)]*\)\s*:\s*\?>[^<]*<div[^>]*class="[^"]*ratings[^"]*"[^>]*>[^<]*<span[^>]*>[^<]*<span[^>]*>[^<]*<\?php\s+for\s*\([^)]+\)\s*:\s*\?>[^<]*<\?php\s+endfor;\s*\?>[^<]*<\/span>[^<]*<\/span>[^<]*<\/div>[^<]*<\?php\s+endif;\s*\?>/s', '[product_rating]', $html);

        // Step 5: Remove complex conditionals
        $html = preg_replace('/<\?php\s+if\s*\([^)]+\)\s*\{\s*\?>[^<]*<\?php\s+\}[^<]*\?\>/s', '', $html);
        $html = preg_replace('/if\s*\([^)]+\)\s*\{[^}]*\}/s', '', $html);

        // Step 6: Remove remaining PHP functions
        $html = preg_replace('/<\?php\s+esc_html_e\([^)]+\);\s*\?>/', '', $html);
        $html = preg_replace('/<\?php\s+echo\s+esc_html\(\s*__\([^)]+\)\s*\);\s*\?>/', '', $html);

        // Step 6: Remove remaining PHP tags and keywords
        $html = preg_replace('/<\?php[^>]*>/', '', $html);
        $html = str_replace('?>', '', $html);
        $html = preg_replace('/\s?\b(if|else|elseif|foreach|while|for|endif|endforeach|endwhile|endfor)\b\s*\([^)]*\)\s*\{?\s?/', '', $html);
        $html = preg_replace('/\s?\}\s?/', '', $html);

        // Step 7: Clean up HTML entities
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        $html = str_replace('&lt;', '<', $html);
        $html = str_replace('&gt;', '>', $html);
        $html = str_replace('&quot;', '"', $html);
        $html = str_replace('&amp;', '&', $html);

        // Step 8: Clean up broken fragments
        $html = preg_replace('/\s+>\s+200\)\s+\{/', '', $html);
        $html = preg_replace('/\s+\d+\)\s*:/', '', $html);
        $html = preg_replace('/<span class="new-badge[^"]*">New<\/span>/i', '[product_badge_new]', $html);
        $html = preg_replace('/<span class="new-badge[^"]*"><\/span>/i', '[product_badge_new]', $html);
        $html = preg_replace('/<span class="discount-badge[^"]*">-%<\/span>/i', '[product_badge_sale]', $html);
        $html = preg_replace('/<a href="">\s*name\)\);\s*\s*<\/a>/i', '[product_categories]', $html);
        $html = preg_replace('/<a href="">name\)\;<\/a>/i', '[product_categories]', $html);
        $html = preg_replace('/\s+is_in_stock\([^)]*\)\):/', '', $html);
        $html = preg_replace('/\s+is_on_sale\([^)]*\)\s*&&[^<]*\)\):/', '', $html);
        $html = preg_replace('/add_to_cart_url\(\)\);/', '', $html);
        $html = preg_replace('/\s+\$[^)]*\)\):/', '', $html);

        // Additional cleanup for category links
        $html = preg_replace('/<p class="product-category"><a href="">\s*name\)\;\s*\s*<\/a><\/p>/i', '<p class="product-category">[product_categories]</p>', $html);
        $html = preg_replace('/<span class="product-category"><a href="">\s*name\)\;\s*\s*<\/a><\/span>/i', '<span class="product-category">[product_categories]</span>', $html);

        // Step 9: Clean up whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = trim($html);

        if (empty($html) || strlen($html) < 50) {
            return $original;
        }

        return $html;
    }

    /**
     * Format HTML with proper indentation
     * Makes HTML more readable when editing cloned templates
     */
    private static function format_html($html) {
        if (empty($html)) {
            return '';
        }

        // Remove existing whitespace to start fresh
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);

        // Simple HTML formatting
        $formatted = '';
        $indent = 0;
        $indent_str = '    '; // 4 spaces per indent level

        // Split by HTML tags
        preg_match_all('/<\/?[^>]+>|[^<]+/', $html, $tokens);

        foreach ($tokens[0] as $token) {
            $token = trim($token);
            if (empty($token)) {
                continue;
            }

            // Check if it's a tag
            if (strpos($token, '<') === 0) {
                if (strpos($token, '</') === 0) {
                    // Closing tag - decrease indent
                    $indent = max(0, $indent - 1);
                    $formatted .= str_repeat($indent_str, $indent) . $token . "\n";
                } elseif (substr($token, -2) === '/>') {
                    // Self-closing tag
                    $formatted .= str_repeat($indent_str, $indent) . $token . "\n";
                } else {
                    // Opening tag
                    $formatted .= str_repeat($indent_str, $indent) . $token . "\n";
                    // Increase indent for content inside
                    $indent++;
                }
            } else {
                // Text content
                $formatted .= str_repeat($indent_str, $indent) . $token . "\n";
            }
        }

        return trim($formatted);
    }

    /**
     * Get template by template_id (string identifier)
     */
    public static function get_template_by_template_id($template_id) {
        TemplateLoader::init();
        $templates = TemplateLoader::get_all_templates();

        // Check if template exists in file-based templates
        if (isset($templates[$template_id])) {
            $template = $templates[$template_id];
            // Set id field to template_id for consistency
            $template['id'] = $template_id;
            return $template;
        }

        // Check database templates
        global $wpdb;
        $table = $wpdb->prefix . 'shortcodeglut_woo_templates';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + fixed suffix), values are prepared
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE template_id = %s", $template_id), ARRAY_A);
    }
}
