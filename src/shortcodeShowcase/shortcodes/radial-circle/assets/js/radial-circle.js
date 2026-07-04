/**
 * Radial Circle Shortcode JavaScript
 * Handles hover interactions and detail panel display
 */

(function($) {
    'use strict';

    const ShortcodeglutRadial = {
        /**
         * Initialize the radial circle functionality
         */
        init: function() {
            $('.shortcodeglut-radial-wrapper').each(function() {
                const $wrapper = $(this);

                // Load center product info by default
                const $centerProduct = $wrapper.find('.shortcodeglut-center-product');
                if ($centerProduct.length) {
                    const title = $centerProduct.data('title') || '';
                    const desc = $centerProduct.data('desc') || '';
                    const price = $centerProduct.data('price') || '';
                    const url = $centerProduct.data('url') || '';
                    ShortcodeglutRadial.showDetail($wrapper, title, desc, price, url);
                }

                // Hover handlers for orbit products
                $wrapper.find('.shortcodeglut-orbit-product').on('mouseenter', function() {
                    const $this = $(this);
                    const title = $this.data('title') || '';
                    const desc = $this.data('desc') || '';
                    const price = $this.data('price') || '';
                    const url = $this.data('url') || '';

                    ShortcodeglutRadial.showDetail($wrapper, title, desc, price, url);
                });

                // When mouse leaves orbit product, restore center product info
                $wrapper.find('.shortcodeglut-orbit-product').on('mouseleave', function() {
                    const title = $centerProduct.data('title') || '';
                    const desc = $centerProduct.data('desc') || '';
                    const price = $centerProduct.data('price') || '';
                    const url = $centerProduct.data('url') || '';
                    ShortcodeglutRadial.showDetail($wrapper, title, desc, price, url);
                });
            });

            // Click handler for detail button - redirect to product page
            $(document).on('click', '.shortcodeglut-detail-btn', function(e) {
                e.preventDefault();
                const $panel = $(this).closest('.shortcodeglut-detail-panel');
                const url = $panel.data('url') || '';
                if (url) {
                    window.location.href = url;
                }
            });
        },

        /**
         * Show detail panel with product information
         */
        showDetail: function($wrapper, title, desc, price, url) {
            const $panel = $wrapper.find('.shortcodeglut-detail-panel');
            if ($panel.length === 0) return;

            const $title = $panel.find('.shortcodeglut-detail-title');
            const $desc = $panel.find('.shortcodeglut-detail-desc');
            const $price = $panel.find('.shortcodeglut-detail-price');

            $title.text(title);
            $desc.text(desc);
            $price.html(price);
            $panel.data('url', url);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ShortcodeglutRadial.init();
    });

})(jQuery);
