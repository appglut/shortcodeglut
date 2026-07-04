<?php
/**
 * Category Tree Shortcode
 *
 * Displays a hierarchical, collapsible tree view of WooCommerce product categories
 * with Font Awesome icons, product counts, and expand/collapse functionality.
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\CategoryTree;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CategoryTreeShortcode extends ShortcodeBase {

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Raw attributes from shortcode
	 */
	private $raw_attributes = array();

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->shortcode_slug = 'shortcodeglut_category_tree';
		$this->shortcode_name = 'WooCommerce Category Tree';
		parent::__construct();

		// Register frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Get default attributes
	 */
	protected function get_default_atts() {
		return array(
			'title'               => '',
			'show_count'          => '1',
			'expandable'          => '1',
			'expanded_depth'      => '0',
			'hide_empty'          => '0',
			'parent'              => '0',
			'orderby'             => 'name',
			'order'               => 'ASC',
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- string default, not a query param
			'exclude'             => '',
			'include'             => '',
			'number'              => '',
			'show_icon'           => '1',
			'icon_style'          => 'fontawesome', // 'fontawesome' or 'gradient'
			'show_breadcrumb'     => '0',
			'link_products'       => '0',
			'link_target'         => '_self', // _self (same tab) or _blank (new tab)
			'product_fields'      => 'name,price', // Comma-separated: name,price,add_to_cart,image
			'width'               => '500px', // Width of the tree container (e.g., 300px, 500px, 100%, auto)
		);
	}

	/**
	 * Render shortcode output
	 */
	public function render_shortcode( $atts ) {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '<p>' . esc_html__( 'WooCommerce is not active.', 'shortcodeglut' ) . '</p>';
		}

		// Build unique wrapper ID
		$wrapper_id = 'shortcodeglut_category_tree_' . $this->shortcode_counter;

		// Enqueue styles and scripts
		wp_enqueue_style( 'shortcodeglut-category-tree' );
		wp_enqueue_style( 'shortcodeglut-fontawesome' );
		wp_enqueue_script( 'shortcodeglut-category-tree' );

		// Get categories
		$categories = $this->get_categories( $atts );

		if ( empty( $categories ) ) {
			return '<p>' . esc_html__( 'No categories found.', 'shortcodeglut' ) . '</p>';
		}

			// Build output
			$width_style = ! empty( $atts['width'] ) ? ' style="width:' . esc_attr( $atts['width'] ) . ';"' : '';
			$output = '<div class="shortcodeglut-archive-wrapper shortcodeglut-category-tree-wrapper" id="' . esc_attr( $wrapper_id ) . '" data-atts="' . esc_attr( wp_json_encode( $atts ) ) . '"' . $width_style . '>';
		// Optional breadcrumb
		if ( $atts['show_breadcrumb'] === '1' ) {
			$output .= $this->render_breadcrumb();
		}

		// Container
		$output .= '<div class="shortcodeglut-tree-container">';

		// Optional title
		if ( ! empty( $atts['title'] ) ) {
			$output .= '<h2 class="shortcodeglut-tree-title">' . esc_html( $atts['title'] ) . '</h2>';
		}

		// Tree
		$output .= '<ul class="shortcodeglut-tree">';
		$output .= $this->render_tree( $categories, $atts, 0 );
		$output .= '</ul>';

		$output .= '</div>'; // .shortcodeglut-tree-container
		$output .= '</div>'; // .shortcodeglut-archive-wrapper

		return $output;
	}

	/**
	 * Get categories with hierarchy
	 */
	private function get_categories( $atts ) {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false, // Always get all categories, we'll check products manually
			'orderby'    => $atts['orderby'],
			'order'      => $atts['order'],
		);


		                                                                                                                                                      
		if ( ! empty( $atts['exclude'] ) ) {
			$exclude_items = explode( ',', $atts['exclude'] );
			$exclude_ids = array();
			foreach ( $exclude_items as $item ) {
					$item = trim( $item );
					if ( is_numeric( $item ) ) {
							$exclude_ids[] = intval( $item );
					} else {
							$term = get_term_by( 'slug', $item, 'product_cat' );
							if ( $term && ! is_wp_error( $term ) ) {
									$exclude_ids[] = $term->term_id;                                                                                        
							}
					}                                                                                                                                       
			}         
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- string default, not a query param
			$args['exclude'] = $exclude_ids;
		}

		if ( ! empty( $atts['include'] ) ) {
        $include_items = explode( ',', $atts['include'] );
        $include_ids = array();
        foreach ( $include_items as $item ) {
                $item = trim( $item );
                if ( is_numeric( $item ) ) {                                                                                                            
                        $include_ids[] = intval( $item );
                } else {                                                                                                                                
                        $term = get_term_by( 'slug', $item, 'product_cat' );
                        if ( $term && ! is_wp_error( $term ) ) {
                                $include_ids[] = $term->term_id;
                        }
                }
        }
        $args['include'] = $include_ids;
  }

		if ( ! empty( $atts['number'] ) ) {
			$args['number'] = intval( $atts['number'] );
		}

		// Get all categories
		$all_categories = get_categories( $args );

		// Update product counts for WooCommerce
		foreach ( $all_categories as $category ) {
			$category->product_count = $this->get_product_count( $category->term_id );
			
			// If hide_empty is enabled, filter out categories with no products
			if ( $atts['hide_empty'] === '1' && $category->product_count === 0 && empty( $category->children ) ) {
				$category->skip = true;
			}
		}

		 // Convert parent slug to ID if a slug is provided instead of numeric ID
			if ( ! empty( $atts['parent'] ) && ! is_numeric( $atts['parent'] ) ) {                                                 
				$parent_term = get_term_by( 'slug', $atts['parent'], 'product_cat' );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {                                                             
					$atts['parent'] = $parent_term->term_id;
				}
			}

		// Build hierarchical tree
		return $this->build_category_tree( $all_categories, $atts['parent'] );
	}

	/**
	 * Get actual product count for a category
	 *
	 * @param int $category_id Category term ID
	 * @return int Product count
	 */
	private function get_product_count( $category_id ) {
		$product_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering.
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'include_children' => false,
					'terms'    => $category_id,
				),
			),
		);

		$query = new \WP_Query( $product_args );
		$count = $query->post_count;
		
		
		return $count;
	}

	/**
	 * Build hierarchical category tree from flat list
	 *
	 * @param array $categories Flat list of categories
	 * @param string $parent_id Parent category ID to start from
	 * @return array Hierarchical tree of categories
	 */
	private function build_category_tree( $categories, $parent_id = '0' ) {
		$tree = array();
		$children_map = array();

		// First pass: organize by parent
		foreach ( $categories as $category ) {
			$cat_parent = isset( $category->parent ) ? $category->parent : '0';
			if ( ! isset( $children_map[ $cat_parent ] ) ) {
				$children_map[ $cat_parent ] = array();
			}
			$children_map[ $cat_parent ][] = $category;
		}

		// Second pass: build tree recursively
		$this->populate_children( $children_map, $parent_id, $tree );

		return $tree;
	}

	/**
	 * Recursively populate children for categories
	 *
	 * @param array $children_map Map of parent ID to children
	 * @param string $parent_id Current parent ID
	 * @param array &$tree Output array to populate
	 */
	private function populate_children( $children_map, $parent_id, &$tree ) {
		if ( ! isset( $children_map[ $parent_id ] ) ) {
			return;
		}

		foreach ( $children_map[ $parent_id ] as $category ) {
			// Skip categories marked for skipping
			if ( isset( $category->skip ) && $category->skip ) {
				continue;
			}
			
			$category->children = array();
			$this->populate_children( $children_map, (string) $category->term_id, $category->children );
			$tree[] = $category;
		}
	}

	/**
	 * Render tree recursively
	 */
	private function render_tree( $categories, $atts, $depth ) {
		$output = '';

		foreach ( $categories as $category ) {
			// Skip categories marked for skipping (empty with hide_empty)
			if ( isset( $category->skip ) && $category->skip ) {
				continue;
			}
			
			$has_children = ! empty( $category->children );
			$has_products = ( isset( $category->product_count ) && $category->product_count > 0 ) || ( ! empty( $category->count ) && $category->count > 0 );
			$is_expanded = $atts['expandable'] === '1' && $depth < intval( $atts['expanded_depth'] );
			

			$output .= '<li class="shortcodeglut-tree-item">';

			// Determine if this category should be expandable
			$is_expandable = ( $has_children || $has_products ) && $atts['expandable'] === '1';

			if ( $is_expandable ) {
				// Expandable parent (has children or products)
				$output .= '<div class="shortcodeglut-tree-link' . ( $is_expanded ? ' expanded' : '' ) . '" onclick="shortcodeglutToggleTree(this)">';

				// Add expand/collapse icon
				if ( $atts['show_icon'] === '1' ) {
					$output .= '<svg class="shortcodeglut-tree-icon" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';
				}

				// Add category icon
				if ( $atts['show_icon'] === '1' ) {
					$output .= $this->get_category_icon( $category, $atts['icon_style'], $depth, $atts );
				}

				$output .= '<span class="shortcodeglut-tree-name">' . esc_html( $category->name ) . '</span>';

				if ( $atts['show_count'] === '1' ) {
					$count = isset( $category->product_count ) ? $category->product_count : 0;
					$output .= '<span class="shortcodeglut-tree-count">' . intval( $count ) . '</span>';
				}

				$output .= '</div>';

				// Child categories subtree
				if ( $has_children ) {
					$output .= '<ul class="shortcodeglut-subtree' . ( $is_expanded ? ' open' : '' ) . '">';
					$output .= $this->render_tree( $category->children, $atts, $depth + 1 );
					$output .= '</ul>';
				}

				// Products subtree
				if ( $has_products && '1' === $atts['link_products'] ) {
					$output .= '<ul class="shortcodeglut-subtree' . ( $is_expanded ? ' open' : '' ) . '">';
					$output .= $this->render_category_products( $category->term_id, $atts );
					$output .= '</ul>';
				}
			} else {
				// Leaf node or non-expandable
				$link_url = '1' === $atts['link_products'] ? get_term_link( $category ) : '#';
				$link_target_attr = esc_attr( $atts['link_target'] );
				$output .= '<a href="' . esc_url( $link_url ) . '" target="' . $link_target_attr . '" class="shortcodeglut-tree-link">';

				if ( $atts['show_icon'] === '1' ) {
					$output .= $this->get_category_icon( $category, $atts['icon_style'], $depth, $atts );
				}

				$output .= '<span class="shortcodeglut-tree-name">' . esc_html( $category->name ) . '</span>';

				if ( $atts['show_count'] === '1' ) {
					$count = isset( $category->product_count ) ? $category->product_count : 0;
					$output .= '<span class="shortcodeglut-tree-count">' . intval( $count ) . '</span>';
				}

				$output .= '</a>';
			}

			$output .= '</li>';
		}

		return $output;
	}

	/**
	 * Get category icon (simple folder icon for all levels)
	 *
	 * @param object $category Category object
	 * @param string $style Icon style ('fontawesome' or 'gradient')
	 * @param int $depth Current depth level
	 * @param array $atts Shortcode attributes
	 * @return string Icon HTML
	 */
	private function get_category_icon( $category, $style, $depth, $atts ) {
		if ( 'fontawesome' === $style ) {
			// Use simple folder icon for all categories
			return '<i class="shortcodeglut-category-icon fa-solid fa-folder" style="color: #666; font-size: 18px;"></i>';
		}

		// Fallback to gradient style
		return $this->get_gradient_icon( $category );
	}

	/**
	 * Get gradient icon (fallback for backward compatibility)
	 *
	 * @param object $category Category object
	 * @return string Icon HTML
	 */
	private function get_gradient_icon( $category ) {
		$gradients = array(
			'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
			'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
			'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
			'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
			'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
			'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
		);

		$gradient = $gradients[ $category->term_id % count( $gradients ) ];

		return '<div class="shortcodeglut-category-icon shortcodeglut-gradient-icon" style="background: ' . esc_attr( $gradient ) . ';">
			<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
		</div>';
	}

	/**
	 * Render products for a category
	 *
	 * @param int $category_id Category term ID
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	private function render_category_products( $category_id, $atts ) {
		$output = '';

		// Parse product fields
		$product_fields = isset( $atts['product_fields'] ) ? $atts['product_fields'] : 'name,price';
		$fields = array_map( 'trim', explode( ',', $product_fields ) );

		$product_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering.
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $category_id,
					'include_children' => false,
				),
			),
		);

		$products = new \WP_Query( $product_args );

		if ( $products->have_posts() ) {
			while ( $products->have_posts() ) {
				$products->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				// Check if add_to_cart is in fields
				$show_add_to_cart = in_array( 'add_to_cart', $fields );
				$fields_without_cart = array_values( array_filter( $fields, function( $f ) {
					return $f !== 'add_to_cart';
				} ) );

				$output .= '<li class="shortcodeglut-tree-item shortcodeglut-product-item">';
				$link_target_attr = esc_attr( $atts['link_target'] );
				$output .= '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" target="' . $link_target_attr . '" class="shortcodeglut-tree-link shortcodeglut-product-link">';

				// Add simple product icon
				if ( $atts['show_icon'] === '1' && 'fontawesome' === $atts['icon_style'] ) {
					$output .= '<i class="fa-solid fa-cube" style="color: #888; font-size: 16px; margin-right: 8px;"></i>';
				}

				// Display product fields based on comma-separated values (excluding add_to_cart)
				foreach ( $fields_without_cart as $field ) {
					switch ( $field ) {
						case 'name':
							$output .= '<span class="shortcodeglut-tree-name">' . esc_html( $product->get_name() ) . '</span>';
							break;

						case 'price':
							if ( $product->get_price() ) {
								$output .= '<span class="shortcodeglut-tree-count">' . wp_kses_post( $product->get_price_html() ) . '</span>';
							}
							break;

						case 'image':
							$image = $product->get_image( 'thumbnail', array( 'class' => 'shortcodeglut-product-thumb', 'style' => 'width: 24px; height: 24px; object-fit: cover; border-radius: 4px; margin-right: 8px;' ) );
							$output .= $image;
							break;
					}
				}

				$output .= '</a>';

				// Add to cart button outside the main link
				if ( $show_add_to_cart && $product->is_in_stock() ) {
					$product_id = $product->get_id();
					$output .= '<button type="button" data-product_id="' . $product_id . '" class="shortcodeglut-add-to-cart" aria-label="' . esc_attr__( 'Add to cart', 'shortcodeglut' ) . '">';
					$output .= '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.86-7.01L19.42 4l-3.87 7H8.53L4.27 2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2z"/></svg>';
					$output .= '</button>';
				}

				$output .= '</li>';
			}
		}

		wp_reset_postdata();

		return $output;
	}

	/**
	 * Render breadcrumb
	 */
	private function render_breadcrumb() {
		$output = '<nav class="shortcodeglut-breadcrumb">';
		$output .= '<a href="' . esc_url( home_url() ) . '" target="_self">' . esc_html__( 'Home', 'shortcodeglut' ) . '</a>';
		$output .= '<span>/</span>';
		$output .= '<span>' . esc_html__( 'Categories', 'shortcodeglut' ) . '</span>';
		$output .= '</nav>';
		return $output;
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Styles
		wp_register_style(
			'shortcodeglut-category-tree',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/category-tree/assets/css/category-tree.css',
			array(),
			SHORTCODEGLUT_VERSION
		);

		// Font Awesome
		wp_register_style(
			'shortcodeglut-fontawesome',
			SHORTCODEGLUT_URL . 'global-assets/vendor/fontawesome/all.min.css',
			array(),
			'7.2.0'
		);

		// Font Awesome font-face path fix
		wp_register_style(
		'shortcodeglut-fontawesome-fix',
		SHORTCODEGLUT_URL . 'global-assets/vendor/fontawesome/fontface-fix.css',
		array( 'shortcodeglut-fontawesome' ),
		'7.2.0'
		);

		// Scripts
		wp_register_script(
			'shortcodeglut-category-tree',
			SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/category-tree/assets/js/category-tree.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);
	}
}
