<?php
/**
 * Product Card: Vibrant Template
 * Template for rendering WooCommerce products with vibrant green and orange colors
 * Using conditional template tags instead of PHP
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;
?>
<div class="shortcodeglut-product-cards shortcodeglut-template-product-card-vibrant" data-product-id="[product_id]">
    <div class="product-template10">
        <div class="product-item">
            <div class="product-thumb">
                <a href="[product_permalink]">
                    <img src="[product_image_url]" alt="[product_title]">
                </a>
                <div class="product-badge">
                    [is_new: <span class="new-badge product-label">[t:New]</span> ]
                    [is_on_sale: [product_badge_sale] ]
                </div>
                <div class="box-cart">
                    [is_in_stock: <div class="product-cart-action">
                        <a href="[add_to_cart_url]"
                           data-product-id="[product_id]"
                           data-product-url="[product_permalink]"
                           data-cart-url="[cart_url]"
                           class="add_to_cart_button ajax_add_to_cart btn btn-cart shortcodeglut-add-to-cart-btn">
                            <span class="cart-title">
                                <i class="fa-solid fa-cart-shopping"></i>
                                [t:Add to Cart]
                            </span>
                        </a>
                    </div> ]
                </div>
            </div>
            <div class="product-caption">
                <div class="product-identity">
                    [has_rating: <div class="ratings">
                        [product_rating]
                    </div> ]
                    <div class="product-title">
                        <a href="[product_permalink]">[product_title]</a>
                    </div>
                    <div class="price-box">
                        [is_on_sale: <span class="price-old">
                            <del>[product_regular_price]</del>
                        </span>
                        <span class="product-price">
                            [product_sale_price]
                        </span> ]
                        [is_not_on_sale: <span class="product-price">
                            [product_price]
                        </span> ]
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
