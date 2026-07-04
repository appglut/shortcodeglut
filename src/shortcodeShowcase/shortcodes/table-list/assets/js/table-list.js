/**
 * Table List Shortcode JavaScript
 * Handles AJAX pagination and column sorting for table list shortcode
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        initTablePagination();
        initTableSorting();
    });

    /**
     * Initialize AJAX pagination
     */
    function initTablePagination() {
        $('.shortcodeglut-pagination.async-pagination').each(function() {
            const $pagination = $(this);
            const $wrapper = $pagination.closest('.shortcodeglut-archive-wrapper');

            // Handle pagination clicks
            $pagination.off('click.page-numbers').on('click.page-numbers', 'a.page-numbers', function(e) {
                e.preventDefault();

                const $link = $(this);
                const page = $link.data('page');

                if (!page) return;

                const wrapperId = $wrapper.attr('id');
                const baseId = wrapperId.replace('_wrapper', '');
                const contentId = 'content_' + baseId;

                // Check if element exists before loading
                if ($('#' + contentId).length === 0) {
                    return;
                }

                loadTableContent($wrapper, { paged: page });
            });
        });
    }

    /**
     * Initialize table column sorting
     */
    function initTableSorting() {
        $('.shortcodeglut-table-sortable').off('click.sort').on('click.sort', function(e) {
            e.preventDefault();

            const $header = $(this);
            const $wrapper = $header.closest('.shortcodeglut-archive-wrapper');

            if (!$wrapper.length) return;

            const sortBy = $header.data('sort');
            if (!sortBy) return;

            // Get current sort state
            let currentOrderBy = $wrapper.data('order-by') || 'title';
            let currentOrder = $wrapper.data('order') || 'ASC';

            // Determine new sort order
            let newOrder = 'ASC';
            if (currentOrderBy === sortBy) {
                // Toggle order if clicking same column
                newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            }

            // Load content with new sort
            loadTableContent($wrapper, {
                order_by: sortBy,
                order: newOrder,
                paged: 1
            });
        });
    }

    /**
     * Load table content via AJAX
     */
    function loadTableContent($wrapper, extraData) {
        // Check if AJAX object is defined
        if (typeof shortcodeglutTableAjax === 'undefined') {
            $wrapper.removeClass('loading');
            return;
        }

        // Show loading state
        $wrapper.addClass('loading');

        const wrapperId = $wrapper.attr('id');
        // Remove _wrapper suffix and add content_ prefix
        const baseId = wrapperId.replace('_wrapper', '');
        const contentId = 'content_' + baseId;

        // Check if content element exists
        if ($('#' + contentId).length === 0) {
            $wrapper.removeClass('loading');
            return;
        }

        // Get attributes from wrapper
        let atts = {};
        try {
            const attsString = $wrapper.attr('data-atts') || '{}';
            atts = JSON.parse(attsString);
        } catch (e) {
            atts = {};
        }

        // Merge with extra data
        const requestData = $.extend({}, atts, extraData);

        // AJAX request
        $.ajax({
            url: shortcodeglutTableAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'shortcodeglut_table_list_load',
                nonce: shortcodeglutTableAjax.nonce,
                ...requestData
            },
            success: function(response) {
                if (response.success && response.data && response.data.html) {
                    const $contentElement = $('#' + contentId);
                    if ($contentElement.length === 0) {
                        $wrapper.removeClass('loading');
                        return;
                    }

                    // Update content
                    $contentElement.html(response.data.html);

                    // Update wrapper data attributes if sorting changed
                    if (requestData.order_by) {
                        $wrapper.data('order-by', requestData.order_by);
                        $wrapper.attr('data-order-by', requestData.order_by);
                    }
                    if (requestData.order) {
                        $wrapper.data('order', requestData.order);
                        $wrapper.attr('data-order', requestData.order);
                    }

                     // Reinitialize pagination for the new content
				const $newPagination = $contentElement.find('.shortcodeglut-pagination.async-pagination');
				if ($newPagination.length > 0) {
					$newPagination.off('click.page-numbers').on('click.page-numbers', 'a.page-numbers', function(e) {
						e.preventDefault();
						const $link = $(this);
						const page = $link.data('page');
						if (!page) return;
						loadTableContent($wrapper, { paged: page });                                                                                                  
					});
				}

				 // Reinitialize sorting for the new content
		const $newHeaders = $contentElement.find('.shortcodeglut-table-sortable');
		$newHeaders.off('click.sort').on('click.sort', function(e) {
			e.preventDefault();
			const $header = $(this);
			const sortBy = $header.data('sort');
			if (!sortBy) return;

			let currentOrderBy = $wrapper.data('order-by') || 'title';
			let currentOrder = $wrapper.data('order') || 'ASC';
			let newOrder = 'ASC';
			if (currentOrderBy === sortBy) {                                                                                                                  
				newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
			}                                                                                                                                                 
																	
			loadTableContent($wrapper, {
				order_by: sortBy,
				order: newOrder,
				paged: 1
			});
		});

                    // Scroll to top of list
                    $('html, body').animate({
                        scrollTop: $wrapper.offset().top - 50
                    }, 300);
                } else {
                    $('#' + contentId).html('<p class="shortcodeglut-error">' + (shortcodeglut_i18n.error_loading || 'Error loading products') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#' + contentId).html('<p class="shortcodeglut-error">Error: ' + error + '</p>');
            },
            complete: function() {
                // Remove loading state
                $wrapper.removeClass('loading');
            }
        });
    }

    // Add to cart AJAX handler (separate from table functionality)
    $(document).on('click', '.shortcodeglut-add-to-cart-btn.ajax_add_to_cart', function(e) {
        e.preventDefault();

        const $button = $(this);
        const productId = $button.data('product_id');

        if (!productId) return;

        // Show loading state
        $button.addClass('loading');

        $.ajax({
            url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
            type: 'POST',
            data: {
                product_id: productId,
                quantity: 1
            },
            success: function(response) {
                if (response.error) {
                    $button.removeClass('loading');
                    alert(response.error);
                } else {
                    $button.removeClass('loading').addClass('added');

                    // Trigger WooCommerce event
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                    // Reset button after delay
                    setTimeout(function() {
                        $button.removeClass('added');
                    }, 2000);
                }
            },
            error: function() {
                $button.removeClass('loading');
                alert(shortcodeglut_i18n.error_adding || 'Error adding product');
            }
        });
    });

})(jQuery);

// Internationalization strings
var shortcodeglut_i18n = shortcodeglut_i18n || {
    error_loading: 'Error loading products. Please try again.',
    add_to_cart: 'Add to Cart',
    adding: 'Adding...',
    added: 'Added!',
    error_adding: 'Error adding product to cart.'
};
