/**
 * Shortcodeglut Add to Cart Button Handler
 * Handles "Add to Cart" -> "View Cart" button functionality
 */

(function($) {
    'use strict';

    // Store processed buttons to prevent duplicate transformations
    var processedButtons = {};

    // Handler for add to cart buttons
    $(document.body).on('click', '.shortcodeglut-add-to-cart-btn', function(e) {
        const $button = $(this);
        const buttonId = $button.attr('data-product-id') || Math.random().toString(36);
        const cartUrl = $button.data('cart-url');

        // Check if this is already a "View Cart" button
        if ($button.hasClass('shortcodeglut-view-cart')) {
            // Open cart in new tab
            e.preventDefault();
            window.open(cartUrl, '_blank');
            return false;
        }

        // Check if button is already in loading state
        if ($button.hasClass('shortcodeglut-add-to-cart-loading')) {
            e.preventDefault();
            return false;
        }

        // Prevent default behavior for AJAX add to cart
        if ($button.hasClass('ajax_add_to_cart') || $button.hasClass('add_to_cart_button')) {
            e.preventDefault();
        }

        // Mark this button as being processed
        if (!processedButtons[buttonId]) {
            processedButtons[buttonId] = true;

            // Show loading state
            showLoadingState($button);

            // For AJAX add to cart
            if ($button.hasClass('ajax_add_to_cart') || $button.hasClass('add_to_cart_button')) {
                // Trigger WooCommerce AJAX add to cart
                const productId = $button.data('product-id');
                const quantity = $button.data('quantity') || 1;

                $(document.body).trigger('added_to_cart', []);

                // Make AJAX call to add product to cart
                // Build AJAX URL with fallback
                var ajaxUrl = (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url)
                    ? wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart')
                    : '/?wc-ajax=add_to_cart';

                $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        product_id: productId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (!response.error) {
                            // Trigger WooCommerce events
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                            // Small delay to show the loading effect
                            setTimeout(function() {
                                transformToViewCart($button, cartUrl);
                            }, 600);
                        } else {
                            // On error, reset the button
                            $button.removeClass('shortcodeglut-add-to-cart-loading');
                            $button.html($button.data('original-html'));
                            $button.css('opacity', '1');
                        }
                    },
                    error: function() {
                        // On error, reset the button
                        $button.removeClass('shortcodeglut-add-to-cart-loading');
                        $button.html($button.data('original-html'));
                        $button.css('opacity', '1');
                    }
                });
            } else {
                // For non-AJAX, transform immediately on click
                $button.one('click', function(e) {
                    e.preventDefault();
                    transformToViewCart($button, cartUrl);
                });
            }
        }
    });

    // Show loading state on button
    function showLoadingState($button) {
        // Store original button content
        var originalHtml = $button.html();
        $button.data('original-html', originalHtml);

        // Add loading class
        $button.addClass('shortcodeglut-add-to-cart-loading');

        // Hide WooCommerce's loader elements
        $('.blockUI').hide();
        $('body').removeClass('block-ui');
        $('.woocommerce-loader').hide();

        // Fade out current content
        $button.css('opacity', '0.6');

        // Show loading spinner
        $button.html('<i class="fa-solid fa-spinner fa-spin"></i> Adding...');
    }

    // Transform button to "View Cart"
    function transformToViewCart($button, cartUrl) {
        // Prevent double transformation
        if ($button.hasClass('shortcodeglut-view-cart')) {
            return;
        }

        // Remove loading class and restore opacity
        $button.removeClass('shortcodeglut-add-to-cart-loading');

        // Fade out effect before transformation
        $button.css({
            'opacity': '0.4',
            'transition': 'opacity 0.2s ease-out'
        });

        // Update button appearance after fade
        setTimeout(function() {
            $button.removeClass('ajax_add_to_cart add_to_cart_button added')
                      .addClass('shortcodeglut-view-cart')
                      .attr('href', cartUrl);

            // Clear button content and add new content
            $button.html('<i class="fa-solid fa-eye"></i> View Cart');

            // Fade in the new content
            $button.css({
                'opacity': '1',
                'transition': 'opacity 0.3s ease-in'
            });

            // Remove any WooCommerce-added "View cart" link that appears after the button
            $button.parent().find('.added_to_cart').remove();

            // Add click handler for new tab
            $button.off('click').on('click', function(e) {
                e.preventDefault();
                window.open(cartUrl, '_blank');
                return false;
            });
        }, 200);
    }

    // Listen for WooCommerce AJAX added_to_cart event
    $(document.body).on('added_to_cart', function(e, fragments, cart_hash, $button) {
        if ($button && $button.hasClass('shortcodeglut-add-to-cart-btn')) {
            // Get cart URL from button data, WooCommerce params, or fallback to /cart/
            var actualCartUrl = $button.data('cart-url');
            if (!actualCartUrl) {
                actualCartUrl = (typeof wc_add_to_cart_params !== 'undefined')
                    ? (wc_add_to_cart_params.cart_url || wc_add_to_cart_params.wc_cart_url || '/cart/')
                    : '/cart/';
            }
            transformToViewCart($button, actualCartUrl);
        }
    });

    // Prevent WooCommerce from adding "View cart" link after our buttons
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('wc-ajax') !== -1) {
            // Remove any "View cart" links that appear after our custom buttons
            $('.shortcodeglut-view-cart').parent().find('.added_to_cart').remove();
        }
    });

    // Aggressively hide WooCommerce loaders for our buttons
    function hideWooCommerceLoaders() {
        // Hide all WooCommerce loader elements
        $('.blockUI, .blockOverlay, .blockElement, .woocommerce-loader').each(function() {
            var $loader = $(this);
            // Check if it's related to our buttons
            if ($loader.closest('.shortcodeglut-product-card, .shortcodeglut-add-to-cart-btn').length ||
                !$loader.closest('.shortcodeglut-add-to-cart-btn').length) {
                $loader.hide();
            }
        });
    }

    // Run on interval to catch any loaders that appear
    setInterval(hideWooCommerceLoaders, 100);

    // Also run on DOM changes
    var observer = new MutationObserver(function(mutations) {
        hideWooCommerceLoaders();
    });
    observer.observe(document.body, { childList: true, subtree: true });

})(jQuery);
