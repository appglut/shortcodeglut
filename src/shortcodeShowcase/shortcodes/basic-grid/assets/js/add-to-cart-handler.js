/**
 * Shortcodeglut Add to Cart Button Handler
 * Handles "Add to Cart" -> "View Cart" button functionality
 */

(function($) {
    'use strict';

    console.log('Shortcodeglut Add to Cart Handler loaded');

    // Store processed buttons to prevent duplicate transformations
    var processedButtons = {};

    // Handler for add to cart buttons
    $(document.body).on('click', '.shortcodeglut-add-to-cart-btn', function(e) {
        const $button = $(this);
        const buttonId = $button.attr('data-product-id') || Math.random().toString(36);
        const cartUrl = $button.data('cart-url');

        console.log('Add to cart clicked, button classes:', $button.attr('class'));

        // Check if this is already a "View Cart" button
        if ($button.hasClass('shortcodeglut-view-cart')) {
            // Open cart in new tab
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log('Opening cart in new tab:', cartUrl);
            window.open(cartUrl, '_blank');
            return false;
        }

        // Check if button is already in loading state
        if ($button.hasClass('shortcodeglut-add-to-cart-loading')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log('Button already loading, ignoring click');
            return false;
        }

        // Prevent default behavior for AJAX add to cart - do this FIRST
        e.preventDefault();
        e.stopImmediatePropagation();

        console.log('Prevented default, starting AJAX add to cart for product:', buttonId);

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
                        console.log('AJAX success response:', response);

                        if (!response.error) {
                            // Mark as processed to prevent double-processing
                            processedButtons[buttonId] = 'completed';

                            // Trigger WooCommerce events
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                            // Small delay to show the loading effect
                            setTimeout(function() {
                                transformToViewCart($button, cartUrl);
                            }, 600);
                        } else {
                            console.log('AJAX returned error:', response);
                            // On error (variable product, etc), show message
                            if (response.product_url) {
                                // Variable product - show message instead of redirect
                                $button.removeClass('shortcodeglut-add-to-cart-loading');
                                $button.html('<i class="fa-solid fa-eye"></i> View Product');
                                $button.addClass('shortcodeglut-view-product');
                                $button.data('product-url', response.product_url);
                            } else {
                                // Reset button
                                $button.removeClass('shortcodeglut-add-to-cart-loading');
                                $button.html($button.data('original-html'));
                                $button.css('opacity', '1');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', xhr.status, error);
                        // On AJAX error, reset button - don't redirect
                        $button.removeClass('shortcodeglut-add-to-cart-loading');
                        $button.html($button.data('original-html'));
                        $button.css('opacity', '1');
                        console.error('Failed to add to cart:', xhr.responseText);
                    }
                });
            } else {
                // For non-AJAX, transform immediately on click
                $button.one('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    transformToViewCart($button, cartUrl);
                });
            }
        }
    });

    // Handler for View Cart buttons (separate from add to cart)
    $(document.body).on('click', '.shortcodeglut-view-cart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const cartUrl = $(this).data('cart-url') || '/cart/';
        console.log('View Cart clicked, opening:', cartUrl);
        window.open(cartUrl, '_blank');
        return false;
    });

    // Handler for View Product buttons (for variable products)
    $(document.body).on('click', '.shortcodeglut-view-product', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const productUrl = $(this).data('product-url');
        console.log('View Product clicked, opening:', productUrl);
        if (productUrl) {
            window.location.href = productUrl;
        }
        return false;
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

    // Transform button to "View Cart" - COMPLETELY REPLACE the button
    function transformToViewCart($button, cartUrl) {
        console.log('Transforming button to View Cart, cartUrl:', cartUrl);

        // Prevent double transformation
        if ($button.hasClass('shortcodeglut-view-cart')) {
            return;
        }

        // Remove loading class
        $button.removeClass('shortcodeglut-add-to-cart-loading');

        // Create a completely new button element
        var $newButton = $('<button>', {
            'type': 'button',
            'class': 'shortcodeglut-view-cart btn btn-cart',
            'html': '<i class="fa-solid fa-eye"></i> View Cart',
            'data-cart-url': cartUrl,
            'css': {
                'opacity': '0',
                'transition': 'opacity 0.3s ease-in',
                'pointer-events': 'none'  // Disable clicks during fade-in
            }
        });

        // Replace the old button with the new one
        $button.replaceWith($newButton);

        // Remove any WooCommerce-added "View cart" link
        $newButton.parent().find('.added_to_cart').remove();

        // Fade in the new button
        setTimeout(function() {
            $newButton.css({
                'opacity': '1',
                'pointer-events': 'auto'  // Re-enable clicks after fade-in
            });
            console.log('New button faded in and clickable');
        }, 300);

        console.log('Button replaced with new element');
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
