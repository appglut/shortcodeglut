/**
 * Magazine Grid Shortcode JavaScript
 * Handles grid layout and product interactions
 */

(function($) {
    'use strict';

    const ShortcodeglutMagazine = {
        /**
         * Initialize the magazine grid functionality
         */
        init: function() {
            $('.shortcodeglut-magazine-wrapper').each(function() {
                const $wrapper = $(this);
                const layout = $wrapper.data('layout') || 'masonry';
                const pagination = $wrapper.data('pagination') === 'true' || $wrapper.data('pagination') === true;

                // Store settings
                $wrapper.data('current-page', 1);

                // Grid item click handlers
                $wrapper.find('.shortcodeglut-magazine-item').on('click', function(e) {
                    const productId = $(this).data('product-id');
                    if (productId && !$(this).find('.shortcodeglut-magazine-placeholder').length) {
                        ShortcodeglutMagazine.loadProduct($wrapper, productId);
                    }
                });

                // Load more button
                if (pagination) {
                    $wrapper.find('.shortcodeglut-magazine-load-more').on('click', function(e) {
                        e.preventDefault();
                        ShortcodeglutMagazine.loadMore($wrapper);
                    });
                }
            });

            // Modal close handlers
            $(document).on('click', '.shortcodeglut-magazine-modal-backdrop', function() {
                $(this).closest('.shortcodeglut-magazine-modal').removeClass('active');
            });

            $(document).on('click', '.shortcodeglut-magazine-modal-close', function(e) {
                e.preventDefault();
                $(this).closest('.shortcodeglut-magazine-modal').removeClass('active');
            });

            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.shortcodeglut-magazine-modal.active').removeClass('active');
                }
            });
        },

        /**
         * Load product details via AJAX
         */
        loadProduct: function($wrapper, productId) {
            const $modal = $wrapper.find('.shortcodeglut-magazine-modal');
            const $content = $modal.find('.shortcodeglut-magazine-modal-content');

            $content.html('<div class="shortcodeglut-loading">Loading...</div>');
            $modal.addClass('active');

            $.ajax({
                url: shortcodeglut_magazine.ajax_url,
                type: 'POST',
                data: {
                    action: 'shortcodeglut_magazine_load',
                    nonce: shortcodeglut_magazine.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(response.data.html);
                    } else {
                        $content.html('<div class="shortcodeglut-error">Error loading product</div>');
                    }
                },
                error: function() {
                    $content.html('<div class="shortcodeglut-error">Error loading product</div>');
                }
            });
        },

        /**
         * Load more products (pagination)
         */
        loadMore: function($wrapper) {
            const $button = $wrapper.find('.shortcodeglut-magazine-load-more');
            const $grid = $wrapper.find('.shortcodeglut-magazine-grid');
            const currentPage = $wrapper.data('current-page') || 1;
            const nextPage = currentPage + 1;

            // Get settings from data attributes
            const layout = $wrapper.data('layout') || 'masonry';
            const limit = $wrapper.data('limit') || 12;
            const showOverlay = $wrapper.data('showOverlay') === 'true' || $wrapper.data('showOverlay') === true;
            const showPrice = $wrapper.data('showPrice') === 'true' || $wrapper.data('showPrice') === true;
            const showCategory = $wrapper.data('showCategory') === 'true' || $wrapper.data('showCategory') === true;

            $button.addClass('loading').text(shortcodeglut_magazine.strings.loading);

            $.ajax({
                url: shortcodeglut_magazine.ajax_url,
                type: 'POST',
                data: {
                    action: 'shortcodeglut_magazine_load_more',
                    nonce: shortcodeglut_magazine.nonce,
                    page: nextPage,
                    limit: limit,
                    layout: layout,
                    show_overlay: showOverlay,
                    category: '',
                    show_price: showPrice,
                    show_category: showCategory
                },
                success: function(response) {
                    if (response.success) {
                        // Append new items
                        $grid.append(response.data.html);
                        $wrapper.data('current-page', nextPage);

                        // Update button visibility
                        if (!response.data.has_more) {
                            $button.remove();
                        } else {
                            $button.removeClass('loading').text(shortcodeglut_magazine.strings.load_more);
                        }
                    } else {
                        $button.removeClass('loading').text(shortcodeglut_magazine.strings.load_more);
                    }
                },
                error: function(xhr, status, error) {
                    $button.removeClass('loading').text(shortcodeglut_magazine.strings.load_more);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if (typeof shortcodeglut_magazine !== 'undefined') {
            ShortcodeglutMagazine.init();
        }
    });

})(jQuery);
