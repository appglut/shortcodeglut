<?php
/**
 * Product Card: berry Template
 * Template for rendering WooCommerce products in horizontal layout with green accent
 * Using conditional template tags instead of PHP
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;
?>
<div class="shortcodeglut-product-cards shortcodeglut-template-product-card-berry" data-product-id="[product_id]">
    <div class="product-design product-template11">
        <div class="product-item">
            <div class="product-thumb">
                <a href="[product_permalink]">
                    <img class="popup_cart_image pri-img" src="[product_image_url]" alt="[product_title]">
                </a>
                <div class="product-badge">
                    [is_new: <span class="new-badge product-label">[t:New]</span> ]
                    [is_on_sale: [product_badge_sale] ]
                </div>
                <div class="hover-content">
                    <div class="product-caption">
                        <a href="[product_permalink]">[product_title]</a>
                    </div>
                    <div class="product-identity">
                        [has_categories: <p class="product-category">
                            [product_categories]
                        </p> ]
                        [has_rating: <div class="ratings">
                            [product_rating]
                        </div> ]
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
                    <div class="box-cart">
                        <div class="product-cart-action">
                            [is_in_stock: <a href="[add_to_cart_url]"
                               data-product-id="[product_id]"
                               data-product-url="[product_permalink]"
                               data-cart-url="[cart_url]"
                               class="add_to_cart_button ajax_add_to_cart btn btn-cart shortcodeglut-add-to-cart-btn">
                                <i class="fa-solid fa-cart-shopping"></i>
                                [t:Add to Cart]
                            </a> ]
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
