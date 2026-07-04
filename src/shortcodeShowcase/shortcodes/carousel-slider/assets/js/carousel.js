/**
 * ShopGlut Carousel Slider JavaScript
 *
 * Handles carousel navigation, autoplay, and slide transitions
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

(function($) {
    'use strict';

    // Carousel class
    class ShopGlutCarousel {
        constructor(element) {
            this.$element = $(element);
            this.$track = this.$element.find('.shopglut-carousel-track');
            this.$slides = this.$element.find('.shopglut-carousel-slide');
            this.$dots = this.$element.find('.shopglut-carousel-dot');
            this.$arrows = this.$element.find('.shopglut-carousel-arrow');
            this.autoplayDelay = parseInt(this.$element.data('autoplay')) || 5000;
            this.carouselId = this.$element.data('carousel-id');
            this.currentSlide = 0;
            this.totalSlides = this.$slides.length;
            this.autoplayTimer = null;

            this.init();
        }

        init() {
            this.bindEvents();
            if (this.autoplayDelay > 0) {
                this.startAutoplay();
            }

            // Pause on hover
            this.$element.on('mouseenter', () => this.stopAutoplay());
            this.$element.on('mouseleave', () => {
                if (this.autoplayDelay > 0) {
                    this.startAutoplay();
                }
            });

            // Handle visibility change to pause when tab is not visible
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoplay();
                } else {
                    if (this.autoplayDelay > 0) {
                        this.startAutoplay();
                    }
                }
            });
        }

        bindEvents() {
            // Arrow navigation
            this.$arrows.on('click', (e) => {
                e.preventDefault();
                const direction = $(e.currentTarget).data('direction');
                if (direction === 'next') {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            });

            // Dot navigation
            this.$dots.on('click', (e) => {
                e.preventDefault();
                const slideIndex = $(e.currentTarget).data('slide');
                this.goToSlide(slideIndex);
            });

            // Touch/swipe support
            let touchStartX = 0;
            let touchEndX = 0;

            this.$element.on('touchstart', (e) => {
                touchStartX = e.originalEvent.touches[0].clientX;
                this.stopAutoplay();
            });

            this.$element.on('touchend', (e) => {
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                this.handleSwipe(touchStartX, touchEndX);
                if (this.autoplayDelay > 0) {
                    this.startAutoplay();
                }
            });

            // Keyboard navigation
            $(document).on('keydown', (e) => {
                // Only handle if this carousel is focused or visible in viewport
                if (!this.isCarouselVisible()) {
                    return;
                }

                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prevSlide();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.nextSlide();
                }
            });
        }

        handleSwipe(startX, endX) {
            const threshold = 50;
            const diff = startX - endX;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }
        }

        isCarouselVisible() {
            const windowHeight = $(window).height();
            const elementTop = this.$element.offset().top;
            const elementBottom = elementTop + this.$element.outerHeight();

            return elementTop < windowHeight && elementBottom > 0;
        }

        goToSlide(index) {
            if (index < 0 || index >= this.totalSlides) {
                return;
            }

            this.currentSlide = index;
            this.$track.css('transform', `translateX(-${this.currentSlide * 100}%)`);
            this.$dots.removeClass('active').eq(this.currentSlide).addClass('active');

            // Trigger custom event
            this.$element.trigger('shopglut:carousel:change', [this.currentSlide]);
        }

        nextSlide() {
            const nextIndex = (this.currentSlide + 1) % this.totalSlides;
            this.goToSlide(nextIndex);
        }

        prevSlide() {
            const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            this.goToSlide(prevIndex);
        }

        startAutoplay() {
            this.stopAutoplay();
            if (this.autoplayDelay > 0 && this.totalSlides > 1) {
                this.autoplayTimer = setInterval(() => {
                    this.nextSlide();
                }, this.autoplayDelay);
            }
        }

        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        }

        destroy() {
            this.stopAutoplay();
            this.$arrows.off('click');
            this.$dots.off('click');
            this.$element.off('mouseenter mouseleave touchstart touchend');
        }
    }

    // Initialize carousels on page load
    function initCarousels() {
        $('.shopglut-carousel').each(function() {
            const $carousel = $(this);
            if (!$carousel.data('shopglut-carousel-instance')) {
                const instance = new ShopGlutCarousel($carousel);
                $carousel.data('shopglut-carousel-instance', instance);
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        initCarousels();
    });

    // Re-initialize for dynamic content
    $(document).on('shopglut:carousel:init', function() {
        initCarousels();
    });

    // Pause all carousels when any modal is open
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                const hasModal = Array.from(mutation.addedNodes).some(node => {
                    return node.nodeType === 1 && (
                        $(node).hasClass('modal') ||
                        $(node).hasClass('fancybox-wrap') ||
                        $(node).find('.modal, .fancybox-wrap').length > 0
                    );
                });

                if (hasModal) {
                    $('.shopglut-carousel').each(function() {
                        const instance = $(this).data('shopglut-carousel-instance');
                        if (instance) {
                            instance.stopAutoplay();
                        }
                    });
                }
            }

            if (mutation.removedNodes.length) {
                const hadModal = Array.from(mutation.removedNodes).some(node => {
                    return node.nodeType === 1 && (
                        $(node).hasClass('modal') ||
                        $(node).hasClass('fancybox-wrap')
                    );
                });

                if (hadModal) {
                    $('.shopglut-carousel').each(function() {
                        const instance = $(this).data('shopglut-carousel-instance');
                        if (instance && instance.autoplayDelay > 0) {
                            instance.startAutoplay();
                        }
                    });
                }
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

})(jQuery);
