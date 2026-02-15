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
        $plugin_url = plugin_dir_url(__FILE__);
        
        // Enqueue admin CSS
        if (file_exists(__DIR__ . '/assets/template-editor.css')) {
            wp_enqueue_style(
                'wooTemplates-admin-style',
                $plugin_url . 'assets/template-editor.css',
                [],
                filemtime(__DIR__ . '/assets/template-editor.css')
            );
        }
        
        // Enqueue admin JS
        if (file_exists(__DIR__ . '/assets/admin-script.js')) {
            wp_enqueue_script(
                'wooTemplates-admin-script',
                $plugin_url . 'assets/admin-script.js',
                ['jquery'],
                filemtime(__DIR__ . '/assets/admin-script.js'),
                true
            );
        }
    }
}

// Initialize the assets class
new WooTemplatesAssets();
