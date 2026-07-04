/**
 * Category Tree Shortcode JavaScript
 * Handles expand/collapse functionality for category tree
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    // Global function for onclick attribute
    window.shortcodeglutToggleTree = function(element) {
        var $element = $(element);
        $element.toggleClass('expanded');

        var $subtree = $element.next('.shortcodeglut-subtree');
        if ($subtree.length) {
            $subtree.toggleClass('open');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        initCategoryTree();
    });

    /**
     * Initialize category tree interactions
     */
    function initCategoryTree() {
        $('.shortcodeglut-category-tree-wrapper').each(function() {
            var $wrapper = $(this);

            // Handle keyboard navigation
            $wrapper.find('.shortcodeglut-tree-link').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        });

        // Handle add to cart button clicks
        $(document).on('click', '.shortcodeglut-add-to-cart', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            var productId = $button.data('product_id');

            if (!productId) {
                return;
            }

            var ajaxUrl = (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url)
                ? wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart')
                : window.location.origin + '/?wc-ajax=add_to_cart';

            var originalHtml = $button.html();
            $button.html('<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="shortcodeglut-spinning"><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="2" fill="none"/></svg>');
            $button.css('pointer-events', 'none');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: 1
                },
                success: function(response) {
                    if (response.error) {
                        $button.html(originalHtml).css('pointer-events', '');
                        alert(response.product_error || response.error);
                    } else {
                        if (response.fragments) {
                            $.each(response.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }

                        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                        var cartUrl = (typeof wc_add_to_cart_params !== 'undefined')
                            ? (wc_add_to_cart_params.cart_url || wc_add_to_cart_params.wc_cart_url || window.location.origin + '/cart/')
                            : window.location.origin + '/cart/';

                        var $viewCartLink = $('<a>', {
                            'href': cartUrl,
                            'target': '_blank',
                            'class': 'shortcodeglut-view-cart',
                            'aria-label': 'View cart (opens in new tab)',
                            'title': 'View cart (opens in new tab)',
                            'html': '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.86-7.01L19.42 4l-3.87 7H8.53L4.27 2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2z"/></svg>'
                        });
                        $button.replaceWith($viewCartLink);
                    }
                },
                error: function() {
                    $button.html(originalHtml).css('pointer-events', '');
                    alert('Failed to add to cart. Please try again.');
                }
            });
        });

    }

})(jQuery);
