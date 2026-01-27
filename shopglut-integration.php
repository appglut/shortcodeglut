<?php
/**
 * ShopGlut Integration for ShortcodeGlut
 *
 * This file should be placed in the ShortcodeGlut plugin directory.
 * It provides helper functions for ShopGlut integration.
 *
 * Installation: Place this file in: wp-content/plugins/shortcodeglut/shopglut-integration.php
 * Then add this line to shortcodeglut.php after the other require_once statements:
 * require_once SHORTCODEGLUT_PATH . 'shopglut-integration.php';
 *
 * @package ShortcodeGlut
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if ShopGlut plugin is active
 *
 * @return bool True if ShopGlut is active
 */
if ( ! function_exists( 'shortcodeglut_is_shopglut_active' ) ) {
	function shortcodeglut_is_shopglut_active() {
		// Check by active plugins list
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );

		if ( is_multisite() ) {
			// Get network active plugins
			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
		}

		// Check for shopglut plugin (check both shopglut.php and any folder containing shopglut.php)
		foreach ( $active_plugins as $plugin ) {
			if ( strpos( $plugin, 'shopglut.php' ) !== false ) {
				return true;
			}
		}

		// Also check if the main class exists
		return class_exists( 'Shopglut\\ShopGlutBase' );
	}
}

/**
 * Add filter to allow ShortcodeGlut features to work within ShopGlut context
 */
function shortcodeglut_shopglut_context_filter( $context ) {
	if ( shortcodeglut_is_shopglut_active() ) {
		$context['shopglut_integration'] = true;
		$context['plugin_url'] = SHORTCODEGLUT_URL;
		$context['plugin_path'] = SHORTCODEGLUT_PATH;
	}
	return $context;
}
add_filter( 'shortcodeglut_context', 'shortcodeglut_shopglut_context_filter', 10, 1 );

/**
 * Ensure ShortcodeGlut assets are loaded on ShopGlut pages when integrated
 */
function shortcodeglut_load_assets_on_shopglut_pages( $hook ) {
	// Only load if ShopGlut is active
	if ( ! shortcodeglut_is_shopglut_active() ) {
		return;
	}

	// Check if we're on a ShopGlut admin page
	if ( isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'shopglut_tools' ) !== false ) {
		// Load ShortcodeGlut assets
		$script_handle = 'shortcodeglut-admin';
		if ( wp_script_is( $script_handle, 'registered' ) ) {
			wp_enqueue_script( $script_handle );
		}

		$style_handle = 'shortcodeglut-admin';
		if ( wp_style_is( $style_handle, 'registered' ) ) {
			wp_enqueue_style( $style_handle );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'shortcodeglut_load_assets_on_shopglut_pages', 20 );
