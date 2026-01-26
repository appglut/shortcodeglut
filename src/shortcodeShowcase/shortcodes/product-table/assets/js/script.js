/**
 * ShopGlut Product Table - Interactive Table
 * Custom search, filter, sort, and pagination functionality
 */

(function($) {
    'use strict';

    /**
     * Product Table Handler
     */
    var ShopglutProductTable = {

        tables: {},

        /**
         * Initialize
         */
        init: function() {
            this.initTables();
            this.bindEvents();
        },

        /**
         * Initialize all product tables
         */
        initTables: function() {
            var self = this;

            $('.shopglut-product-table').each(function() {
                var $table = $(this);
                var tableId = $table.attr('id');
                var $wrapper = $table.closest('.shopglut-product-table-wrapper');

                // Store table data
                self.tables[tableId] = {
                    allRows: [],
                    filteredRows: [],
                    sortColumn: null,
                    sortDirection: 'asc',
                    currentPage: 1,
                    itemsPerPage: parseInt($table.data('items-per-page')) || 10
                };

                // Extract all rows
                $table.find('tbody tr').each(function() {
                    var $row = $(this);
                    var rowData = {
                        element: $row,
                        categories: $row.data('categories') || '',
                        categoryNames: $row.data('category-names') || '',
                        tags: $row.data('tags') || '',
                        tagNames: $row.data('tag-names') || '',
                        stock: $row.data('stock') || '',
                        cells: []
                    };

                    $row.find('td').each(function() {
                        var $cell = $(this);
                        rowData.cells.push({
                            element: $cell,
                            text: $cell.text().trim(),
                            html: $cell.html()
                        });
                    });

                    self.tables[tableId].allRows.push(rowData);
                });

                // Initialize filtered rows with all rows
                self.tables[tableId].filteredRows = [...self.tables[tableId].allRows];

                // Initialize sorting
                self.initSorting($table, tableId);

                // Populate category filter
                var $categoryFilter = $wrapper.find('.sgpt-category-filter');
                if ($categoryFilter.length) {
                    self.populateCategoryFilter($categoryFilter, self.tables[tableId]);
                }

                // Populate tag filter
                var $tagFilter = $wrapper.find('.sgpt-tag-filter');
                if ($tagFilter.length) {
                    self.populateTagFilter($tagFilter, self.tables[tableId]);
                }

                // Initial render with pagination
                self.applyFilters(tableId);
            });
        },

        /**
         * Initialize sorting functionality
         */
        initSorting: function($table, tableId) {
            var self = this;

            $table.find('thead th[data-column]').each(function() {
                var $th = $(this);

                if ($th.hasClass('no-sort')) {
                    return;
                }

                $th.on('click', function() {
                    var column = $(this).data('column');
                    var table = self.tables[tableId];

                    if (table.sortColumn === column) {
                        table.sortDirection = table.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        table.sortColumn = column;
                        table.sortDirection = 'asc';
                    }

                    // Update visual indicators
                    $table.find('thead th').removeClass('sort-asc sort-desc');
                    $th.addClass(table.sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

                    self.sortTable(tableId, column, table.sortDirection);
                });
            });
        },

        /**
         * Sort table by column
         */
        sortTable: function(tableId, column, direction) {
            var self = this;
            var table = this.tables[tableId];
            var $table = $('#' + tableId);
            var columnIndex = -1;

            // Find column index
            $table.find('thead th[data-column]').each(function(index) {
                if ($(this).data('column') === column) {
                    columnIndex = index;
                }
            });

            if (columnIndex === -1) {
                return;
            }

            // Sort filtered rows
            table.filteredRows.sort(function(a, b) {
                var aVal = a.cells[columnIndex] ? a.cells[columnIndex].text : '';
                var bVal = b.cells[columnIndex] ? b.cells[columnIndex].text : '';

                // Try numeric comparison
                var aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                var bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum) &&
                    aVal.match(/^[\$£€¥]?\s*[\d,]+\.?\d*\s*?$/) &&
                    bVal.match(/^[\$£€¥]?\s*[\d,]+\.?\d*\s*?$/)) {
                    aVal = aNum;
                    bVal = bNum;
                } else {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }

                if (direction === 'asc') {
                    return aVal > bVal ? 1 : (aVal < bVal ? -1 : 0);
                } else {
                    return aVal < bVal ? 1 : (aVal > bVal ? -1 : 0);
                }
            });

            // Re-render with pagination
            self.renderTable(tableId);
        },

        /**
         * Render table with pagination
         */
        renderTable: function(tableId) {
            var table = this.tables[tableId];
            var $table = $('#' + tableId);
            var $tbody = $table.find('tbody');
            var $wrapper = $table.closest('.shopglut-product-table-wrapper');

            // Clear tbody
            $tbody.empty();

            if (table.filteredRows.length === 0) {
                var colCount = $table.find('thead th').length;
                $tbody.html('<tr><td colspan="' + colCount + '" class="sgpt-no-results">No results found</td></tr>');
                this.updateResultsCount(tableId);
                return;
            }

            // Calculate pagination
            var startIndex = (table.currentPage - 1) * table.itemsPerPage;
            var endIndex = Math.min(startIndex + table.itemsPerPage, table.filteredRows.length);
            var paginatedRows = table.filteredRows.slice(startIndex, endIndex);

            // Append filtered rows
            paginatedRows.forEach(function(rowData) {
                $tbody.append(rowData.element);
            });

            // Update results count
            this.updateResultsCount(tableId);
        },

        /**
         * Update results count display
         */
        updateResultsCount: function(tableId) {
            var table = this.tables[tableId];
            var $wrapper = $('#' + tableId).closest('.shopglut-product-table-wrapper');
            var $resultsCount = $wrapper.find('.sgpt-results-count');

            if (!$resultsCount.length) {
                return;
            }

            var filtered = table.filteredRows.length;
            var total = table.allRows.length;
            var start = (table.currentPage - 1) * table.itemsPerPage + 1;
            var end = Math.min(start + table.itemsPerPage - 1, filtered);

            if (filtered === 0) {
                $resultsCount.text('No results found');
            } else {
                $resultsCount.text('Showing ' + start + ' to ' + end + ' of ' + filtered + ' entries' +
                    (filtered < total ? ' (filtered from ' + total + ' total entries)' : ''));
            }
        },

        /**
         * Populate category filter dropdown
         */
        populateCategoryFilter: function($filter, table) {
            var categories = new Map();

            table.allRows.forEach(function(row) {
                if (row.categoryNames) {
                    var cats = row.categoryNames.split(',');
                    var slugs = row.categories.split(',');
                    cats.forEach(function(cat, index) {
                        if (cat && slugs[index]) {
                            categories.set(slugs[index], cat);
                        }
                    });
                }
            });

            // Sort categories alphabetically
            var sortedCategories = Array.from(categories.entries()).sort(function(a, b) {
                return a[1].localeCompare(b[1]);
            });

            // Clear existing options (keep first "All" option)
            $filter.find('option:not(:first)').remove();

            // Add category options
            sortedCategories.forEach(function(item) {
                var $option = $('<option>').val(item[0]).text(item[1]);
                $filter.append($option);
            });
        },

        /**
         * Populate tag filter dropdown
         */
        populateTagFilter: function($filter, table) {
            var tags = new Map();

            table.allRows.forEach(function(row) {
                if (row.tagNames) {
                    var tagNames = row.tagNames.split(',');
                    var slugs = row.tags.split(',');
                    tagNames.forEach(function(tag, index) {
                        if (tag && slugs[index]) {
                            tags.set(slugs[index], tag);
                        }
                    });
                }
            });

            // Sort tags alphabetically
            var sortedTags = Array.from(tags.entries()).sort(function(a, b) {
                return a[1].localeCompare(b[1]);
            });

            // Clear existing options (keep first "All" option)
            $filter.find('option:not(:first)').remove();

            // Add tag options
            sortedTags.forEach(function(item) {
                var $option = $('<option>').val(item[0]).text(item[1]);
                $filter.append($option);
            });
        },

        /**
         * Apply all filters
         */
        applyFilters: function(tableId) {
            var self = this;
            var table = this.tables[tableId];
            var $wrapper = $('#' + tableId).closest('.shopglut-product-table-wrapper');
            var searchTerm = $wrapper.find('.sgpt-search-input').val().toLowerCase();
            var categoryFilter = $wrapper.find('.sgpt-category-filter').val();
            var tagFilter = $wrapper.find('.sgpt-tag-filter').val();
            var stockFilter = $wrapper.find('.sgpt-stock-filter').val();

            // Reset to first page when filters change
            table.currentPage = 1;

            // Filter rows
            table.filteredRows = table.allRows.filter(function(row) {
                // Search filter
                if (searchTerm) {
                    var rowText = row.cells.map(function(cell) { return cell.text; }).join(' ').toLowerCase();
                    if (rowText.indexOf(searchTerm) === -1) {
                        return false;
                    }
                }

                // Category filter
                if (categoryFilter) {
                    var rowCategories = row.categories ? row.categories.split(',') : [];
                    if (rowCategories.indexOf(categoryFilter) === -1) {
                        return false;
                    }
                }

                // Tag filter
                if (tagFilter) {
                    var rowTags = row.tags ? row.tags.split(',') : [];
                    if (rowTags.indexOf(tagFilter) === -1) {
                        return false;
                    }
                }

                // Stock filter
                if (stockFilter) {
                    if (!row.stock || !row.stock.includes(stockFilter)) {
                        return false;
                    }
                }

                return true;
            });

            // Re-sort if column is set
            if (table.sortColumn) {
                this.sortTable(tableId, table.sortColumn, table.sortDirection);
            } else {
                this.renderTable(tableId);
            }
        },

        /**
         * Change page
         */
        changePage: function(tableId, page) {
            var table = this.tables[tableId];
            var maxPage = Math.ceil(table.filteredRows.length / table.itemsPerPage);

            if (page < 1) page = 1;
            if (page > maxPage) page = maxPage;

            table.currentPage = page;
            this.renderTable(tableId);
        },

        /**
         * Change items per page
         */
        changeItemsPerPage: function(tableId, itemsPerPage) {
            var table = this.tables[tableId];
            table.itemsPerPage = parseInt(itemsPerPage);
            table.currentPage = 1;
            this.renderTable(tableId);
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            var self = this;

            // Items per page change
            $(document).on('change', '.sgpt-length-select', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                self.changeItemsPerPage(tableId, $(this).val());
            });

            // Search input
            $(document).on('keyup search', '.sgpt-search-input', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                self.applyFilters(tableId);
            });

            // Category filter
            $(document).on('change', '.sgpt-category-filter', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                self.applyFilters(tableId);
            });

            // Tag filter
            $(document).on('change', '.sgpt-tag-filter', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                self.applyFilters(tableId);
            });

            // Stock filter
            $(document).on('change', '.sgpt-stock-filter', function() {
                var tableId = $(this).closest('.shopglut-product-table-wrapper').find('.shopglut-product-table').attr('id');
                self.applyFilters(tableId);
            });

            // Add to cart button
            $(document).on('click', '.shopglut-table-add-to-cart', this.handleAddToCart);

            // View product button
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

            // Check if wc_add_to_cart_params exists
            if (typeof wc_add_to_cart_params === 'undefined') {
                console.error('WooCommerce add to cart params not found');
                return;
            }

            // Show loading state
            var originalHtml = $button.html();
            $button.text('Adding');
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
                        $button.html(originalHtml);
                        $button.prop('disabled', false);
                        alert(response.error);
                    } else {
                        // Update cart fragments
                        if (response.fragments) {
                            $.each(response.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }

                        // Replace button with "View Cart"
                        var cartUrl = typeof wc_add_to_cart_params.cart_url !== 'undefined'
                            ? wc_add_to_cart_params.cart_url
                            : (wc_add_to_cart_params.cart_url || wc_add_to_cart_params.wc_cart_url);

                        $button.removeClass('ajax_add_to_cart')
                            .addClass('shopglut-view-cart')
                            .attr('href', cartUrl)
                            .removeAttr('data-product_id')
                            .text('View Cart');
                    }
                },
                error: function() {
                    $button.html(originalHtml);
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
            if (url && url !== '#') {
                window.location.href = url;
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('.shopglut-product-table').length > 0) {
            ShopglutProductTable.init();
        }
    });

    // Export for global access
    window.ShopglutProductTable = ShopglutProductTable;

})(jQuery);
