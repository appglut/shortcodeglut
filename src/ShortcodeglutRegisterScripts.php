<?php
namespace Shortcodeglut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShortcodeglutRegisterScripts {

    private $load_assets = false;

    public function __construct() {

        // Include module asset registration files
        $this->include_module_assets();

        add_action( 'admin_init', [ $this, 'shortcodeglut_check_admin_pages' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'shortcodeglut_conditionally_enqueue_assets' ], 9999 );
        add_action( 'admin_enqueue_scripts', [ $this, 'shortcodeglut_conditionally_enqueue_assets' ], 9999 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueTemplatePreviewAssets' ] );
    }

    /**
     * Include module asset registration files
     * Only includes assets for modules available in shortcodeglut
     */
    private function include_module_assets() {
        $module_assets = [
            // Tools - only what's available in shortcodeglut
            SHORTCODEGLUT_PATH . 'src/wooTemplates/assets.php',
        ];

        foreach ($module_assets as $asset_file) {
            if (file_exists($asset_file)) {
                require_once $asset_file;
            }
        }
    }

    public function shortcodeglut_check_admin_pages() {
        // Security check for admin pages
        if (!current_user_can('manage_options')) {
            return;
        }

        // Verify nonce for admin pages that modify data
        if (isset($_GET['action']) && !isset($_GET['_wpnonce'])) {
            return;
        }

        if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
            if (!wp_verify_nonce($nonce, 'shortcodeglut_admin_nonce')) {
                return;
            }
        }

        $this->load_assets = true;
    }

    public function shortcodeglut_conditionally_enqueue_assets() {
        // Only load assets on frontend if needed
        if (is_admin()) {
            return;
        }

        // Load shortcode showcase assets
        $this->enqueue_shortcode_showcase_assets();
    }

    /**
     * Enqueue shortcode showcase assets on frontend
     */
    private function enqueue_shortcode_showcase_assets() {
        // Woo Category Shortcode assets
        if (is_singular() || is_archive()) {
            wp_enqueue_style(
                'shortcodeglut-woo-category',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/css/style.css',
                array(),
                SHORTCODEGLUT_VERSION
            );
            wp_enqueue_script(
                'shortcodeglut-woo-category',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/js/script.js',
                array('jquery'),
                SHORTCODEGLUT_VERSION,
                true
            );
        }

        // Product Table Shortcode assets
        $has_data_table = false;
        if (function_exists('is_product') && is_product()) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'shopglut_product_table')) {
                $has_data_table = true;
            }
        }
        // Check if content contains product table shortcode
        if (!$has_data_table) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'shopglut_product_table')) {
                $has_data_table = true;
            }
        }

        if ($has_data_table) {
            wp_enqueue_style('shortcodeglut-data-tables');
            wp_enqueue_style(
                'shortcodeglut-product-table',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/css/style.css',
                array('shortcodeglut-data-tables'),
                SHORTCODEGLUT_VERSION
            );
            wp_enqueue_script(
                'shortcodeglut-product-table',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/product-table/assets/js/script.js',
                array('jquery', 'datatables'),
                SHORTCODEGLUT_VERSION,
                true
            );
        }

        // Sale Products Shortcode assets
        if (is_singular() || is_archive() || is_shop() || is_home()) {
            global $post;
            $has_sale_shortcode = false;
            if ($post && has_shortcode($post->post_content, 'shopglut_sale_products')) {
                $has_sale_shortcode = true;
            }

            if ($has_sale_shortcode) {
                wp_enqueue_style(
                    'shortcodeglut-sale-products',
                    SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/sale-products/assets/css/style.css',
                    array(),
                    SHORTCODEGLUT_VERSION
                );
                wp_enqueue_script(
                    'shortcodeglut-sale-products',
                    SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/sale-products/assets/js/script.js',
                    array('jquery'),
                    SHORTCODEGLUT_VERSION,
                    true
                );
            }
        }
    }

    public function enqueueTemplatePreviewAssets() {
        // Enqueue admin assets
        $screen = get_current_screen();

        if ($screen && false !== (strpos($screen->id, 'shortcodeglut') || strpos($screen->id, 'shopglut_tools'))) {
            // Admin CSS
            wp_enqueue_style(
                'shortcodeglut-admin',
                SHORTCODEGLUT_URL . 'global-assets/css/style.css',
                array(),
                SHORTCODEGLUT_VERSION
            );

            // Load code editors for template editor
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for loading code editors based on GET parameter
            if (isset($_GET['editor']) && 'woo_template' === sanitize_text_field(wp_unslash($_GET['editor']))) {
                wp_enqueue_code_editor(array('type' => 'text/html'));
                wp_enqueue_code_editor(array('type' => 'text/css'));
                wp_enqueue_script('csslint');
                wp_enqueue_script('htmlhint');
            }
        }
    }

    public static function get_instance() {
        static $instance;

        if (is_null($instance)) {
            $instance = new self();
        }
        return $instance;
    }
}
