/**
 * Kanban Board Shortcode JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';

    // Handle load more clicks
    $(document).on('click', '.shortcodeglut-load-more', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $column = $button.closest('.shortcodeglut-column');
        var column = $button.data('column');
        var page = $column.data('page') || 1;
        var $wrapper = $button.closest('.shortcodeglut-kanban-wrapper');

        $button.addClass('loading');

        $.ajax({
            url: shortcodeglutKanban.ajax_url,
            type: 'POST',
            data: {
                action: 'shortcodeglut_kanban_load',
                nonce: shortcodeglutKanban.nonce,
                column: column,
                page: page,
                per_column: $wrapper.data('per-column') || 4,
                category: $wrapper.data('category') || '',
                exclude: $wrapper.data('exclude') || ''
            },
            success: function(response) {
                if (response.success) {
                    var $newCards = $(response.data.html);

                    // Remove current button
                    $button.remove();

                    // Append new cards
                    $column.append($newCards);

                    // Update page number
                    $column.data('page', page + 1);

                    // Add load more button if more products exist
                    if (response.data.has_more) {
                        var $newButton = $('<button class="shortcodeglut-load-more" data-column="' + column + '">Load More</button>');
                        $column.append($newButton);
                    }
                }
            },
            complete: function() {
                $button.removeClass('loading');
            }
        });
    });
});
