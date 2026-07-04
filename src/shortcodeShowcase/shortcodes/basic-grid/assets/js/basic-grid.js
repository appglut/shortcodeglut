/**
 * Shortcodeglut Basic Grid - AJAX/URL Sorting
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle sort dropdown change
        $(document).on('change', '.shortcodeglut-archive-wrapper .shortcodeglut-sort-select', function() {
            var orderBy = $(this).val();
            var $wrapper = $(this).closest('.shortcodeglut-archive-wrapper');

            // Get shortcode attributes from wrapper
            var attsJson = $wrapper.data('atts');
            var atts = typeof attsJson === 'string' ? JSON.parse(attsJson) : attsJson;

            // Check if AJAX is enabled
            var ajaxEnabled = (atts.ajax === 'on' || atts.ajax === '1' || atts.ajax === 'true');

            if (ajaxEnabled) {
                // Use AJAX - no URL change
                var $content = $wrapper.find('.shortcodeglut-grid-content');
                $content.css('opacity', '0.5');

                // Make AJAX request
                $.post(shortcodeglutGridAjax.ajax_url, {
                    action: 'shortcodeglut_grid_load',
                    nonce: shortcodeglutGridAjax.nonce,
                    paged: 1,
                    order_by: orderBy,
                    columns: atts.columns,
                    rows: atts.rows,
                    limit: atts.limit,
                    order: atts.order,
                    items_per_page: atts.items_per_page,
                    template: atts.template,
                    paging: atts.paging ? 1 : 0,
                    ajax: atts.ajax,
                    category: atts.category,
                    exclude: atts.exclude,
                    show_image: atts.show_image ? 1 : 0,
                    show_title: atts.show_title ? 1 : 0,
                    show_excerpt: atts.show_excerpt ? 1 : 0,
                    show_price: atts.show_price ? 1 : 0,
                    show_button: atts.show_button ? 1 : 0,
                    show_rating: atts.show_rating ? 1 : 0,
                    image_size: atts.image_size
                }, function(response) {
                    if (response.success && response.data.html) {
                        $content.html(response.data.html);
                        $content.css('opacity', '1');
                    }
                }).fail(function() {
                    $content.css('opacity', '1');
                });
            } else {
                // Use URL - redirect with sort parameter
                var url = new URL(window.location.href);
                url.searchParams.set('shortcodeglut_sort', orderBy);
                url.searchParams.delete('shortcodeglut_paged');
                window.location.href = url.toString();
            }
        });

        // Handle AJAX pagination (only when AJAX is enabled)
        $(document).on('click', '.shortcodeglut-archive-wrapper .async-pagination a', function(e) {
            e.preventDefault();

            var $link = $(this);
            var page = $link.attr('data-page'); // Use attr() to get raw string value
            var $wrapper = $link.closest('.shortcodeglut-archive-wrapper');
            var $content = $wrapper.find('.shortcodeglut-grid-content');
            var $sortSelect = $wrapper.find('.shortcodeglut-sort-select');

            // Convert to integer and validate
            page = parseInt(page, 10);
            if (isNaN(page) || page < 1) {
                return;
            }

            // Get shortcode attributes from wrapper
            var attsJson = $wrapper.data('atts');
            var atts = typeof attsJson === 'string' ? JSON.parse(attsJson) : attsJson;

            // Check if AJAX is enabled
            var ajaxEnabled = (atts.ajax === 'on' || atts.ajax === '1' || atts.ajax === 'true');

            if (!ajaxEnabled) {
                // If AJAX is disabled, let the default link behavior work
                return;
            }

            // Show loading state
            $content.css('opacity', '0.5');

            // Get current sort value
            var orderBy = $sortSelect.length > 0 ? $sortSelect.val() : atts.order_by;

            // Prepare AJAX data
            var data = {
                action: 'shortcodeglut_grid_load',
                nonce: shortcodeglutGridAjax.nonce,
                paged: page,  // page is already validated as integer
                order_by: orderBy,
                columns: atts.columns,
                rows: atts.rows,
                limit: atts.limit,
                order: atts.order,
                items_per_page: atts.items_per_page,
                template: atts.template,
                paging: atts.paging ? 1 : 0,
                ajax: atts.ajax,
                category: atts.category,
                exclude: atts.exclude,
                show_image: atts.show_image ? 1 : 0,
                show_title: atts.show_title ? 1 : 0,
                show_excerpt: atts.show_excerpt ? 1 : 0,
                show_price: atts.show_price ? 1 : 0,
                show_button: atts.show_button ? 1 : 0,
                show_rating: atts.show_rating ? 1 : 0,
                image_size: atts.image_size
            };

            // Make AJAX request
            $.ajax({
                url: shortcodeglutGridAjax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success && response.data.html) {
                        $content.html(response.data.html);
                        $content.css('opacity', '1');

                        // Scroll to top of content
                        $('html, body').animate({
                            scrollTop: $wrapper.offset().top - 100
                        }, 300);
                    }
                },
                error: function(xhr, status, error) {
                    $content.css('opacity', '1');
                }
            });
        });
    });

})(jQuery);
