/**
 * Horizontal Image Left Shortcode - Add to Cart Handler
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    $(document).on('click', '.shortcodeglut-horizontal-left-btn-primary.ajax_add_to_cart', function(e) {
        e.preventDefault();

        const $button = $(this);
        const productId = $button.data('product_id');
        const productUrl = $button.data('product_url');
        const cartUrl = $button.data('cart_url');

        // Check if product is simple
        if (!$button.hasClass('product_type_simple')) {
            window.location.href = productUrl;
            return;
        }

        // Show loading state
        $button.addClass('loading').text('Adding...');

        $.ajax({
            type: 'POST',
            url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
            data: {
                product_id: productId
            },
            success: function(response) {
                if (response.error) {
                    window.location.href = productUrl;
                    return;
                }

                $button.removeClass('loading').addClass('added').text('Added!');

                // Redirect to cart if enabled
                if (wc_add_to_cart_params.cart_redirect_after_add === 'yes') {
                    window.location.href = cartUrl;
                    return;
                }

                // Trigger fragments refresh
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                // Reset button after delay
                setTimeout(function() {
                    $button.removeClass('added').text('Add to Cart');
                }, 2000);
            },
            error: function() {
                window.location.href = productUrl;
            }
        });
    });

    // AJAX Pagination
    $(document).on('click', '.shortcodeglut-pagination.async-pagination a', function(e) {
        e.preventDefault();

        const $link = $(this);
        const page = $link.data('page');
        const wrapper = $link.closest('.shortcodeglut-horizontal-left-wrapper');
        const atts = wrapper.data('atts');

        if (!page || !atts) return;

        const content = wrapper.find('.shortcodeglut-horizontal-left-content');
        content.html('<p>Loading...</p>');

        $.ajax({
            type: 'POST',
            url: shortcodeglutHorizontalLeft.ajax_url,
            data: {
                action: 'shortcodeglut_horizontal_left_load',
                nonce: shortcodeglutHorizontalLeft.nonce,
                paged: page,
                ...atts
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                    $(document.body).trigger('shortcodeglut_products_loaded');
                }
            }
        });
    });

})(jQuery);
