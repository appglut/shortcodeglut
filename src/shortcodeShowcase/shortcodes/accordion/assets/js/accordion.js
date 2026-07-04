/**
 * Accordion List Shortcode JavaScript
 * Handles accordion toggle and AJAX pagination for accordion list shortcode
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initAccordionToggle();
        initAccordionPagination();
    });

    /**
     * Initialize accordion toggle behavior
     */
    function initAccordionToggle() {
        $(document).off('click.accordion', '.shortcodeglut-accordion-header').on('click.accordion', '.shortcodeglut-accordion-header', function() {
            var $header = $(this);
            var $item = $header.closest('.shortcodeglut-accordion-item');
            var $accordion = $item.closest('.shortcodeglut-accordion');
            var isActive = $item.hasClass('active');
            var expandMode = $accordion.data('expand') || 'single';

            if (expandMode === 'single') {
                $accordion.find('.shortcodeglut-accordion-item').removeClass('active');
            }

            if (!isActive) {
                $item.addClass('active');
            }
        });
    }

    /**
     * Initialize AJAX pagination
     */
    function initAccordionPagination() {
        $(document).off('click.accordion-pagination', '.shortcodeglut-pagination.async-pagination a.page-numbers').on('click.accordion-pagination', '.shortcodeglut-pagination.async-pagination a.page-numbers', function(e) {
            e.preventDefault();

            var $link = $(this);
            var page = $link.data('page');

            if (!page) return;

            var $wrapper = $link.closest('.shortcodeglut-accordion-wrapper');

            loadAccordionContent($wrapper, { paged: page });
        });
    }

    /**
     * Load accordion content via AJAX
     */
    function loadAccordionContent($wrapper, extraData) {
        if (typeof shortcodeglutAccordionAjax === 'undefined') {
            $wrapper.removeClass('loading');
            return;
        }

        $wrapper.addClass('loading');

        var wrapperId = $wrapper.attr('id');
        var baseId = wrapperId.replace('_wrapper', '');
        var contentId = 'content_' + baseId;

        if ($('#' + contentId).length === 0) {
            $wrapper.removeClass('loading');
            return;
        }

        var atts = {};
        try {
            var attsString = $wrapper.attr('data-atts') || '{}';
            atts = JSON.parse(attsString);
        } catch (e) {
            atts = {};
        }

        var requestData = $.extend({}, atts, extraData);

        $.ajax({
            url: shortcodeglutAccordionAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'shortcodeglut_accordion_load',
                nonce: shortcodeglutAccordionAjax.nonce,
                paged: requestData.paged || 1,
                expand: requestData.expand || 'single',
                show_price: requestData.show_price || '1',
                show_excerpt: requestData.show_excerpt || '1',
                show_features: requestData.show_features || '1',
                items_per_page: requestData.items_per_page || 10,
                paging: requestData.paging || '1',
                ajax: requestData.ajax || 'off',
                order_by: requestData.order_by || 'title',
                order: requestData.order || 'ASC',
                category: requestData.category || '',
                exclude: requestData.exclude || '',
                icon_width: requestData.icon_width || 56
            },
            success: function(response) {
                if (response.success && response.data && response.data.html) {
                    var $contentElement = $('#' + contentId);
                    if ($contentElement.length === 0) {
                        $wrapper.removeClass('loading');
                        return;
                    }

                    $contentElement.html(response.data.html);

                    // Reinitialize pagination for the new content
                    var $newPagination = $contentElement.find('.shortcodeglut-pagination.async-pagination');
                    if ($newPagination.length > 0) {
                        $newPagination.off('click.accordion-pagination').on('click.accordion-pagination', 'a.page-numbers', function(e) {
                            e.preventDefault();
                            var page = $(this).data('page');
                            if (!page) return;
                            loadAccordionContent($wrapper, { paged: page });
                        });
                    }

                    // Scroll to top of accordion
                    $('html, body').animate({
                        scrollTop: $wrapper.offset().top - 50
                    }, 300);
                }
            },
            error: function(xhr, status, error) {
                $('#' + contentId).html('<p class="shortcodeglut-error">Error: ' + error + '</p>');
            },
            complete: function() {
                $wrapper.removeClass('loading');
            }
        });
    }

})(jQuery);

// Internationalization strings
var shortcodeglut_accordion_i18n = shortcodeglut_accordion_i18n || {
    error_loading: 'Error loading products. Please try again.',
    add_to_cart: 'Add to Cart',
    adding: 'Adding...',
    added: 'Added!',
    error_adding: 'Error adding product to cart.'
};
