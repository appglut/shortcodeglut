/**
 * ShopGlut Category Shortcode - Async Loading
 * Handles AJAX pagination and form submissions for category shortcode
 */

(function($) {
    'use strict';

    /**
     * Category Shortcode Handler
     */
    var ShopglutCategoryShortcode = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Handle async form submissions
            $(document).on('submit', '.__shopglut_submit_async', this.handleAsyncFormSubmit);

            // Handle async pagination clicks
            $(document).on('click', '.pagination.async .page-link', this.handleAsyncPaginationClick);
        },

        /**
         * Handle async form submission
         */
        handleAsyncFormSubmit: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $container = $($form.data('container'));

            if ($container.length === 0) {
                return;
            }

            // Show loading state
            ShopglutCategoryShortcode.showLoading($container);

            // Get form data
            var formData = $form.serialize();

            // Get shortcode parameters from data attributes
            var shortcodeParams = ShopglutCategoryShortcode.getShortcodeParams($form);

            // Merge form data with shortcode params
            var postData = formData + '&' + $.param(shortcodeParams) + '&action=shopglut_woo_category_products&nonce=' + shopglutWooCategoryAjax.nonce;

            // Make AJAX request
            $.ajax({
                url: shopglutWooCategoryAjax.ajax_url,
                type: 'POST',
                data: postData,
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);

                        // Scroll to container
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 100
                        }, 500);
                    } else {
                        ShopglutCategoryShortcode.showError($container, response.data.message || 'An error occurred');
                    }
                },
                error: function() {
                    ShopglutCategoryShortcode.showError($container, 'Failed to load products. Please try again.');
                }
            });
        },

        /**
         * Handle async pagination click
         */
        handleAsyncPaginationClick: function(e) {
            e.preventDefault();

            var $link = $(this);
            var $pagination = $link.closest('.pagination');
            var $container = $pagination.closest('[id^="content_shopglut_woo_category_"]');

            if ($container.length === 0) {
                return;
            }

            // Get page number from link
            var pageUrl = $link.attr('href');
            var pageMatch = pageUrl.match(/\/page\/(\d+)/);
            var page = pageMatch ? pageMatch[1] : 1;

            // Show loading state
            ShopglutCategoryShortcode.showLoading($container);

            // Get form element
            var $form = $container.prev('form.__shopglut_submit_async');

            if ($form.length === 0) {
                // If no form found, try to find it above the container
                $form = $('[data-container="#' + $container.attr('id') + '"]');
            }

            // Get form data
            var formData = $form.length > 0 ? $form.serialize() : '';

            // Get shortcode parameters
            var shortcodeParams = ShopglutCategoryShortcode.getShortcodeParams($form);

            // Add page parameter
            shortcodeParams.paged = page;

            // Merge data
            var postData = formData + '&' + $.param(shortcodeParams) + '&action=shopglut_woo_category_products&nonce=' + shopglutWooCategoryAjax.nonce;

            // Make AJAX request
            $.ajax({
                url: shopglutWooCategoryAjax.ajax_url,
                type: 'POST',
                data: postData,
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);

                        // Scroll to container
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 100
                        }, 500);
                    } else {
                        ShopglutCategoryShortcode.showError($container, response.data.message || 'An error occurred');
                    }
                },
                error: function() {
                    ShopglutCategoryShortcode.showError($container, 'Failed to load products. Please try again.');
                }
            });
        },

        /**
         * Get shortcode parameters from form
         */
        getShortcodeParams: function($form) {
            var params = {};

            // Try to get params from data attributes
            if ($form.length > 0) {
                var dataAttrs = ['id', 'cat_field', 'operator', 'items_per_page', 'template', 'paging', 'cols', 'colspad', 'colsphone'];

                $.each(dataAttrs, function(index, attr) {
                    var value = $form.data('shortcode-' + attr);
                    if (value !== undefined) {
                        params[attr] = value;
                    }
                });
            }

            // Fallback: try to extract from container ID or other sources
            if (Object.keys(params).length === 0) {
                // Set some default params
                params = {
                    items_per_page: 10,
                    paging: 1,
                    cols: 1,
                    colspad: 1,
                    colsphone: 1
                };
            }

            return params;
        },

        /**
         * Show loading state
         */
        showLoading: function($container) {
            $container.html('<div class="shopglut-loading">Loading products...</div>');
        },

        /**
         * Show error message
         */
        showError: function($container, message) {
            $container.html('<p class="shopglut-error">' + message + '</p>');
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        ShopglutCategoryShortcode.init();
    });

})(jQuery);
