/**
 * ShopGlut Sale Products Shortcode Script
 *
 * Handles asynchronous pagination for sale products
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    // Handle pagination clicks
    $(document).on('click', '.shopglut-sale-products-pagination.async-pagination a.page-numbers', function(e) {
        e.preventDefault();

        var $link = $(this);
        var $wrapper = $link.closest('.shopglut-sale-products-wrapper');
        var $content = $wrapper.find('.shopglut-sale-products-content');
        var page = $link.data('page');

        if (!page) {
            return;
        }

        // Get shortcode attributes from wrapper
        var atts = $wrapper.data('atts');

        // Add loading state
        $content.css('opacity', '0.5');

        // Send AJAX request
        $.ajax({
            url: shopglutSaleProductsAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'shopglut_sale_products_load',
                nonce: shopglutSaleProductsAjax.nonce,
                paged: page,
                limit: atts.limit || 12,
                orderby: atts.orderby || 'date',
                order: atts.order || 'DESC',
                category: atts.category || '',
                exclude: atts.exclude || '',
                template: atts.template || '',
                columns: atts.columns || 4,
                rows: atts.rows || 1,
                paging: atts.paging || 0,
                items_per_page: atts.items_per_page || 12,
                show_image: atts.show_image !== false,
                show_title: atts.show_title !== false,
                show_price: atts.show_price !== false,
                show_button: atts.show_button !== false,
                show_rating: atts.show_rating || false,
                show_badge: atts.show_badge !== false
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $content.html(response.data.html);
                    $content.css('opacity', '1');

                    // Trigger custom event for other scripts
                    $wrapper.trigger('shopglut_sale_products_loaded', [page]);
                }
            },
            error: function() {
                $content.css('opacity', '1');
            }
        });
    });

})(jQuery);
