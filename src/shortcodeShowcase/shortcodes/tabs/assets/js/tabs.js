/**
 * Tabs Layout Shortcode JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';

    // Handle tab switching
    $(document).on('click', '.shortcodeglut-tab-btn', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var $wrapper = $btn.closest('.shortcodeglut-tabs-wrapper');
        var tabId = $btn.data('tab');
        var animation = $wrapper.data('animation') || 'fade';

        // Remove active class from all buttons and panels
        $wrapper.find('.shortcodeglut-tab-btn').removeClass('active');
        $wrapper.find('.shortcodeglut-tab-panel').removeClass('active ' + animation + '-active');

        // Add active class to clicked button
        $btn.addClass('active');

        // Find and activate the corresponding panel
        var $panel = $wrapper.find('.shortcodeglut-tab-panel[data-tab="' + tabId + '"]');

        // Add animation class if needed
        if (animation === 'slide') {
            $panel.addClass('animation-slide');
        }

        $panel.addClass('active');

        // Optional: Load products via AJAX if panel is empty
        if ($panel.find('.shortcodeglut-grid').is(':empty')) {
            loadTabProducts($panel, $wrapper);
        }
    });

    // Load tab products via AJAX
    function loadTabProducts($panel, $wrapper) {
        var tabId = $panel.data('tab');
        var termId = $panel.data('term-id') || 0;
        var postsPerPage = $panel.data('posts-per-page') || 8;
        var columns = $panel.data('columns') || 4;

        $.ajax({
            url: shortcodeglutTabs.ajax_url,
            type: 'POST',
            data: {
                action: 'shortcodeglut_tabs_load',
                nonce: shortcodeglutTabs.nonce,
                tab_id: tabId,
                term_id: termId,
                posts_per_page: postsPerPage,
                columns: columns
            },
            success: function(response) {
                if (response.success) {
                    $panel.find('.shortcodeglut-grid').html(response.data.html);
                }
            }
        });
    }

    // Handle add to cart button clicks
    $(document).on('click', '.shortcodeglut-card-btn.ajax_add_to_cart', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var productId = $btn.data('product-id');

        // Trigger WooCommerce add to cart
        $(document).trigger('added_to_cart', [productId, $btn]);
    });
});
