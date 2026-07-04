<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Asset registration for wooTemplates
 */

class WooTemplatesAssets {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_frontend_assets() {
        $plugin_url = plugin_dir_url(__FILE__);

        // Enqueue CSS
        if (file_exists(__DIR__ . '/assets/style.css')) {
            wp_enqueue_style(
                'wooTemplates-style',
                $plugin_url . 'assets/style.css',
                [],
                filemtime(__DIR__ . '/assets/style.css')
            );
        }

        // Enqueue JS
        if (file_exists(__DIR__ . '/assets/script.js')) {
            wp_enqueue_script(
                'wooTemplates-script',
                $plugin_url . 'assets/script.js',
                ['jquery'],
                filemtime(__DIR__ . '/assets/script.js'),
                true
            );
        }
    }

    public function enqueue_admin_assets($hook) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for script loading
        if (!isset($_GET['page']) || 'shortcodeglut' !== sanitize_text_field(wp_unslash($_GET['page']))) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for view verification
        $view = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : '';

        if ('woo_templates' === $view) {
            $this->enqueue_templates_list_assets();
        } elseif ('woo_template_editor' === $view) {
            $this->enqueue_template_editor_assets();
        }
    }

    private function enqueue_templates_list_assets() {
        // Enqueue templates list CSS
        wp_enqueue_style(
            'shortcodeglut-woo-templates-list',
            SHORTCODEGLUT_URL . 'global-assets/css/woo-templates-list.css',
            [],
            defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0'
        );

        // Enqueue templates list JS
        wp_enqueue_script(
            'shortcodeglut-woo-templates-list',
            SHORTCODEGLUT_URL . 'global-assets/js/woo-templates-list.js',
            ['jquery'],
            defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0',
            true
        );

        // Localize script
        wp_localize_script('shortcodeglut-woo-templates-list', 'shortcodeglutTemplatesList', [
            'previewNonce' => wp_create_nonce('shortcodeglut_preview_nonce'),
            'i18n' => [
                'previewTemplate' => esc_html__('Preview Template', 'shortcodeglut'),
                'close' => esc_html__('Close', 'shortcodeglut'),
                'loadingPreview' => esc_html__('Loading preview...', 'shortcodeglut'),
                'errorLoadingPreview' => esc_html__('Error loading preview', 'shortcodeglut'),
            ]
        ]);
    }

    private function enqueue_template_editor_assets() {
        // Enqueue template editor CSS
        wp_enqueue_style(
            'shortcodeglut-template-editor',
            SHORTCODEGLUT_URL . 'global-assets/css/template-editor.css',
            [],
            defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0'
        );

        // Enqueue template editor JS
        wp_enqueue_script(
            'shortcodeglut-template-editor',
            SHORTCODEGLUT_URL . 'global-assets/js/template-editor.js',
            ['jquery'],
            defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0',
            true
        );
    }
}

// Initialize the assets class
new WooTemplatesAssets();
