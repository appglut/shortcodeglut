/**
 * Book Flip Shortcode JavaScript
 * Handles two-page spread with realistic page-turn animations
 */

(function($) {
    'use strict';

    let currentPage = 1;
    let totalPages = 1;

    const ShortcodeglutBookFlip = {
        /**
         * Initialize the book flip functionality
         */
        init: function() {
            $('.shortcodeglut-book-flip-wrapper').each(function() {
                const $wrapper = $(this);
                const $pages = $wrapper.find('.shortcodeglut-page-right');

                totalPages = parseInt($wrapper.data('total-pages')) || $pages.length;
                currentPage = 1;

                // Initial setup - only show first page
                $pages.each(function() {
                    const pageNum = parseInt($(this).data('page'));
                    if (pageNum === 1) {
                        $(this).addClass('visible');
                    } else {
                        $(this).removeClass('visible').css('opacity', '0').css('visibility', 'hidden');
                    }
                });

                // Navigation handlers
                $wrapper.find('.shortcodeglut-nav-prev').on('click', function() {
                    if (currentPage > 1) {
                        ShortcodeglutBookFlip.flipToPrev($wrapper);
                    }
                });

                $wrapper.find('.shortcodeglut-nav-next').on('click', function() {
                    if (currentPage < totalPages) {
                        ShortcodeglutBookFlip.flipToNext($wrapper);
                    }
                });

                // Page click to flip next
                $pages.on('click', function() {
                    const pageNum = parseInt($(this).data('page'));
                    // Only flip if this is the current visible page and not flipped
                    if (pageNum === currentPage && currentPage < totalPages && !$(this).hasClass('flipped')) {
                        ShortcodeglutBookFlip.flipToNext($wrapper);
                    }
                });

                // Button click handler
                $wrapper.find('.shortcodeglut-page-btn').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const url = $(this).attr('href');
                    if (url) {
                        window.location.href = url;
                    }
                });

                // Initialize button states
                ShortcodeglutBookFlip.updateNavButtons($wrapper);
            });
        },

        /**
         * Flip to next page
         * Previous left page gets hidden, current page flips to left, next page appears on right
         */
        flipToNext: function($wrapper) {
            if (currentPage >= totalPages) return;

            const $currentRightPage = $wrapper.find('.shortcodeglut-page-right[data-page="' + currentPage + '"]');
            const $introPage = $wrapper.find('.shortcodeglut-left-intro');

            // Hide the intro page if still visible
            $introPage.addClass('hidden');

            // Hide the previously flipped page (if exists)
            if (currentPage > 1) {
                const $prevFlippedPage = $wrapper.find('.shortcodeglut-page-right[data-page="' + (currentPage - 1) + '"]');
                $prevFlippedPage.css('visibility', 'hidden').css('opacity', '0');
            }

            // Flip current page (it will show back on left with product details)
            $currentRightPage.addClass('flipped');

            // Increment page
            currentPage++;

            // Show next page on right
            setTimeout(function() {
                const $nextPage = $wrapper.find('.shortcodeglut-page-right[data-page="' + currentPage + '"]');
                $nextPage.addClass('visible').css('visibility', 'visible').css('opacity', '1');

                ShortcodeglutBookFlip.updateNavButtons($wrapper);
                ShortcodeglutBookFlip.updatePageIndicator($wrapper);
            }, 400);
        },

        /**
         * Flip to previous page
         * Current right page hides, current left page unflips to right, previous left page shows
         */
        flipToPrev: function($wrapper) {
            if (currentPage <= 1) return;

            // Hide current page on right
            const $currentRightPage = $wrapper.find('.shortcodeglut-page-right[data-page="' + currentPage + '"]');
            $currentRightPage.removeClass('visible').css('opacity', '0').css('visibility', 'hidden');

            setTimeout(function() {
                currentPage--;

                // Unflip the previous page (moves from left back to right)
                const $prevPage = $wrapper.find('.shortcodeglut-page-right[data-page="' + currentPage + '"]');
                $prevPage.removeClass('flipped').addClass('visible').css('visibility', 'visible').css('opacity', '1');

                // Show the page before that on left (if exists)
                if (currentPage > 1) {
                    const $leftPage = $wrapper.find('.shortcodeglut-page-right[data-page="' + (currentPage - 1) + '"]');
                    $leftPage.css('visibility', 'visible').css('opacity', '1');
                } else {
                    // If we're back to page 1, show the intro page
                    setTimeout(function() {
                        $wrapper.find('.shortcodeglut-left-intro').removeClass('hidden');
                    }, 300);
                }

                ShortcodeglutBookFlip.updateNavButtons($wrapper);
                ShortcodeglutBookFlip.updatePageIndicator($wrapper);
            }, 300);
        },

        /**
         * Update navigation button states
         */
        updateNavButtons: function($wrapper) {
            $wrapper.find('.shortcodeglut-nav-prev').prop('disabled', currentPage === 1);
            $wrapper.find('.shortcodeglut-nav-next').prop('disabled', currentPage === totalPages);
        },

        /**
         * Update page indicator
         */
        updatePageIndicator: function($wrapper) {
            $wrapper.find('.current-page').text(currentPage);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ShortcodeglutBookFlip.init();
    });

})(jQuery);
