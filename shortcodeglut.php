<?php
/**
 * Plugin Name: ShortcodeGlut - Product Shortcodes for WooCommerce
 * Plugin URI: https://shopglut.com/
 * Description: Beautiful WooCommerce product shortcodes with grid, list, and table layouts for displaying products, sale items, and category listings.
 * Version: 1.0.0
 * Author: ShopGlut
 * Author URI: https://shopglut.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shortcodeglut
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
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

// Initialize the plugin
function shortcodeglut_init() {
	\Shortcodeglut\ShortcodeglutBase::get_instance();
	\Shortcodeglut\tools\ShortcodeglutTools::get_instance();
}
add_action( 'plugins_loaded', 'shortcodeglut_init' );

// Activation hook
register_activation_hook( __FILE__, function() {
	\Shortcodeglut\ShortcodeglutDatabase::shortcodeglut_initialize();
} );
