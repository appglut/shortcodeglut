/**
 * Zigzag Layout Shortcode - Main JavaScript
 * Handles AJAX pagination for zigzag layout
 */

(function($) {
    'use strict';

    $(document).on('click', '.shortcodeglut-zigzag-wrapper.async-pagination .page-numbers a', function(e) {
        e.preventDefault();

        const $link = $(this);
        const $wrapper = $link.closest('.shortcodeglut-zigzag-wrapper');
        const page = $link.data('page');

        if (!page) {
            return;
        }

        const shortcodeId = $wrapper.data('shortcode-id');
        const contentId = 'content_' + shortcodeId;
        const $content = $('#' + contentId);
        const atts = $wrapper.data('atts');

        // Show loading state
        $content.css('opacity', '0.5');

        $.ajax({
            url: shortcodeglutZigzag.ajax_url,
            type: 'POST',
            data: {
                action: 'shortcodeglut_zigzag_load',
                nonce: shortcodeglutZigzag.nonce,
                paged: page,
                ...atts
            },
            success: function(response) {
                if (response.success) {
                    $content.html(response.data.html);
                    $content.css('opacity', '1');

                    // Scroll to top of content
                    $('html, body').animate({
                        scrollTop: $wrapper.offset().top - 100
                    }, 300);
                }
            },
            error: function() {
                $content.css('opacity', '1');
            }
        });
    });

})(jQuery);
