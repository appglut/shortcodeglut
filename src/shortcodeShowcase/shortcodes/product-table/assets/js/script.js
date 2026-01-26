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
            this.bindFilterEvents();
        },

        /**
         * Initialize DataTables for all product tables
         */
        initDataTables: function() {
            $('.shopglut-product-table[data-options]').each(function() {
                var $table = $(this);
                var options = $table.data('options');
                var tableId = $table.attr('id');

                // Check if DataTables is available
                if (typeof $.fn.DataTable === 'undefined') {
                    return;
                }

                // Initialize DataTable
                var dataTable = $table.DataTable({
                    pageLength: options.pageLength || 20,
                    paging: options.paging !== false,
                    searching: false, // Disable built-in search, use custom
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
                    },
                    initComplete: function() {
                        // Store DataTable instance for custom filtering
                        $table.data('dataTable', dataTable);
                    }
                });

                // Add wrapper class
                $table.closest('.dataTables_wrapper').addClass('shopglut-datatables-wrapper');
            });
        },

        /**
         * Bind filter events
         */
        bindFilterEvents: function() {
            // Items per page change
            $(document).on('change', '.dt-length-select', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                var length = parseInt($(this).val());

                $('#' + tableId).each(function() {
                    var dataTable = $(this).data('dataTable');
                    if (dataTable) {
                        dataTable.page.len(length).draw();
                    }
                });
            });

            // Category filter change
            $(document).on('change', '.dt-category-filter', function() {
                var tableId = $(this).data('table');
                ShopglutProductTable.applyFilters(tableId);
            });

            // Tag filter change
            $(document).on('change', '.dt-tag-filter', function() {
                var tableId = $(this).data('table');
                ShopglutProductTable.applyFilters(tableId);
            });

            // Search input
            $(document).on('keyup search', '.dt-search-input', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                var searchTerm = $(this).val();

                $('#' + tableId).each(function() {
                    var dataTable = $(this).data('dataTable');
                    if (dataTable) {
                        dataTable.search(searchTerm).draw();
                    }
                });
            });
        },

        /**
         * Apply category and tag filters
         */
        applyFilters: function(tableId) {
            var $wrapper = $('#' + tableId + '_wrapper');
            var categoryFilter = $wrapper.find('.dt-category-filter').val();
            var tagFilter = $wrapper.find('.dt-tag-filter').val();

            $('#' + tableId).each(function() {
                var dataTable = $(this).data('dataTable');
                if (!dataTable) return;

                // Apply custom filter
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var row = dataTable.row(dataIndex);
                    var node = row.node();
                    var $row = $(node);

                    // Category filter
                    if (categoryFilter) {
                        var categoryData = $row.find('td').filter(function() {
                            return $(this).data('category') !== undefined;
                        });
                        if (categoryData.length === 0) {
                            // Check if row contains category link with matching slug
                            var hasCategory = $row.find('td').html().indexOf('product_cat') > -1 &&
                                              $row.find('a[href*="/product-category/' + categoryFilter + '/"]').length > 0;
                            if (!hasCategory) {
                                return false;
                            }
                        }
                    }

                    // Tag filter
                    if (tagFilter) {
                        var hasTag = $row.find('td').html().indexOf('product_tag') > -1 &&
                                       $row.find('a[href*="/product-tag/' + tagFilter + '/"]').length > 0;
                        if (!hasTag) {
                            return false;
                        }
                    }

                    return true;
                });

                dataTable.draw();
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
