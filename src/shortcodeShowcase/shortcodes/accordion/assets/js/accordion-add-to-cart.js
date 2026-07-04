/**
 * Shortcodeglut Accordion Add to Cart Handler
 * Handles "Add to Cart" -> "View Cart" button functionality for accordion shortcode
 */

(function($) {
    'use strict';

    var processedButtons = {};

    $(document.body).on('click', '.shortcodeglut-accordion-cart .shortcodeglut-add-to-cart-btn', function(e) {
        var $button = $(this);
        var buttonId = $button.attr('data-product_id') || Math.random().toString(36);
        var cartUrl = $button.attr('data-cart-url');

        if ($button.hasClass('shortcodeglut-view-cart')) {
            e.preventDefault();
            window.open(cartUrl, '_blank');
            return false;
        }

        if ($button.hasClass('shortcodeglut-add-to-cart-loading')) {
            e.preventDefault();
            return false;
        }

        if ($button.hasClass('ajax_add_to_cart')) {
            e.preventDefault();
        }

        if (!processedButtons[buttonId]) {
            processedButtons[buttonId] = true;

            showLoadingState($button);

            if ($button.hasClass('ajax_add_to_cart')) {
                var productId = $button.attr('data-product_id');
                var quantity = $button.attr('data-quantity') || 1;

                var ajaxUrl = '';
                if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
                    ajaxUrl = wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
                } else if (typeof woocommerce_params !== 'undefined' && woocommerce_params.ajax_url) {
                    ajaxUrl = woocommerce_params.ajax_url;
                } else {
                    ajaxUrl = window.shortcodeglutAccordionAjax ? window.shortcodeglutAccordionAjax.ajax_url : '/wp-admin/admin-ajax.php';
                }

                $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        product_id: productId,
                        quantity: quantity
                    },
                    beforeSend: function(xhr) {
                        if (ajaxUrl.indexOf('admin-ajax.php') !== -1 && window.shortcodeglutAccordionAjax) {
                            xhr.setRequestHeader('X-WP-Nonce', window.shortcodeglutAccordionAjax.nonce);
                        }
                    },
                    success: function(response) {
                        if (!response.error) {
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                            setTimeout(function() {
                                transformToViewCart($button, cartUrl);
                            }, 600);
                        } else {
                            resetButton($button);
                            alert('Error: ' + (response.message || 'Could not add product to cart'));
                        }
                    },
                    error: function() {
                        resetButton($button);
                    }
                });
            }
        }
    });

    function showLoadingState($button) {
        var originalHtml = $button.html();
        $button.data('original-html', originalHtml);
        $button.addClass('shortcodeglut-add-to-cart-loading');
        $button.html('<i class="fa-solid fa-spinner fa-spin"></i> <span>Adding...</span>');
    }

    function resetButton($button) {
        $button.removeClass('shortcodeglut-add-to-cart-loading');
        var originalHtml = $button.data('original-html');
        if (originalHtml) {
            $button.html(originalHtml);
        }
        $button.css('opacity', '1');
    }

    function transformToViewCart($button, cartUrl) {
        if ($button.hasClass('shortcodeglut-view-cart')) {
            return;
        }

        $button.removeClass('shortcodeglut-add-to-cart-loading');

        $button.css({
            'opacity': '0.4',
            'transition': 'opacity 0.2s ease-out'
        });

        setTimeout(function() {
            $button.removeClass('ajax_add_to_cart add_to_cart_button added')
                .addClass('shortcodeglut-view-cart')
                .attr('href', cartUrl);

            $button.html('<i class="fa-solid fa-eye"></i> <span>View Cart</span>');

            $button.css({
                'opacity': '1',
                'transition': 'opacity 0.3s ease-in'
            });

            $button.off('click').on('click', function(e) {
                e.preventDefault();
                window.open(cartUrl, '_blank');
                return false;
            });
        }, 200);
    }

    // Listen for WooCommerce AJAX added_to_cart event
    $(document.body).on('added_to_cart', function(e, fragments, cart_hash, $thisButton) {
        if ($thisButton && $thisButton.hasClass('shortcodeglut-add-to-cart-btn') &&
            $thisButton.closest('.shortcodeglut-accordion-content').length) {
            var actualCartUrl = $thisButton.data('cart-url');
            if (!actualCartUrl) {
                actualCartUrl = (typeof wc_add_to_cart_params !== 'undefined')
                    ? (wc_add_to_cart_params.cart_url || wc_add_to_cart_params.wc_cart_url || '/cart/')
                    : '/cart/';
            }
            transformToViewCart($thisButton, actualCartUrl);
        }
    });

    // Hide WooCommerce loaders
    function hideWooCommerceLoaders() {
        $('.shortcodeglut-accordion-content .blockUI, .shortcodeglut-accordion-content .blockOverlay, .shortcodeglut-accordion-content.woocommerce-loader').hide();
    }

    setInterval(hideWooCommerceLoaders, 100);

    var observer = new MutationObserver(function() {
        hideWooCommerceLoaders();
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // Prevent WooCommerce from adding "View cart" link after our buttons
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('wc-ajax') !== -1) {
            $('.shortcodeglut-accordion-content .shortcodeglut-view-cart').parent().find('.added_to_cart').remove();
        }
    });

})(jQuery);
