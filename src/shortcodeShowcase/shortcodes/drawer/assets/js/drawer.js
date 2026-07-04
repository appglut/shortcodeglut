/**
 * Drawer Panels Shortcode - Main JavaScript
 * Handles panel switching functionality
 */

(function($) {
    'use strict';

    $(document.body).on('click', '.shortcodeglut-drawer-nav-item', function() {
        const $navItem = $(this);
        const $wrapper = $navItem.closest('.shortcodeglut-drawer-wrapper');
        const panelIndex = $navItem.data('panel');

        // Remove active class from all nav items and panels
        $wrapper.find('.shortcodeglut-drawer-nav-item').removeClass('active');
        $wrapper.find('.shortcodeglut-drawer-panel').removeClass('active');

        // Add active class to clicked nav item and corresponding panel
        $navItem.addClass('active');
        const $panel = $wrapper.find('#panel-' + panelIndex);

        if ($panel.length) {
            $panel.addClass('active');
        }
    });

})(jQuery);
