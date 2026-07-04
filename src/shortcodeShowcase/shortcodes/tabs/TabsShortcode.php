<?php
/**
 * Tabs Layout Shortcode Handler
 *
 * Handles [shortcodeglut_tabs] shortcode to display products
 * in a tabbed layout with customizable header background
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Tabs;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TabsShortcode extends ShortcodeBase {

    private static $instance = null;

    public function __construct() {
        $this->shortcode_slug = 'shortcodeglut_tabs';
        $this->shortcode_name = 'WooCommerce Tabs Layout';
        parent::__construct();

        add_action( 'wp_ajax_shortcodeglut_tabs_load', array( $this, 'ajax_load_tab_products' ) );
        add_action( 'wp_ajax_nopriv_shortcodeglut_tabs_load', array( $this, 'ajax_load_tab_products' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function enqueue_frontend_assets() {
        wp_register_style(
            'shortcodeglut-tabs',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/tabs/assets/css/tabs.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        wp_register_script(
            'shortcodeglut-tabs',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/tabs/assets/js/tabs.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );

        wp_localize_script( 'shortcodeglut-tabs', 'shortcodeglutTabs', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'shortcodeglut_tabs_nonce' ),
        ) );
    }

    public function render_shortcode( $atts ) {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '<div class="shortcodeglut-tabs-placeholder">[ShortcodeGlut Tabs]</div>';
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
        }

        $this->shortcode_counter++;
        $unique_id = 'shortcodeglut_tabs_' . $this->shortcode_counter;

        $atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
        $atts = $this->sanitize_atts( $atts );

        wp_enqueue_style( 'shortcodeglut-tabs' );
        wp_enqueue_script( 'shortcodeglut-tabs' );

        ob_start();
        $this->render_output( $unique_id, $atts );
        return ob_get_clean();
    }

    protected function get_default_atts() {
        return array(
            'style'             => 'pill',
            'animation'         => 'fade',
            'header_bg_color'   => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'show_header'       => '1',
            'section_title'     => 'Browse by Category',
            'categories'        => '',
            'posts_per_page'    => 8,
            'columns'           => 4,
            'show_empty'        => '1',
        );
    }

    private function sanitize_atts( $atts ) {
        $atts['style'] = sanitize_text_field( $atts['style'] );
        $atts['animation'] = sanitize_text_field( $atts['animation'] );
        $atts['header_bg_color'] = sanitize_text_field( $atts['header_bg_color'] );
        $atts['show_header'] = filter_var( $atts['show_header'], FILTER_VALIDATE_BOOLEAN );
        $atts['section_title'] = sanitize_text_field( $atts['section_title'] );
        $atts['categories'] = sanitize_text_field( $atts['categories'] );
        $atts['posts_per_page'] = absint( $atts['posts_per_page'] );
        $atts['columns'] = absint( $atts['columns'] );
        $atts['show_empty'] = filter_var( $atts['show_empty'], FILTER_VALIDATE_BOOLEAN );
        return $atts;
    }

    private function render_output( $unique_id, $atts ) {
        $tabs = $this->get_tabs( $atts );

        echo '<div class="shortcodeglut-tabs-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper" data-shortcode-id="' . esc_attr( $unique_id ) . '" data-animation="' . esc_attr( $atts['animation'] ) . '">';

        if ( $atts['show_header'] ) {
            $this->render_header( $atts );
        }

        echo '<div class="shortcodeglut-tabs">';
        $this->render_tabs_nav( $tabs, $atts );
        echo '<div class="shortcodeglut-tabs-content">';
        $this->render_tabs_panels( $tabs, $atts, $unique_id );
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function render_header( $atts ) {
        echo '<div class="shortcodeglut-header">';
        echo '<h2 class="shortcodeglut-title">' . esc_html( $atts['section_title'] ) . '</h2>';
        echo '</div>';
    }

    private function get_tabs( $atts ) {
        $tabs = array();

        if ( ! empty( $atts['categories'] ) ) {
            $category_slugs = explode( ',', $atts['categories'] );
            foreach ( $category_slugs as $slug ) {
                $slug = trim( $slug );
                $term = get_term_by( 'slug', $slug, 'product_cat' );
                if ( $term ) {
                    $tabs[] = array(
                        'id' => $term->slug,
                        'label' => $term->name,
                        'term_id' => $term->term_id,
                    );
                }
            }
        } else {
            $product_categories = get_terms( array(
                'taxonomy' => 'product_cat',
                'hide_empty' => ! $atts['show_empty'],
                'number' => 6,
            ) );

            foreach ( $product_categories as $term ) {
                $tabs[] = array(
                    'id' => $term->slug,
                    'label' => $term->name,
                    'term_id' => $term->term_id,
                );
            }
        }

        if ( empty( $tabs ) ) {
            $tabs[] = array(
                'id' => 'all',
                'label' => 'All Products',
                'term_id' => 0,
            );
        }

        return $tabs;
    }

    private function render_tabs_nav( $tabs, $atts ) {
        $style_class = 'shortcodeglut-tabs-nav-' . esc_attr( $atts['style'] );

        echo '<nav class="shortcodeglut-tabs-nav ' . esc_attr( $style_class ) . '" style="background: ' . esc_attr( $atts['header_bg_color'] ) . ';">';

        $first = true;
        foreach ( $tabs as $tab ) {
            $active_class = $first ? 'active' : '';
            echo '<button class="shortcodeglut-tab-btn ' . esc_attr( $active_class ) . '" data-tab="' . esc_attr( $tab['id'] ) . '">' . esc_html( $tab['label'] ) . '</button>';
            $first = false;
        }

        echo '</nav>';
    }

    private function render_tabs_panels( $tabs, $atts, $unique_id ) {
        $first = true;
        foreach ( $tabs as $tab ) {
            $active_class = $first ? 'active' : '';
            echo '<div class="shortcodeglut-tab-panel ' . esc_attr( $active_class ) . '" id="' . esc_attr( $unique_id . '_panel_' . $tab['id'] ) . '" data-tab="' . esc_attr( $tab['id'] ) . '" data-term-id="' . esc_attr( $tab['term_id'] ) . '" data-posts-per-page="' . esc_attr( $atts['posts_per_page'] ) . '" data-columns="' . esc_attr( $atts['columns'] ) . '">';

            $products = $this->get_tab_products( $tab['term_id'], $atts );
            $this->render_products_grid( $products, $atts );

            echo '</div>';
            $first = false;
        }
    }

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering in tabs. Limited to posts_per_page count.
    private function get_tab_products( $term_id, $atts ) {
        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $atts['posts_per_page'],
        );

        if ( $term_id > 0 ) {
            $query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering in tabs. Limited to posts_per_page count.
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            );
        }

        return new \WP_Query( $query_args );
    }

    private function render_products_grid( $products, $atts ) {
        if ( $products->have_posts() ) {
            echo '<div class="shortcodeglut-grid">';

            while ( $products->have_posts() ) {
                $products->the_post();
                $product = wc_get_product( get_the_ID() );

                if ( $product ) {
                    $this->render_product_card( $product );
                }
            }

            echo '</div>';
            wp_reset_postdata();
        } else {
            $this->render_empty_state();
        }
    }

    private function render_product_card( $product ) {
        $title = get_the_title( $product->get_id() );
        $link = get_permalink( $product->get_id() );
        $excerpt = wp_trim_words( $product->get_short_description(), 10 );
        $gradient = $this->get_product_gradient();

        echo '<div class="shortcodeglut-card">';
        echo '<div class="shortcodeglut-card-image" style="background: ' . esc_attr( $gradient ) . ';">';
        echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
        echo '</div>';

        echo '<div class="shortcodeglut-card-body">';
        echo '<div class="shortcodeglut-card-title">' . esc_html( $title ) . '</div>';

        if ( $excerpt ) {
            echo '<div class="shortcodeglut-card-excerpt">' . esc_html( $excerpt ) . '</div>';
        }

        echo '<div class="shortcodeglut-card-footer">';
        echo '<div class="shortcodeglut-card-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';

        if ( $product->is_purchasable() && $product->is_in_stock() ) {
            echo '<button class="shortcodeglut-card-btn ajax_add_to_cart" data-product_id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Add', 'shortcodeglut' ) . '</button>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function get_product_gradient() {
        $gradients = array(
            'linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)',
            'linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%)',
            'linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%)',
            'linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%)',
            'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)',
        );
        return $gradients[ array_rand( $gradients ) ];
    }

    private function render_empty_state() {
        echo '<div class="shortcodeglut-empty-state">';
        echo '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
        echo '<p>' . esc_html__( 'No products found in this category.', 'shortcodeglut' ) . '</p>';
        echo '</div>';
    }

    public function ajax_load_tab_products() {
        check_ajax_referer( 'shortcodeglut_tabs_nonce', 'nonce' );

        $tab_id = isset( $_POST['tab_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tab_id'] ) ) : '';
        $term_id = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;
        $posts_per_page = isset( $_POST['posts_per_page'] ) ? absint( wp_unslash( $_POST['posts_per_page'] ) ) : 8;
        $columns = isset( $_POST['columns'] ) ? absint( wp_unslash( $_POST['columns'] ) ) : 4;

        $atts = array(
            'posts_per_page' => $posts_per_page,
            'columns' => $columns,
        );

        $products = $this->get_tab_products( $term_id, $atts );

        ob_start();
        $this->render_products_grid( $products, $atts );
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }
}
