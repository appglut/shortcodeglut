<?php
/**
 * Product Card: Basic Template
 * Template for rendering WooCommerce products in a basic card layout
 * Using conditional template tags instead of PHP
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="shortcodeglut-product-cards shortcodeglut-template-product-card-basic" data-product-id="[product_id]">
    <div class="product-design product-template1">
        <div class="product-item">
            <div class="product-thumb">
                <a href="[product_permalink]">
                    [product_image]
                </a>
                <div class="product-badge">
                    [is_new: <span class="new-badge product-label">[t:New]</span> ]
                    [is_on_sale: [product_badge_sale] ]
                </div>

                <div class="box-cart">
                    <div class="product-cart-action">
                        [is_in_stock: <button type="button"
                           data-product-id="[product_id]"
                           data-product-url="[product_permalink]"
                           data-cart-url="[cart_url]"
                           class="add_to_cart_button ajax_add_to_cart btn btn-cart shortcodeglut-add-to-cart-btn"
                           data-quantity="1">
                            <i class="fa-solid fa-cart-shopping"></i>
                            [t:Add to Cart]
                        </button> ]
                    </div>
                </div>
            </div>

            <div class="product-caption">
                <div class="product-identity">
                    [has_categories: <p class="product-category">
                        [product_categories]
                    </p> ]
                    [has_rating: <div class="ratings">
                        [product_rating]
                    </div> ]
                </div>

                <div class="product-identity">
                    <p class="product-title popup_cart_title">
                        <a href="[product_permalink]">[product_title]</a>
                    </p>
                    <div class="price-box">
                        [is_on_sale: <span class="product-price">
                            <span class="money">[product_sale_price]</span>
                        </span>
                        <span class="price-old">
                            <del>
                                <span class="money">[product_regular_price]</span>
                            </del>
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
