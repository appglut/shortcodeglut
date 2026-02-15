<?php
/**
 * Plugin Name: ShortcodeGlut - Product Shortcodes for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/shopglut
 * Description: Beautiful WooCommerce product shortcodes with grid, list, and table layouts for displaying products, sale items, and category listings.
 * Version: 1.0.0
 * Author: AppGlut
 * Author URI: https://appglut.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shortcodeglut
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SHORTCODEGLUT_VERSION', '1.0.0' );
define( 'SHORTCODEGLUT_FILE', __FILE__ );
define( 'SHORTCODEGLUT_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHORTCODEGLUT_URL', plugin_dir_url( __FILE__ ) );

// Load core classes
require_once SHORTCODEGLUT_PATH . 'src/ShortcodeglutDatabase.php';
require_once SHORTCODEGLUT_PATH . 'src/ShortcodeglutRegisterScripts.php';
require_once SHORTCODEGLUT_PATH . 'src/ShortcodeglutRegisterMenu.php';
require_once SHORTCODEGLUT_PATH . 'src/ShortcodeglutBase.php';
require_once SHORTCODEGLUT_PATH . 'src/ShortcodeglutTools.php';
require_once SHORTCODEGLUT_PATH . 'src/WelcomePage.php';

// Load WooTemplates classes before initializing SettingsPage
require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesEntity.php';
require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/SettingsPage.php';

// Load ShopGlut integration - suppresses ShortcodeGlut menu when ShopGlut is active
require_once SHORTCODEGLUT_PATH . 'shopglut-integration.php';

// Initialize WelcomePage early to register menu
\Shortcodeglut\WelcomePage::get_instance();

// Initialize SettingsPage early to register AJAX actions
\Shortcodeglut\wooTemplates\SettingsPage::get_instance();

// Initialize the plugin
function shortcodeglut_init() {
	\Shortcodeglut\ShortcodeglutBase::get_instance();
	\Shortcodeglut\tools\ShortcodeglutTools::get_instance();
}
add_action( 'plugins_loaded', 'shortcodeglut_init' );

// Activation hook
register_activation_hook( __FILE__, function() {
	\Shortcodeglut\ShortcodeglutDatabase::shortcodeglut_initialize();
	// Set transient to redirect to welcome page
	set_transient( 'shortcodeglut_activation_redirect', true, 30 );
} );

// Admin init hook for redirect to welcome page
add_action( 'admin_init', function() {
	// Check if we should redirect to welcome page
	if ( get_transient( 'shortcodeglut_activation_redirect' ) ) {
		delete_transient( 'shortcodeglut_activation_redirect' );

		// Don't redirect if activating from network admin or bulk activation
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking WordPress core parameter during plugin activation
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Redirect to welcome page
		wp_safe_redirect( admin_url( 'admin.php?page=shortcodeglut-welcome' ) );
		exit;
	}
} );
