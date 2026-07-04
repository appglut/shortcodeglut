<?php
/**
 * Shortcode Showcase Module Initialization
 *
 * This file initializes the shortcodes on both frontend and admin
 * to make them available for use in pages and posts
 *
 * Universal version - works with both Shortcodeglut and Shortcodeglut plugins.
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

		// Load ShortcodeBase class first (required by other shortcodes)
		require_once __DIR__ . '/ShortcodeBase.php';

		// Initialize Woo Category Shortcode
		require_once __DIR__ . '/shortcodes/woo-category/WooCategoryShortcode.php';
		\Shortcodeglut\shortcodeShowcase\shortcodes\WooCategory\WooCategoryShortcode::get_instance();

		// Initialize Basic Grid Shortcode
		require_once __DIR__ . '/shortcodes/basic-grid/BasicGridShortcode.php';
		\Shortcodeglut\shortcodeShowcase\shortcodes\BasicGrid\BasicGridShortcode::get_instance();

		// Initialize Category Tree Shortcode
		require_once __DIR__ . '/shortcodes/category-tree/CategoryTreeShortcode.php';
		\Shortcodeglut\shortcodeShowcase\shortcodes\CategoryTree\CategoryTreeShortcode::get_instance();

			// Initialize Table List Shortcode
			require_once __DIR__ . '/shortcodes/table-list/TableListShortcode.php';
			\Shortcodeglut\shortcodeShowcase\shortcodes\TableList\TableListShortcode::get_instance();

			// Initialize SideOne Shortcode
			require_once __DIR__ . '/shortcodes/sideone/SideoneShortcode.php';
			\Shortcodeglut\shortcodeShowcase\shortcodes\Sideone\SideoneShortcode::get_instance();

			// Initialize Kanban Board Shortcode
			require_once __DIR__ . '/shortcodes/kanban/KanbanShortcode.php';
			\Shortcodeglut\shortcodeShowcase\shortcodes\Kanban\KanbanShortcode::get_instance();

			// Initialize Tabs Layout Shortcode
			require_once __DIR__ . '/shortcodes/tabs/TabsShortcode.php';
			\Shortcodeglut\shortcodeShowcase\shortcodes\Tabs\TabsShortcode::get_instance();

			// Initialize Carousel Slider Shortcode
			require_once __DIR__ . '/shortcodes/carousel-slider/CarouselSliderShortcode.php';
			\Shortcodeglut\shortcodeShowcase\shortcodes\CarouselSlider\CarouselSliderShortcode::get_instance();

				// Initialize Accordion List Shortcode
				require_once __DIR__ . '/shortcodes/accordion/AccordionShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\Accordion\AccordionShortcode::get_instance();

				// Initialize Masonry Grid Shortcode
				require_once __DIR__ . '/shortcodes/masonry-grid/MasonryGridShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\MasonryGrid\MasonryGridShortcode::get_instance();


					// Initialize Conveyor Belt Shortcode
					require_once __DIR__ . '/shortcodes/conveyor-belt/ConveyorBeltShortcode.php';
					\Shortcodeglut\shortcodeShowcase\shortcodes\ConveyorBelt\ConveyorBeltShortcode::get_instance();
				// Initialize Timeline Shortcode
				require_once __DIR__ . '/shortcodes/timeline/TimelineShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\Timeline\TimelineShortcode::get_instance();

				// Initialize Zigzag Shortcode
				require_once __DIR__ . '/shortcodes/zigzag/ZigzagShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\Zigzag\ZigzagShortcode::get_instance();

				// Initialize Drawer Panels Shortcode
				require_once __DIR__ . '/shortcodes/drawer/DrawerShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\Drawer\DrawerShortcode::get_instance();

				// Initialize Horizontal Left Shortcode
				require_once __DIR__ . '/shortcodes/horizontal-left/HorizontalLeftShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\HorizontalLeft\HorizontalLeftShortcode::get_instance();

				// Initialize Radial Circle Shortcode
				require_once __DIR__ . '/shortcodes/radial-circle/RadialCircleShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\RadialCircle\RadialCircleShortcode::get_instance();

				// Initialize Book Flip Shortcode
				require_once __DIR__ . '/shortcodes/book-flip/BookFlipShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\BookFlip\BookFlipShortcode::get_instance();

				// Initialize Magazine Grid Shortcode
				require_once __DIR__ . '/shortcodes/magazine-grid/MagazineGridShortcode.php';
				\Shortcodeglut\shortcodeShowcase\shortcodes\MagazineGrid\MagazineGridShortcode::get_instance();

			}

			// Hook into WordPress init to register shortcodes
			add_action( 'init', 'Shortcodeglut\\shortcodeShowcase\\init_shortcode_showcase', 20 );
		}
