/**
 * ShopGlut Product Table - DataTables Initialization
 * Initializes DataTables.js for product tables with sorting, searching, and pagination
 */

(function($) {
    'use strict';

    /**
     * Product Table Handler
     */
    var ShopglutProductTable = {

        /**
         * Initialize
         */
        init: function() {
            this.initDataTables();
            this.bindEvents();
        },

        /**
         * Initialize DataTables for all product tables
         */
        initDataTables: function() {
            $('.shopglut-product-table[data-options]').each(function() {
                var $table = $(this);
                var options = $table.data('options');

                // Check if DataTables is available
                if (typeof $.fn.DataTable === 'undefined') {
                    return;
                }

                // Initialize DataTable
                $table.DataTable({
                    pageLength: options.pageLength || 20,
                    paging: options.paging !== false,
                    searching: options.searching !== false,
                    ordering: true,
                    info: true,
                    responsive: options.responsive !== false,
                    language: {
                        search: '_INPUT_',
                        searchPlaceholder: 'Search products...',
                        lengthMenu: 'Show _MENU_ products per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ products',
                        infoEmpty: 'No products found',
                        infoFiltered: '(filtered from _MAX_ total products)',
                        paginate: {
                            first: 'First',
                            last: 'Last',
                            next: 'Next',
                            previous: 'Previous'
                        },
                        emptyTable: 'No products available'
                    },
                    columnDefs: [
                        { orderable: false, targets: '.no-sort' },
                        { searchable: false, targets: '.no-search' }
                    ],
                    dom: '<"shopglut-table-top"<"table-info"l>fr>t<"shopglut-table-bottom"ip>>',
                    drawCallback: function() {
                        // Re-bind events after table redraw
                        ShopglutProductTable.bindEvents();
                    }
                });

                // Add wrapper class
                $table.closest('.dataTables_wrapper').addClass('shopglut-datatables-wrapper');
            });
        },

        /**
         * Bind custom events
         */
        bindEvents: function() {
            // Handle add to cart button clicks
            $(document).on('click', '.shopglut-table-add-to-cart', this.handleAddToCart);

            // Handle view button clicks
            $(document).on('click', '.shopglut-table-view-product', this.handleViewProduct);
        },

        /**
         * Handle add to cart button click
         */
        handleAddToCart: function(e) {
            e.preventDefault();

            var $button = $(this);
            var productId = $button.data('product_id');

            if (!productId) {
                return;
            }

            // Show loading state
            var originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update dashicons-spin"></span> Adding...');
            $button.prop('disabled', true);

            // AJAX add to cart
            $.ajax({
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: 1
                },
                success: function(response) {
                    if (response.error) {
                        // Show error
                        $button.html(originalText);
                        $button.prop('disabled', false);
                        alert(response.error);

                        // Trigger event for other plugins
                        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                    } else {
                        // Success
                        if (response.fragments) {
                            // Update cart fragments
                            $.each(response.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }

                        // Update button
                        $button.removeClass('add_to_cart_button ajax_add_to_cart')
                            .addClass('added_to_cart_button')
                            .html('<span class="dashicons dashicons-cart"></span> Added!')
                            .prop('disabled', false);

                        // Restore button after delay
                        setTimeout(function() {
                            $button.removeClass('added_to_cart_button')
                                .addClass('add_to_cart_button ajax_add_to_cart')
                                .html(originalText);
                        }, 2000);

                        // Trigger event
                        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                    }
                },
                error: function() {
                    $button.html(originalText);
                    $button.prop('disabled', false);
                    alert('Failed to add to cart. Please try again.');
                }
            });
        },

        /**
         * Handle view product button click
         */
        handleViewProduct: function(e) {
            e.preventDefault();

            var url = $(this).attr('href');
            if (url) {
                window.location.href = url;
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        ShopglutProductTable.init();
    });

})(jQuery);
