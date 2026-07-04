/**
 * Drawer Panels Shortcode - Add to Cart JavaScript
 * Handles AJAX add to cart functionality
 */

(function($) {
    'use strict';

    $(document.body).on('click', '.shortcodeglut-drawer .shortcodeglut-panel-btn.ajax_add_to_cart', function(e) {
        if (typeof wc_add_to_cart_params === 'undefined') {
            return;
        }

        const $button = $(this);
        const productId = $button.data('product_id');
        const productUrl = $button.data('product-url');

        e.preventDefault();
        e.stopPropagation();

        // Check if product is already in cart - do nothing, just show "View Cart"
        if ($button.hasClass('added')) {
            return;
        }

        // Show loading state
        $button.addClass('loading');

        // AJAX add to cart
        $.ajax({
            url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
            type: 'POST',
            data: {
                product_id: productId
            },
            success: function(response) {
                if (response.error) {
                    // On error, just remove loading state
                    $button.removeClass('loading');
                    return;
                }

                // Update button state
                $button.removeClass('loading');
                $button.addClass('added');

                // Update button text to "View Cart" - no redirection
                $button.text('View Cart');

                // Trigger WooCommerce event
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                // Update cart fragments
                if (response.fragments) {
                    $.each(response.fragments, function(key, value) {
                        $(key).replaceWith(value);
                    });
                }
            },
            error: function(xhr, status, error) {
                // On error, just remove loading state
                $button.removeClass('loading');
            }
        });
    });

})(jQuery);
