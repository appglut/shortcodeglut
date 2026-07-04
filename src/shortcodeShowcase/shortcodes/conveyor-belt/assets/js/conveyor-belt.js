/**
 * Conveyor Belt Shortcode JavaScript
 *
 * Handles infinite scrolling animation and add to cart functionality
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        ShortcodeglutConveyor.init();
    });

    var ShortcodeglutConveyor = {
        wrapper: null,
        settings: null,

        /**
         * Initialize the conveyor belt functionality
         */
        init: function() {
            this.wrapper = $('.shortcodeglut-conveyor-wrapper');

            if (this.wrapper.length === 0) {
                return;
            }

            // Get settings from WordPress localize
            if (typeof shortcodeglutConveyorAjax !== 'undefined') {
                this.settings = shortcodeglutConveyorAjax;
            }

            // Bind add to cart buttons
            this.bindAddToCart();

            // Pause animation on touch devices for better UX
            this.handleTouchDevices();
        },

        /**
         * Bind add to cart button click events
         */
        bindAddToCart: function() {
            this.wrapper.on('click', '.ajax_add_to_cart', function(e) {
                e.preventDefault();

                var $btn = $(this);
                var productId = $btn.data('product_id');
                var productUrl = $btn.data('product-url');

                // Check if button is already loading
                if ($btn.hasClass('shortcodeglut-loading') || $btn.hasClass('shortcodeglut-added')) {
                    return;
                }

                // Add loading state
                $btn.addClass('shortcodeglut-loading');
                var originalText = $btn.html();
                $btn.html('');

                // Add product to cart via AJAX
                $.ajax({
                    type: 'POST',
                    url: shortcodeglutConveyorAjax.ajax_url,
                    data: {
                        action: 'shortcodeglut_ajax_add_to_cart',
                        product_id: productId,
                        quantity: 1,
                        nonce: shortcodeglutConveyorAjax.nonce
                    },
                    success: function(response) {
                        $btn.removeClass('shortcodeglut-loading').addClass('shortcodeglut-added');
                        $btn.html('Added!');

                        // Update cart count if available
                        if (typeof wc_add_to_cart_params !== 'undefined') {
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                        }

                        // Transform to View Cart button (no redirect)
                        setTimeout(function() {
                            var cartUrl = wc_add_to_cart_params ? wc_add_to_cart_params.cart_url : '/cart/';
                            $btn.removeClass('shortcodeglut-added ajax_add_to_cart add_to_cart_button')
                                .addClass('shortcodeglut-view-cart')
                                .removeAttr('data-product-id')
                                .attr('data-cart-url', cartUrl)
                                .html('<i class="fa-solid fa-eye"></i> View Cart');
                        }, 600);
                    },
                    error: function() {
                        $btn.removeClass('shortcodeglut-loading');
                        $btn.html(originalText);

                        // Don't redirect - just show error in console
                        console.error('Failed to add to cart');
                    }
                });
            });
        },

        /**
         * Handle touch devices
         * Pause animation on touch devices for better UX
         */
        handleTouchDevices: function() {
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                // Pause animation on touch devices
                this.wrapper.find('.shortcodeglut-conveyor-track').css('animation-play-state', 'paused');

                // Add touch indicator
                this.wrapper.each(function() {
                    var $wrapper = $(this);
                    if (!$wrapper.find('.shortcodeglut-touch-hint').length) {
                        $wrapper.append('<div class="shortcodeglut-touch-hint">Swipe to browse</div>');
                    }
                });
            }
        },

        /**
         * Pause/Resume animation
         */
        pauseAnimation: function() {
            this.wrapper.find('.shortcodeglut-conveyor-track').css('animation-play-state', 'paused');
        },

        /**
         * Resume animation
         */
        resumeAnimation: function() {
            this.wrapper.find('.shortcodeglut-conveyor-track').css('animation-play-state', 'running');
        },

        /**
         * Update animation speed
         */
        updateSpeed: function(speed) {
            var durations = {
                'slow': '60s',
                'continuous': '40s',
                'fast': '20s',
                'paused': '0s'
            };

            if (durations[speed]) {
                this.wrapper.find('.shortcodeglut-conveyor-track').css('animation-duration', durations[speed]);
            }
        }
    };

    // Make available globally
    window.ShortcodeglutConveyor = ShortcodeglutConveyor;

    // Handle visibility change to pause animation when tab is not visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && ShortcodeglutConveyor.wrapper) {
            ShortcodeglutConveyor.wrapper.find('.shortcodeglut-conveyor-track').css('animation-play-state', 'paused');
        } else if (!document.hidden && ShortcodeglutConveyor.wrapper) {
            ShortcodeglutConveyor.wrapper.find('.shortcodeglut-conveyor-track').css('animation-play-state', 'running');
        }
    });

})(jQuery);
