<?php
/**
 * Card: Horizontal Purple Template
 * Template for rendering WooCommerce products in horizontal layout with purple accent
 * Using conditional template tags instead of PHP
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;
?>
<div class="product-design product-card-horizontal-purple" data-product-id="[product_id]">
    <div class="cta-container">
        <div class="product-item">
            <div class="product-thumb">
                <a href="[product_permalink]">
                    <div class="pri-img" style="background-image: url('[product_image_url]');"></div>
                </a>
                <div class="product-badge">
                    [is_new: <span class="new-badge product-label">[t:New]</span> ]
                    [is_on_sale: [product_badge_sale] ]
                </div>
            </div>

            <div class="product-caption product-identity">
                <h3>
                    <a href="[product_permalink]">[product_title]</a>
                </h3>
                [has_categories: <span class="product-category">[t:Category:]
                    [product_categories]
                </span> ]
                [has_rating: <div class="ratings">[product_rating]</div> ]
                <p class="price-box">
                    [is_on_sale: <span class="product-price">
                        <span class="money">[product_sale_price]</span>
                    </span>
                    <span class="price-old">
                        <del>
                            <span class="money">[product_regular_price]</span>
                        </del>
                    </span> ]
                    [is_not_on_sale: <span class="product-price">
                        <span class="money">[product_price]</span>
                    </span> ]
                </p>
                [has_description: <p class="cta-description">[product_short_description]</p> ]
                <div class="cta-actions">
                    [is_in_stock: <div class="product-cart-action">
                        <a href="[add_to_cart_url]"
                           data-product_id="[product_id]"
                           data-product-url="[product_permalink]"
                           data-cart-url="[cart_url]"
                           class="add_to_cart_button ajax_add_to_cart btn-cart shortcodeglut-add-to-cart-btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            [t:Add to Cart]
                        </a>
                    </div> ]
                </div>
            </div>
        </div>
    </div>
</div>
