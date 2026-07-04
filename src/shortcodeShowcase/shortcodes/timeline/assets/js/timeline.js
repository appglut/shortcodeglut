(function($) {
    'use strict';

    $(document).ready(function() {

        // AJAX pagination
        $(document).on('click', '.shortcodeglut-timeline-wrapper .async-pagination a', function(e) {
            e.preventDefault();

            var $link = $(this);
            var page = $link.data('page') || 1;
            var $wrapper = $link.closest('.shortcodeglut-timeline-wrapper');
            var atts;

            try {
                atts = JSON.parse($wrapper.attr('data-atts'));
            } catch (err) {
                return;
            }

            atts.action = 'shortcodeglut_timeline_load';
            atts.nonce = shortcodeglutTimeline.nonce;
            atts.paged = page;
            atts.ajax = 'on';

            $.ajax({
                url: shortcodeglutTimeline.ajax_url,
                type: 'POST',
                data: atts,
                beforeSend: function() {
                    $wrapper.find('.shortcodeglut-timeline').css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        $wrapper.find('[id^="content_"]').html(response.data.html);
                    }
                },
                complete: function() {
                    $wrapper.find('.shortcodeglut-timeline').css('opacity', '1');
                }
            });
        });

    });
})(jQuery);
