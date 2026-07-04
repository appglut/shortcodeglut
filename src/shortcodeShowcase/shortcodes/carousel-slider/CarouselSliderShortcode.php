<?php
/**
 * Carousel Slider Shortcode Handler
 *
 * Handles [shopglut_carousel] shortcode to display products
 * in a beautiful carousel slider with navigation arrows and dots
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase\shortcodes\CarouselSlider;

use Shortcodeglut\shortcodeShowcase\ShortcodeBase;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CarouselSliderShortcode extends ShortcodeBase {

    private static $instance = null;
    private $products_cache = array();

    public function __construct() {
        $this->shortcode_slug = 'shortcodeglut_carousel';
        $this->shortcode_name = 'Carousel Slider';
        parent::__construct();

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
            'shopglut-carousel',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/carousel-slider/assets/css/carousel.css',
            array(),
            SHORTCODEGLUT_VERSION
        );

        wp_register_script(
            'shopglut-carousel',
            SHORTCODEGLUT_URL . 'src/shortcodeShowcase/shortcodes/carousel-slider/assets/js/carousel.js',
            array( 'jquery' ),
            SHORTCODEGLUT_VERSION,
            true
        );

        wp_localize_script( 'shopglut-carousel', 'shopglutCarousel', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'shopglut_carousel_nonce' ),
        ) );
    }

   protected function get_default_atts() {                   
      return array(                                                                                   
          'autoplay'         => '5000',                                                               
          'arrows'           => 'true',    
          'dots'             => 'true',                                                                                                                                                                  
          'section_title'    => 'Featured Products',                                                  
          'posts_per_page'   => 6,                                                                    
          'category'         => '',                                                                   
          'order_by'         => 'date-desc',                                                          
          'show_price'       => '1',                                                                  
          'show_button'      => '1',                                                                  
          'product_bgs'      => '',         // Product ID:background pairs (e.g. "123:#ff0000,456:linear-gradient(...),789:url(bg.jpg)")                                             
          'default_bg'       => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',  // Default fallback gradient                                                                                   
      );                                                                                              
  }           

    public function render_shortcode( $atts ) {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '<div class="shopglut-carousel-placeholder">[ShopGlut Carousel]</div>';
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            return '<p class="shopglut-error">' . esc_html__( 'WooCommerce is required.', 'shortcodeglut' ) . '</p>';
        }

        $this->shortcode_counter++;
        $unique_id = 'shopglut_carousel_' . $this->shortcode_counter;

        $atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );
        $atts = $this->sanitize_atts( $atts );

        wp_enqueue_style( 'shopglut-carousel' );
        wp_enqueue_script( 'shopglut-carousel' );

        ob_start();
        $this->render_output( $unique_id, $atts );
        return ob_get_clean();
    }

   private function sanitize_atts( $atts ) {  
        $atts['autoplay'] = absint( $atts['autoplay'] );                                                                                                                                          
      $atts['arrows'] = filter_var( $atts['arrows'], FILTER_VALIDATE_BOOLEAN );                       
      $atts['dots'] = filter_var( $atts['dots'], FILTER_VALIDATE_BOOLEAN );                           
      $atts['section_title'] = sanitize_text_field( $atts['section_title'] );                         
      $atts['posts_per_page'] = absint( $atts['posts_per_page'] );                                    
      $atts['category'] = sanitize_text_field( $atts['category'] );                                   
      $atts['order_by'] = sanitize_text_field( $atts['order_by'] );                                   
      $atts['show_price'] = filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN );               
      $atts['show_button'] = filter_var( $atts['show_button'], FILTER_VALIDATE_BOOLEAN );             
      $atts['product_bgs'] = sanitize_text_field( $atts['product_bgs'] );                             
      $atts['default_bg'] = sanitize_text_field( $atts['default_bg'] );                               
      return $atts;                                                                                   
  }      

    private function render_output( $unique_id, $atts ) {
        $products = $this->get_carousel_products( $atts );
        $gradients = $this->get_gradient_colors();

        echo '<div class="shopglut-archive" data-carousel-id="' . esc_attr( $unique_id ) . '">';
        $this->render_header( $atts );
        $this->render_carousel( $unique_id, $products, $atts, $gradients );
        echo '</div>';
    }

    private function render_header( $atts ) {
        echo '<div class="shopglut-header">';
        echo '<h1 class="shopglut-title">' . esc_html( $atts['section_title'] ) . '</h1>';
        echo '<div class="shopglut-code-info">[shopglut_carousel autoplay="' . esc_attr( $atts['autoplay'] ) . '" arrows="' . ( $atts['arrows'] ? 'true' : 'false' ) . '" dots="' . ( $atts['dots'] ? 'true' : 'false' ) . '"]</div>';
        echo '</div>';
    }

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Necessary for WooCommerce product sorting by price, sales, and rating. Limited to posts_per_page count.
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering. Limited to posts_per_page count.
    private function get_carousel_products( $atts ) {
        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $atts['posts_per_page'],
        );

        $order_by = $atts['order_by'];
        switch ( $order_by ) {
            case 'price-asc':
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WooCommerce price sorting, limited by posts_per_page.
                $query_args['order'] = 'ASC';
                break;
            case 'price-desc':
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WooCommerce price sorting, limited by posts_per_page.
                $query_args['order'] = 'DESC';
                break;
            case 'popularity-desc':
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WooCommerce sales sorting, limited by posts_per_page.
                $query_args['order'] = 'DESC';
                break;
            case 'rating-desc':
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WooCommerce rating sorting, limited by posts_per_page.
                $query_args['order'] = 'DESC';
                break;
            case 'title-asc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            case 'date-desc':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'ASC';
                break;
            case 'date':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
        }

        if ( ! empty( $atts['category'] ) ) {
            $query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering, limited by posts_per_page.
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        $product_visibility_term_ids = wc_get_product_visibility_term_ids();
        if ( ! empty( $product_visibility_term_ids['exclude-from-catalog'] ) ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => $product_visibility_term_ids['exclude-from-catalog'],
                'operator' => 'NOT IN',
            );
        }

        return new \WP_Query( $query_args );
    }

   private function render_carousel( $unique_id, $products, $atts, $gradients ) {
          $show_arrows_class = $atts['arrows'] ? 'show-arrows' : '';                                  
          $show_dots_class = $atts['dots'] ? 'show-dots' : '';                                        
          $product_backgrounds = $this->parse_product_backgrounds( $atts['product_bgs'] );            
                                                                                                      
          echo '<div class="shopglut-carousel ' . esc_attr( $show_arrows_class ) . ' ' . esc_attr(    
  $show_dots_class ) . '" data-autoplay="' . esc_attr( $atts['autoplay'] ) . '" data-carousel-id="' . 
  esc_attr( $unique_id ) . '">';                                                                      
                                                            
          if ( $atts['arrows'] ) {                                                                    
              echo '<button class="shopglut-carousel-arrow prev" data-direction="prev">';
              echo '<i class="fa fa-chevron-left" aria-hidden="true"></i>';                           
              echo '</button>';                                                                       
          }                                                                                           
                                                                                                      
          echo '<div class="shopglut-carousel-track">';                                               
                                                            
          if ( $products->have_posts() ) {                                                            
              while ( $products->have_posts() ) {           
                  $products->the_post();                                                              
                  $product = wc_get_product( get_the_ID() );

                  if ( $product ) {
                      $product_id = $product->get_id();
                      $slide_background = $this->get_product_background( $product_id,
  $product_backgrounds, $atts['default_bg'] );                                                        
                      $this->render_slide( $product, $atts, $slide_background );
                  }                                                                                   
              }                                                                                       
              wp_reset_postdata();                                                                    
          } else {                                                                                    
              $this->render_empty_slide();                  
          }                                                                                           
                                                            
          echo '</div>';                                                                              
                                                            
          if ( $atts['arrows'] ) {                                                                    
              echo '<button class="shopglut-carousel-arrow next" data-direction="next">';
              echo '<i class="fa fa-chevron-right" aria-hidden="true"></i>';                          
              echo '</button>';                                                                       
          }                                                                                           
                                                                                                      
          if ( $atts['dots'] && $products->have_posts() ) {                                           
              $slide_count = $products->post_count;
              echo '<div class="shopglut-carousel-nav">';                                             
              for ( $i = 0; $i < $slide_count; $i++ ) {                                               
                  $active_class = ( $i === 0 ) ? 'active' : '';                                       
                  echo '<button class="shopglut-carousel-dot ' . esc_attr( $active_class ) . '"       
  data-slide="' . esc_attr( $i ) . '"></button>';                                                     
              }                                                                                       
              echo '</div>';                                                                          
          }                                                                                           
  
          echo '</div>';                                                                              
      }

     private function parse_product_backgrounds( $product_bgs_string ) {                                                             
          if ( empty( $product_bgs_string ) ) {                                                                                       
              return array();                                                                                                         
          }                                                                                                                           
                                                                                                                                      
          $product_backgrounds = array();                                                                                             
          $pairs = explode( '|', $product_bgs_string );  // <-- MUST use | not ,                                                      
                                                                                                                                      
          foreach ( $pairs as $pair ) {                                                                                               
              $pair = trim( $pair );                                                                                                  
              if ( empty( $pair ) ) {                                                                                                 
                  continue;                                                                                                           
              }                                                                                                                       
                                                                                                                                      
              // Split by first colon only                                                                                            
              $colon_pos = strpos( $pair, ':' );                                                                                      
              if ( $colon_pos === false ) {                                                                                           
                  continue;                                                                                                           
              }                                                                                                                       
                                                                                                                                      
              $product_id = trim( substr( $pair, 0, $colon_pos ) );                                                                   
              $background = trim( substr( $pair, $colon_pos + 1 ) );
                                                                                                                                      
              if ( is_numeric( $product_id ) && ! empty( $background ) ) {                                                            
                  $product_backgrounds[ absint( $product_id ) ] = $background;                                                        
              }                                                                                                                       
          }                                                                                                                           
                                                                                                                                      
          return $product_backgrounds;                                                                                                
      }   

      private function get_product_background( $product_id, $product_backgrounds, $default_bg ) {     
          if ( isset( $product_backgrounds[ $product_id ] ) ) {
              $bg = $product_backgrounds[ $product_id ];                                              
              if ( $this->is_valid_background( $bg ) ) {                                              
                  return $this->format_background( $bg );                                             
              }                                                                                       
          }                                                                                           
          return $this->format_background( $default_bg );                                             
      }                                                                                               
  
                                                                                             

    private function render_slide( $product, $atts, $background ) {
        $title = get_the_title( $product->get_id() );
        $excerpt = wp_trim_words( $product->get_short_description(), 15 );
        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( $product->get_description(), 15 );
        }

        $tag = $this->get_product_tag( $product );
        $price = $product->get_price_html();
        $link = get_permalink( $product->get_id() );

        echo '<div class="shopglut-carousel-slide">';
        echo '<div class="shopglut-slide-bg" style="background: ' . esc_attr( $background ) . ';"></div>';
        echo '<div class="shopglut-slide-content">';
        echo '<div class="shopglut-slide-text">';

        if ( $tag ) {
            echo '<span class="shopglut-slide-tag">' . esc_html( $tag ) . '</span>';
        }

        echo '<h2 class="shopglut-slide-title">' . esc_html( $title ) . '</h2>';

        if ( $excerpt ) {
            echo '<p class="shopglut-slide-excerpt">' . esc_html( $excerpt ) . '</p>';
        }

        if ( $atts['show_price'] ) {
            echo '<div class="shopglut-slide-price">' . wp_kses_post( $price ) . '</div>';
        }

        if ( $atts['show_button'] ) {
            if ( $product->is_purchasable() && $product->is_in_stock() ) {
               echo '<button class="shopglut-slide-btn shortcodeglut-add-to-cart-btn ajax_add_to_cart" data-product_id="' . esc_attr(
  $product->get_id() ) . '" data-cart-url="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'Add to Cart', 'shortcodeglut' ) .
  '</button>'; 
            } else {
                echo '<a href="' . esc_url( $link ) . '" class="shopglut-slide-btn">' . esc_html__( 'View Details', 'shortcodeglut' ) . '</a>';
            }
        }

        echo '</div>';
        echo '<div class="shopglut-slide-visual">';
        echo '<div class="shopglut-slide-icon">';
        if ( $product->get_image_id() ) {
            echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'class' => 'shopglut-product-image' ) );
        } else {
            echo '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function render_empty_slide() {
        echo '<div class="shopglut-carousel-slide">';
        echo '<div class="shopglut-slide-bg"></div>';
        echo '<div class="shopglut-slide-content">';
        echo '<div class="shopglut-slide-text">';
        echo '<h2 class="shopglut-slide-title">' . esc_html__( 'No Products Found', 'shortcodeglut' ) . '</h2>';
        echo '<p class="shopglut-slide-excerpt">' . esc_html__( 'Please add some products to display them in the carousel.', 'shortcodeglut' ) . '</p>';
        echo '</div>';
        echo '<div class="shopglut-slide-visual">';
        echo '<div class="shopglut-slide-icon">';
        echo '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function get_product_tag( $product ) {
        $tags = array(
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' => 'Best Seller',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' => 'Hot Deal',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' => 'New Release',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' => 'Eco Friendly',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' => 'Limited',
            'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' => 'Premium',
        );

        $sales = get_post_meta( $product->get_id(), 'total_sales', true );
        $date_created = $product->get_date_created();

        if ( $sales > 50 ) {
            return 'Best Seller';
        } elseif ( $date_created && $date_created->date( 'U' ) > strtotime( '-30 days' ) ) {
            return 'New Release';
        } elseif ( $product->is_on_sale() ) {
            return 'Hot Deal';
        } elseif ( has_term( 'eco', 'product_cat', $product->get_id() ) || has_term( 'sustainable', 'product_cat', $product->get_id() ) ) {
            return 'Eco Friendly';
        } elseif ( $product->get_price() > 100 ) {
            return 'Premium';
        }

        return 'Featured';
    }

    private function get_gradient_colors() {
        return array(
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        );
    }

    private function parse_backgrounds( $backgrounds_string ) {
        if ( empty( $backgrounds_string ) ) {
            return array();
        }
        return array_map( 'trim', explode( ',', $backgrounds_string ) );
    }

    private function get_slide_background( $slide_index, $parsed_backgrounds, $default_bg ) {
        if ( isset( $parsed_backgrounds[ $slide_index ] ) ) {
            $bg = $parsed_backgrounds[ $slide_index ];
            if ( $this->is_valid_background( $bg ) ) {
                return $this->format_background( $bg );
            }
        }
        return $this->format_background( $default_bg );
    }

    private function is_valid_background( $bg ) {
        $trimmed = trim( $bg );

        // Check if it's a URL
        if ( preg_match( '/^url\(/i', $trimmed ) || filter_var( $trimmed, FILTER_VALIDATE_URL ) ) {
            return true;
        }

        // Check if it's a gradient (linear or radial)
        if ( preg_match( '/^(linear|radial|conic)-gradient\(/i', $trimmed ) ) {
            return true;
        }

        // Check if it's a hex color
        if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $trimmed ) ) {
            return true;
        }

        // Check if it's rgb/rgba color
        if ( preg_match( '/^rgba?\(/i', $trimmed ) ) {
            return true;
        }

        // Check if it's a named color (basic validation)
        $named_colors = array( 'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink', 'brown', 'black', 'white', 'gray', 'grey', 'transparent' );
        if ( in_array( strtolower( $trimmed ), $named_colors, true ) ) {
            return true;
        }

        return false;
    }

    private function format_background( $bg ) {
        $trimmed = trim( $bg );

        // If it's already a URL with url() wrapper, return as-is
        if ( preg_match( '/^url\(/i', $trimmed ) ) {
            return $trimmed;
        }

        // If it's a plain URL, wrap it in url()
        if ( filter_var( $trimmed, FILTER_VALIDATE_URL ) ) {
            return 'url(' . $trimmed . ')';
        }

        // For colors and gradients, return as-is
        return $trimmed;
    }
}
