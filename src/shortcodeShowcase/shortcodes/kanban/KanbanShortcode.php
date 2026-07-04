<?php
/**
 * Kanban Board Shortcode Handler
 *
 * Handles [shortcodeglut_kanban] shortcode to display products
 * in a 3-column Kanban board layout
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\Kanban;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KanbanShortcode extends ShortcodeBase {

    private static $instance = null;

    public function __construct() {
        $this->shortcode_slug = 'shortcodeglut_kanban';
        $this->shortcode_name = 'WooCommerce Kanban Board';
        parent::__construct();

        add_action( 'wp_ajax_shortcodeglut_kanban_load', array( $this, 'ajax_load_products' ) );
        add_action( 'wp_ajax_nopriv_shortcodeglut_kanban_load', array( $this, 'ajax_load_products' ) );
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
            'shortcodeglut-kanban',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/kanban/assets/css/kanban.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        wp_register_script(
            'shortcodeglut-kanban',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/kanban/assets/js/kanban.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );

        wp_localize_script( 'shortcodeglut-kanban', 'shortcodeglutKanban', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'shortcodeglut_kanban_nonce' ),
        ) );
    }

    public function render_shortcode( $atts ) {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '<div class="shortcodeglut-kanban-placeholder">[Shortcodeglut Kanban Board]</div>';
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            return '<p class="shortcodeglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
        }

        $this->shortcode_counter++;
        $unique_id = 'shortcodeglut_kanban_' . $this->shortcode_counter;

        $atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
        $atts = $this->sanitize_atts( $atts );

        wp_enqueue_style( 'shortcodeglut-kanban' );
        wp_enqueue_script( 'shortcodeglut-kanban' );

        ob_start();
        $this->render_output( $unique_id, $atts );
        return ob_get_clean();
    }

    protected function get_default_atts() {
        return array(
            'columns'       => 3,
            'per_column'    => 4,
            'show_header'   => '1',
            'section_title' => 'Kanban Board',
            'show'          => 'featured,new,onsale',
            'category'      => '',
    // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
            'exclude'       => '',
        );
    }

    private function sanitize_atts( $atts ) {
        $atts['columns'] = min( absint( $atts['columns'] ), 3 );
        $atts['per_column'] = absint( $atts['per_column'] );
        $atts['show_header'] = filter_var( $atts['show_header'], FILTER_VALIDATE_BOOLEAN );
        $atts['section_title'] = sanitize_text_field( $atts['section_title'] );
        $atts['show'] = sanitize_text_field( $atts['show'] );
        $atts['category'] = sanitize_text_field( $atts['category'] );
// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
        $atts['exclude'] = sanitize_text_field( $atts['exclude'] );
        return $atts;
    }

    private function parse_show_attribute( $show_value ) {
        if ( empty( $show_value ) ) {
            return array( 'featured', 'new', 'onsale' );
        }

        $types = explode( ',', $show_value );
        $allowed = array( 'featured', 'new', 'onsale', 'low_stock' );
        $enabled = array();

        foreach ( $types as $type ) {
            $type = trim( $type );
            if ( in_array( $type, $allowed ) ) {
                $enabled[] = $type;
            }
        }

        return empty( $enabled ) ? array( 'featured', 'new', 'onsale' ) : $enabled;
    }

    private function render_output( $unique_id, $atts ) {
        $enabled_columns = $this->parse_show_attribute( $atts['show'] );

        echo '<div class="shortcodeglut-kanban-wrapper" id="' . esc_attr( $unique_id ) . '_wrapper">';

        if ( $atts['show_header'] ) {
            $this->render_header( $atts );
        }

        echo '<div class="shortcodeglut-kanban">';

        foreach ( $enabled_columns as $column_type ) {
            $this->render_column( $column_type, $atts, 1 );
        }

        echo '</div>';
        echo '</div>';
    }

    private function render_header( $atts ) {
        echo '<div class="shortcodeglut-header">';
        echo '<h2 class="shortcodeglut-title">' . esc_html( $atts['section_title'] ) . '</h2>';
        echo '</div>';
    }

    private function render_column( $column_type, $atts, $page = 1 ) {
        $products = $this->get_column_products( $column_type, $atts, $page, $atts['per_column'] );

        $column_data = $this->get_column_config( $column_type );

        echo '<div class="shortcodeglut-column" data-column="' . esc_attr( $column_type ) . '" data-page="' . esc_attr( $page ) . '">';
        echo '<div class="shortcodeglut-column-header">';

        if ( ! empty( $column_data['priority_class'] ) ) {
            echo '<div class="shortcodeglut-column-title">';
            echo '<span class="shortcodeglut-column-priority ' . esc_attr( $column_data['priority_class'] ) . '"></span>';
            echo esc_html( $column_data['title'] );
            echo '</div>';
        } else {
            echo '<div class="shortcodeglut-column-title">' . esc_html( $column_data['title'] ) . '</div>';
        }

        echo '<span class="shortcodeglut-column-count">' . esc_html( $products->post_count ) . '</span>';
        echo '</div>';

        if ( $products->have_posts() ) {
            while ( $products->have_posts() ) {
                $products->the_post();
                $product = wc_get_product( get_the_ID() );
                if ( $product ) {
                    $this->render_product_card( $product, $column_type );
                }
            }
            wp_reset_postdata();
        }

        if ( $products->post_count >= $atts['per_column'] ) {
            echo '<button class="shortcodeglut-load-more" data-column="' . esc_attr( $column_type ) . '">' . esc_html__( 'Load More', 'shortcodeglut' ) . '</button>';
        }

        echo '</div>';
    }

    private function get_column_config( $column_type ) {
        $configs = array(
            'featured' => array( 'title' => 'Featured', 'priority_class' => 'high' ),
            'new' => array( 'title' => 'New Arrivals', 'priority_class' => 'medium' ),
            'onsale' => array( 'title' => 'On Sale', 'priority_class' => 'low' ),
            'low_stock' => array( 'title' => 'Last Chance', 'priority_class' => 'critical' ),
        );
        return isset( $configs[ $column_type ] ) ? $configs[ $column_type ] : array( 'title' => 'Products', 'priority_class' => '' );
    }

    private function get_column_products( $column_type, $atts, $page = 1, $per_page = 4 ) {
        $offset = ( $page - 1 ) * $per_page;

        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'offset' => $offset,
            'paged' => 0,
        );

        if ( ! empty( $atts['category'] ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering, proper indexes exist.
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        if ( ! empty( $atts['exclude'] ) ) {
            $exclude_ids = array_map( 'absint', explode( ',', $atts['exclude'] ) );
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- User-controlled product exclusion, limited and bounded.
            $query_args['post__not_in'] = $exclude_ids;
        }

        switch ( $column_type ) {
            case 'featured':
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for featured filtering, proper indexes exist.
                $query_args['tax_query'] = $query_args['tax_query'] ?? array();
                $query_args['tax_query'][] = array(
                    'taxonomy' => 'product_visibility',
                    'field' => 'name',
                    'terms' => 'featured',
                );
                break;

            case 'new':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;

            case 'onsale':
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for sale price filtering, proper indexes exist.
                $query_args['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_sale_price',
                        'value' => 0,
                        'compare' => '>',
                        'type' => 'NUMERIC',
                    ),
                    array(
                        'key' => '_min_variation_sale_price',
                        'value' => 0,
                        'compare' => '>',
                        'type' => 'NUMERIC',
                    ),
                );
                break;

            case 'low_stock':
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for stock level filtering, proper indexes exist.
                $query_args['meta_query'] = array(
                    array(
                        'key' => '_stock',
                        'value' => 5,
                        'compare' => '<',
                        'type' => 'NUMERIC',
                    ),
                );
                break;
        }

        return new \WP_Query( $query_args );
    }

    private function render_product_card( $product, $column_type ) {
        $title = get_the_title( $product->get_id() );
        $link = get_permalink( $product->get_id() );
        $excerpt = wp_trim_words( $product->get_short_description(), 10 );
        $tag = $this->get_product_tag( $product, $column_type );
        $gradient = $this->get_product_icon_gradient( $product, $column_type );

        echo '<div class="shortcodeglut-card">';
        echo '<div class="shortcodeglut-card-icon" style="background: ' . esc_attr( $gradient ) . ';">';
        echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
        echo '</div>';

        if ( $tag ) {
            echo '<span class="shortcodeglut-card-tag">' . esc_html( $tag ) . '</span>';
        }

        echo '<div class="shortcodeglut-card-title">';
        echo '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>';
        echo '</div>';

        if ( $excerpt ) {
            echo '<div class="shortcodeglut-card-desc">' . esc_html( $excerpt ) . '</div>';
        }

        echo '<div class="shortcodeglut-card-meta">';
        echo '<span class="shortcodeglut-card-price">' . wp_kses_post( $product->get_price_html() ) . '</span>';

        if ( $product->is_purchasable() && $product->is_in_stock() ) {
            echo '<button class="shortcodeglut-card-btn ajax_add_to_cart" data-product_id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Add', 'shortcodeglut' ) . '</button>';
        }

        echo '</div>';
        echo '</div>';
    }

    private function get_product_tag( $product, $column_type ) {
        $tags = array(
            'featured' => 'Featured',
            'new' => 'New',
            'onsale' => 'On Sale',
            'low_stock' => 'Last Chance',
        );
        return isset( $tags[ $column_type ] ) ? $tags[ $column_type ] : '';
    }

    private function get_product_icon_gradient( $product, $column_type ) {
        $gradients = array(
            'featured' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'new' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'onsale' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'low_stock' => 'linear-gradient(135deg, #a855f7 0%, #6366f1 100%)',
        );
        return isset( $gradients[ $column_type ] ) ? $gradients[ $column_type ] : $gradients['featured'];
    }

    public function ajax_load_products() {
        check_ajax_referer( 'shortcodeglut_kanban_nonce', 'nonce' );

        $column = isset( $_POST['column'] ) ? sanitize_text_field( wp_unslash( $_POST['column'] ) ) : '';
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_column = isset( $_POST['per_column'] ) ? absint( wp_unslash( $_POST['per_column'] ) ) : 4;
        $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
        $exclude = isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '';

        $atts = array(
            'per_column' => $per_column,
            'category' => $category,
    // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User-controlled product exclusion, limited and bounded.
            'exclude' => $exclude,
        );

        $products = $this->get_column_products( $column, $atts, $page + 1, $per_column );

        ob_start();
        if ( $products->have_posts() ) {
            while ( $products->have_posts() ) {
                $products->the_post();
                $product = wc_get_product( get_the_ID() );
                if ( $product ) {
                    $this->render_product_card( $product, $column );
                }
            }
            wp_reset_postdata();
        }
        $html = ob_get_clean();

        $has_more = $products->post_count >= $per_column;

        wp_send_json_success( array(
            'html' => $html,
            'has_more' => $has_more,
        ) );
    }
}
