<?php
/**
 * Shortcode Showcase Module Initialization
 *
 * This file initializes the shortcodes on both frontend and admin
 * to make them available for use in pages and posts
 *
 * Universal version - works with both Shortcodeglut and Shopglut plugins.
 * The loader.php defines SHORTCODEGLUT_* constants appropriately for each plugin.
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize Shortcode Showcase shortcodes
 */
if ( ! function_exists( 'Shortcodeglut\\shortcodeShowcase\\init_shortcode_showcase' ) ) {
	function init_shortcode_showcase() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Initialize Woo Category Shortcode
		require_once __DIR__ . '/shortcodes/woo-category/WooCategoryShortcode.php';
		\Shortcodeglut\shortcodeShowcase\shortcodes\WooCategory\WooCategoryShortcode::get_instance();

		// Initialize Product Table Shortcode
		require_once __DIR__ . '/shortcodes/product-table/ProductTableShortcode.php';
		\Shortcodeglut\shortcodeShowcase\shortcodes\ProductTable\ProductTableShortcode::get_instance();

		// Initialize Sale Products Shortcode
		if ( file_exists( __DIR__ . '/shortcodes/sale-products/SaleProductsShortcode.php' ) ) {
			require_once __DIR__ . '/shortcodes/sale-products/SaleProductsShortcode.php';
			\Shortcodeglut\shortcodeShowcase\shortcodes\SaleProducts\SaleProductsShortcode::get_instance();
		}
	}

	// Hook into WordPress init
	add_action( 'init', 'Shortcodeglut\\shortcodeShowcase\\init_shortcode_showcase', 20 );
}
