/**
 * Shortcodeglut Category Shortcode - Async Loading
 * Handles AJAX pagination and form submissions for category shortcode
 */

(function($) {
    'use strict';

    /**
     * Category Shortcode Handler
     */
    var ShortcodeglutCategoryShortcode = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Handle async form submissions
            $(document).on('submit', '.__shortcodeglut_submit_async', this.handleAsyncFormSubmit);

            // Handle pagination clicks - bind to all pagination links within woo category containers
            $(document).on('click', '.shortcodeglut-pagination .page-link', this.handleAsyncPaginationClick);
        },

        /**
         * Handle async form submission
         */
        handleAsyncFormSubmit: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $container = $($form.data('container'));

            if ($container.length === 0) {
                return;
            }

            // Show loading state
            ShortcodeglutCategoryShortcode.showLoading($container);

            // Get form data
            var formData = $form.serialize();

            // Get shortcode parameters from data attributes
            var shortcodeParams = ShortcodeglutCategoryShortcode.getShortcodeParams($form);

            // Merge form data with shortcode params
            var postData = formData + '&' + $.param(shortcodeParams) + '&action=shortcodeglut_woo_category_products&nonce=' + shortcodeglutWooCategoryAjax.nonce;

            // Make AJAX request
            $.ajax({
                url: shortcodeglutWooCategoryAjax.ajax_url,
                type: 'POST',
                data: postData,
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);

                        // Scroll to container
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 100
                        }, 500);
                    } else {
                        ShortcodeglutCategoryShortcode.showError($container, response.data.message || 'An error occurred');
                    }
                },
                error: function() {
                    ShortcodeglutCategoryShortcode.showError($container, 'Failed to load products. Please try again.');
                }
            });
        },

        /**
         * Handle async pagination click
         */
       handleAsyncPaginationClick: function(e) {
      var $link = $(this);
      var pageData = $link.attr('data-page');
                                                                                                                                      
      // If no data-page attribute, this is normal pagination - let it work normally
      if (pageData === undefined) {                                                                                                   
          return; // Let the default link behavior work     
      }

      // This is AJAX pagination - prevent default and handle with AJAX
      e.preventDefault();

      var page = parseInt(pageData, 10);
      var $wrapper = $link.closest('.shortcodeglut');
      var $content = $wrapper.find('div[id^="content_"]').first();

      // Validate page number
      if (isNaN(page) || page < 1) {
          return;                                                                                                                     
      }
                                                                                                                                      
      // Get shortcode attributes from wrapper (like BasicGrid does)
      var attsJson = $wrapper.data('atts');
      var atts = typeof attsJson === 'string' ? JSON.parse(attsJson) : attsJson;

      // Show loading state (use opacity like BasicGrid)
      $content.css('opacity', '0.5');

      // Prepare AJAX data directly as object (no form serialization)
      var data = {
          action: 'shortcodeglut_woo_category_products',
          nonce: shortcodeglutWooCategoryAjax.nonce,
          paged: page,
          categories: atts.categories,                                                                                                
          cat_field: atts.cat_field,
          operator: atts.operator,                                                                                                    
          items_per_page: atts.items_per_page,              
          template: atts.template,
          paging: atts.paging ? 1 : 0,
          cols: atts.cols,
          colspad: atts.colspad,
          colsphone: atts.colsphone,
          ajax: atts.ajax,
          ajax_pagination: atts.ajax_pagination
      };

      // Make AJAX request
      $.ajax({
          url: shortcodeglutWooCategoryAjax.ajax_url,
          type: 'POST',
          data: data,                                                                                                                 
          success: function(response) {
              if (response.success && response.data.html) {                                                                           
                  $content.html(response.data.html);        
                  $content.css('opacity', '1');

                  // Scroll to top of content
                  $('html, body').animate({
                      scrollTop: $wrapper.offset().top - 100
                  }, 300);
              } else {
                  $content.css('opacity', '1');
                  $content.html('<p class="shortcodeglut-error">' + (response.data.message || 'An error occurred') + '</p>');
              }
          },
          error: function() {
              $content.css('opacity', '1');                                                                                           
              $content.html('<p class="shortcodeglut-error">Failed to load products. Please try again.</p>');
          }                                                                                                                           
      });                                                   
  },          

        /**
         * Get shortcode parameters from form
         */
            getShortcodeParams: function($form) {
            var params = {};

            // Get all attributes from JSON data attribute (like BasicGrid does)
            if ($form.length > 0) {
                var attsJson = $form.data('atts');
                var atts = typeof attsJson === 'string' ? JSON.parse(attsJson) : attsJson;
                                                                                                                                            
                if (atts) {
                    params = {                                                
                        categories: atts.categories,
                        cat_field: atts.cat_field,
                        operator: atts.operator,
                        template: atts.template,            
                        paging: atts.paging,   
                        items_per_page: atts.items_per_page,  // <-- ADD THIS LINE 
                        cols: atts.cols,    
                        colspad: atts.colspad,
                        colsphone: atts.colsphone,
                        ajax: atts.ajax,          
                        ajax_pagination: atts.ajax_pagination
                   };
                }     
            }
                                                                                                                                            
            return params;
        },                  

        /**
         * Show loading state
         */
        showLoading: function($container) {
            $container.html('<div class="shortcodeglut-loading">Loading products...</div>');
        },

        /**
         * Show error message
         */
        showError: function($container, message) {
            $container.html('<p class="shortcodeglut-error">' + message + '</p>');
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        ShortcodeglutCategoryShortcode.init();
    });

})(jQuery);
