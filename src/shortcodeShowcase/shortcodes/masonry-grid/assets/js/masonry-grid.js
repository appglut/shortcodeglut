/**
 * Masonry Grid Shortcode JavaScript
 *
 * Handles sorting, pagination, and dynamic height adjustments
 * for the masonry grid layout
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        ShortcodeglutMasonry.init();
    });

    var ShortcodeglutMasonry = {
        wrapper: null,
        settings: null,

        /**
         * Initialize the masonry grid functionality
         */
        init: function() {
            this.wrapper = $('.shortcodeglut-masonry-wrapper');

            if (this.wrapper.length === 0) {
                return;
            }

            // Get settings from WordPress localize
            if (typeof shortcodeglutMasonryAjax !== 'undefined') {
                this.settings = shortcodeglutMasonryAjax;
            }

            // Bind sorting dropdown
            this.bindSorting();

            // Bind pagination
            this.bindPagination();

            // Bind add to cart buttons
            this.bindAddToCart();

            // Adjust card heights after images load
            this.adjustCardHeights();
        },

        /**
         * Bind sorting dropdown change event
         */
        bindSorting: function() {
            this.wrapper.on('change', '.shortcodeglut-sort-select', function() {
                var $select = $(this);
                var orderBy = $select.val();
                var $wrapper = $select.closest('.shortcodeglut-masonry-wrapper');

                // Update the results count text
                $wrapper.find('.shortcodeglut-results-count').text('Loading products...');

                // Trigger AJAX load with new sorting
                if (ShortcodeglutMasonry.settings && ShortcodeglutMasonry.settings.ajax_enabled) {
                    ShortcodeglutMasonry.loadProducts(1, orderBy);
                } else {
                    // Fallback to URL parameter method
                    var currentUrl = ShortcodeglutMasonry.settings ? ShortcodeglutMasonry.settings.current_url : window.location.href;
                    var newUrl = currentUrl + (currentUrl.indexOf('?') > -1 ? '&' : '?') + 'shortcodeglut_sort=' + orderBy;
                    window.location.href = newUrl;
                }
            });
        },

        /**
         * Bind pagination click events
         */
        bindPagination: function() {
            this.wrapper.on('click', '.shortcodeglut-pagination.async-pagination .page-numbers a', function(e) {
                e.preventDefault();

                var $link = $(this);
                var page = $link.data('page');

                if (!page) {
                    return;
                }

                // Get current sort order
                var orderBy = '';
                var $wrapper = $link.closest('.shortcodeglut-masonry-wrapper');
                var $sortSelect = $wrapper.find('.shortcodeglut-sort-select');

                if ($sortSelect.length > 0) {
                    orderBy = $sortSelect.val();
                }

                // Update current page indicator
                $wrapper.find('.shortcodeglut-pagination .page-numbers a').removeClass('current');
                $link.addClass('current');

                // Update results count
                $wrapper.find('.shortcodeglut-results-count').text('Loading products...');

                // Scroll to top of grid
                $('html, body').animate({
                    scrollTop: $wrapper.offset().top - 100
                }, 300);

                // Load products for this page
                ShortcodeglutMasonry.loadProducts(page, orderBy);
            });
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
                var cartUrl = $btn.data('cart-url');

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
                    url: shortcodeglutMasonryAjax.ajax_url,
                    data: {
                        action: 'shortcodeglut_ajax_add_to_cart',
                        product_id: productId,
                        quantity: 1,
                        nonce: shortcodeglutMasonryAjax.nonce
                    },
                    success: function(response) {
                        $btn.removeClass('shortcodeglut-loading').addClass('shortcodeglut-added');
                        $btn.html('Added!');

                        // Update cart count if available
                        if (typeof wc_add_to_cart_params !== 'undefined') {
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                        }

                        // Redirect to cart after a short delay
                        setTimeout(function() {
                            window.location.href = cartUrl || wc_add_to_cart_params.cart_url;
                        }, 500);
                    },
                    error: function() {
                        $btn.removeClass('shortcodeglut-loading');
                        $btn.html(originalText);

                        // Fallback to product page
                        if (productUrl) {
                            window.location.href = productUrl;
                        }
                    }
                });
            });
        },

        /**
         * Load products via AJAX
         */
        loadProducts: function(page, orderBy) {
            if (!this.settings || !this.settings.ajax_enabled) {
                return;
            }

            var $wrapper = this.wrapper;
            var $content = $wrapper.find('.shortcodeglut-masonry-content');

            // Get shortcode attributes from data attribute
            var atts = $wrapper.data('atts');
            if (!atts) {
                atts = {};
            }

            // Update with new page and sort order
            atts.paged = page;
            atts.order_by = orderBy || atts.order_by;

            // Add loading state
            $wrapper.addClass('loading');

            // AJAX request
            $.ajax({
                type: 'POST',
                url: this.settings.ajax_url,
                data: {
                    action: 'shortcodeglut_masonry_load',
                    nonce: this.settings.nonce,
                    paged: page,
                    order_by: orderBy || atts.order_by,
                    columns: atts.columns,
                    rows: atts.rows,
                    limit: atts.limit,
                    order: atts.order,
                    items_per_page: atts.items_per_page,
                    template: atts.template,
                    paging: atts.paging ? '1' : '0',
                    ajax: atts.ajax,
                    category: atts.category || '',
                    exclude: atts.exclude || '',
                    toolbar: atts.toolbar,
                    show_breadcrumb: atts.show_breadcrumb ? '1' : '0',
                    card_style: atts.card_style,
                    gap: atts.gap,
                    border_radius: atts.border_radius,
                    shadow: atts.shadow ? '1' : '0',
                    hover_lift: atts.hover_lift ? '1' : '0',
                    image_height: atts.image_height,
                    show_tags: atts.show_tags ? '1' : '0',
                    show_excerpt: atts.show_excerpt ? '1' : '0',
                    tag_color: atts.tag_color,
                    tag_text_color: atts.tag_text_color
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Update content
                        $content.html(response.data.html);

                        // Remove loading state
                        $wrapper.removeClass('loading');

                        // Re-adjust card heights for new content
                        ShortcodeglutMasonry.adjustCardHeights();

                        // Re-bind add to cart buttons
                        ShortcodeglutMasonry.bindAddToCart();

                        // Re-bind pagination
                        ShortcodeglutMasonry.bindPagination();
                    } else {
                        $wrapper.removeClass('loading');
                        $content.html('<p class="woocommerce-info">Error loading products. Please try again.</p>');
                    }
                },
                error: function() {
                    $wrapper.removeClass('loading');
                    $content.html('<p class="woocommerce-info">Error loading products. Please try again.</p>');
                }
            });
        },

        /**
         * Adjust card heights for better masonry layout
         * Called after images load and after AJAX content updates
         */
        adjustCardHeights: function() {
            // Wait for images to load
            var $images = this.wrapper.find('.shortcodeglut-card-image img');
            var imageCount = $images.length;
            var loadedCount = 0;

            if (imageCount === 0) {
                this.normalizeCardHeights();
                return;
            }

            $images.each(function() {
                if (this.complete) {
                    loadedCount++;
                    if (loadedCount === imageCount) {
                        ShortcodeglutMasonry.normalizeCardHeights();
                    }
                } else {
                    $(this).on('load', function() {
                        loadedCount++;
                        if (loadedCount === imageCount) {
                            ShortcodeglutMasonry.normalizeCardHeights();
                        }
                    });
                }
            });

            // Fallback timeout in case some images don't trigger load
            setTimeout(function() {
                ShortcodeglutMasonry.normalizeCardHeights();
            }, 2000);
        },

        /**
         * Normalize card heights within each column
         * This prevents items from getting stuck or having uneven gaps
         */
        normalizeCardHeights: function() {
            // Get the current column count from settings
            var columns = this.settings && this.settings.columns ? parseInt(this.settings.columns) : 4;
            var $masonry = this.wrapper.find('.shortcodeglut-masonry');
            var $items = $masonry.find('.shortcodeglut-masonry-item');

            if ($items.length === 0) {
                return;
            }

            // Force reflow to ensure masonry layout is calculated correctly
            $masonry.css('column-count', columns);

            // Ensure each card has proper break-inside to avoid column breaks
            $items.each(function() {
                $(this).css({
                    'break-inside': 'avoid',
                    'page-break-inside': 'avoid'
                });
            });
        },

        /**
         * Handle window resize
         * Adjust column count for responsive behavior
         */
        handleResize: function() {
            var width = $(window).width();
            var columns = 4;

            if (width <= 480) {
                columns = 1;
            } else if (width <= 768) {
                columns = 2;
            } else if (width <= 1200) {
                columns = 3;
            }

            var $masonry = this.wrapper.find('.shortcodeglut-masonry');
            $masonry.css('column-count', columns);
        }
    };

    // Handle window resize
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (ShortcodeglutMasonry.wrapper && ShortcodeglutMasonry.wrapper.length > 0) {
                ShortcodeglutMasonry.handleResize();
                ShortcodeglutMasonry.adjustCardHeights();
            }
        }, 250);
    });

    // Make available globally
    window.ShortcodeglutMasonry = ShortcodeglutMasonry;

})(jQuery);
