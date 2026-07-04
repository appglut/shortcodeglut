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

        add_action( 'wp_enqueue_scripts', array( $this, 'register_all_assets' ), 5 );

        add_action( 'admin_init', [ $this, 'shortcodeglut_check_admin_pages' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'shortcodeglut_conditionally_enqueue_assets' ], 9999 );
        add_action( 'admin_enqueue_scripts', [ $this, 'shortcodeglut_conditionally_enqueue_assets' ], 9999 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueTemplatePreviewAssets' ] );
    }


    public function register_all_assets() {
      $this->register_woo_category_assets();
      $this->register_category_tree_assets();
      $this->register_table_list_assets();
      $this->register_sideone_assets();
      $this->register_accordion_assets();
      $this->register_masonry_grid_assets();
      $this->register_conveyor_belt_assets();
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

    /**
     * Register woo-category shortcode assets
     */
    private function register_woo_category_assets() {
        // Register woo category styles
        wp_register_style(
            'shortcodeglut-woo-category',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/css/woo-category.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        // Register woo category script
        wp_register_script(
            'shortcodeglut-woo-category',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/js/woo-category.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );

        // Register add-to-cart-handler (global for all templates)
        wp_register_style(
            'shortcodeglut-add-to-cart-handler',
            SHORTCODEGLUT_URL . 'global-assets/css/add-to-cart-handler.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        wp_register_script(
            'shortcodeglut-add-to-cart-handler',
            SHORTCODEGLUT_URL . 'global-assets/js/add-to-cart-handler.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );
    }

    public function shortcodeglut_check_admin_pages() {
        // Security check for admin pages
        if (!current_user_can('manage_options')) {
            return;
        }

        // Note: This function only controls asset loading, not form processing
        // Form processing happens in dedicated handlers with proper nonce checks
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
            // Enqueue Font Awesome for icons
            wp_enqueue_style(
                'shortcodeglut-fontawesome',
                SHORTCODEGLUT_URL . 'global-assets/vendor/fontawesome/all.min.css',
                array(),
                '6.4.0'
            );

            wp_enqueue_style(
                'shortcodeglut-woo-category',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/css/woo-category.css',
                array(),
                SHORTCODEGLUT_VERSION
            );
            wp_enqueue_script(
                'shortcodeglut-woo-category',
                SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/woo-category/assets/js/woo-category.js',
                array('jquery'),
                SHORTCODEGLUT_VERSION,
                true
            );

            // Enqueue add-to-cart-handler for templates
            wp_enqueue_style('shortcodeglut-add-to-cart-handler');
            wp_enqueue_script('shortcodeglut-add-to-cart-handler');
            // Enqueue category tree shortcode assets
            wp_enqueue_style('shortcodeglut-category-tree-css');
            wp_enqueue_script('shortcodeglut-category-tree');

            // Enqueue table list shortcode assets
            wp_enqueue_style('shortcodeglut-table-list');
            wp_enqueue_script('shortcodeglut-table-list');
            wp_enqueue_script('shortcodeglut-table-list-add-to-cart');

            // Enqueue sideone shortcode assets
            wp_enqueue_style('shortcodeglut-sideone');
            wp_enqueue_script('shortcodeglut-sideone');

            // Enqueue accordion shortcode assets
            wp_enqueue_style('shortcodeglut-accordion');
            wp_enqueue_script('shortcodeglut-accordion');
            wp_enqueue_script('shortcodeglut-accordion-add-to-cart');

            // Enqueue masonry grid shortcode assets
            wp_enqueue_style('shortcodeglut-masonry-grid');
            wp_enqueue_script('shortcodeglut-masonry-grid');

            // Enqueue conveyor belt shortcode assets
            wp_enqueue_style('shortcodeglut-conveyor-belt');
            wp_enqueue_script('shortcodeglut-conveyor-belt');
        }
    }

    public function enqueueTemplatePreviewAssets() {
        // Enqueue admin assets
        $screen = get_current_screen();

        if ($screen && false !== strpos($screen->id, 'shortcodeglut')) {
            // Admin CSS
            wp_enqueue_style(
                'shortcodeglut-admin',
                SHORTCODEGLUT_URL . 'global-assets/css/style.css',
                array(),
                SHORTCODEGLUT_VERSION
            );

            // Enqueue Font Awesome for preview modal icons
            wp_enqueue_style(
                'shortcodeglut-fontawesome-admin',
                SHORTCODEGLUT_URL . 'global-assets/vendor/fontawesome/all.min.css',
                array(),
                '7.2.0'
            );
        }
    }

     /**
       * Register category-tree shortcode assets
       */
      private function register_category_tree_assets() {
          // Register category tree styles
          wp_register_style(
              'shortcodeglut-category-tree-css',
              SHORTCODEGLUT_URL .
'src/shortcodeShowcase/shortcodes/category-tree/assets/css/category-tree.css',
              array(),
              SHORTCODEGLUT_VERSION
          );

          // Register category tree script
          wp_register_script(
              'shortcodeglut-category-tree',
              SHORTCODEGLUT_URL .
'src/shortcodeShowcase/shortcodes/category-tree/assets/js/category-tree.js',
              array( 'jquery' ),
              SHORTCODEGLUT_VERSION,
              true
          );

      }

     /**
       * Register table-list shortcode assets
       */
      private function register_table_list_assets() {
          // Register table list styles
          wp_register_style(
              'shortcodeglut-table-list',
              SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/table-list/assets/css/table-list.css',
              array(),
              SHORTCODEGLUT_VERSION
          );

          // Register table list script
          wp_register_script(
              'shortcodeglut-table-list',
              SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/table-list/assets/js/table-list.js',
              array( 'jquery' ),
              SHORTCODEGLUT_VERSION,
              true
          );

          // Register table list add to cart script
          wp_register_script(
              'shortcodeglut-table-list-add-to-cart',
              SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/table-list/assets/js/table-list-add-to-cart.js',
              array( 'jquery' ),
              SHORTCODEGLUT_VERSION,
              true
          );
      }

    /**
     * Register sideone shortcode assets
     */
    private function register_sideone_assets() {
        // Register sideone styles
        wp_register_style(
            'shortcodeglut-sideone',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/sideone/assets/css/sideone.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        // Register sideone script
        wp_register_script(
            'shortcodeglut-sideone',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/sideone/assets/js/sideone.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );
    }

    /**
     * Register accordion shortcode assets
     */
    private function register_accordion_assets() {
        // Register accordion styles
        wp_register_style(
            'shortcodeglut-accordion',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/accordion/assets/css/accordion.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        // Register accordion script
        wp_register_script(
            'shortcodeglut-accordion',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/accordion/assets/js/accordion.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );

        // Register accordion add to cart script
        wp_register_script(
            'shortcodeglut-accordion-add-to-cart',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/accordion/assets/js/accordion-add-to-cart.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );
    }

    /**
     * Register masonry grid shortcode assets
     */
    private function register_masonry_grid_assets() {
        // Register masonry grid styles
        wp_register_style(
            'shortcodeglut-masonry-grid',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/masonry-grid/assets/css/masonry-grid.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        // Register masonry grid script
        wp_register_script(
            'shortcodeglut-masonry-grid',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/masonry-grid/assets/js/masonry-grid.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );
    }

    /**
     * Register conveyor belt shortcode assets
     */
    private function register_conveyor_belt_assets() {
        // Register conveyor belt styles
        wp_register_style(
            'shortcodeglut-conveyor-belt',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/conveyor-belt/assets/css/conveyor-belt.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        // Register conveyor belt script
        wp_register_script(
            'shortcodeglut-conveyor-belt',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/conveyor-belt/assets/js/conveyor-belt.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );
    }

    public static function get_instance() {
        static $instance;

        if (is_null($instance)) {
            $instance = new self();
        }
        return $instance;
    }
}
