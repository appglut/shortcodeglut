jQuery(document).ready(function($) {
    // Hide admin sidebar and adjust layout
    $('#adminmenumain').hide();
    $('#wpwrap').css('margin-top', 0);
    $('#wpbody').css('margin-left', 0);
    $('#wpbody-content').css({
        'padding-left': 0,
        'padding-bottom': 0
    });
    $('body').addClass('shortcodeglut-fullpage-editor');
    $('html').addClass('shortcodeglut-fullpage-editor');

    // Hide loader when page is fully loaded
    $(window).on('load', function() {
        $('.shortcodeglut-page-loader').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Also hide after a minimum timeout in case window.load doesn't fire
    setTimeout(function() {
        $('.shortcodeglut-page-loader').fadeOut(300, function() {
            $(this).remove();
        });
    }, 2000);

    // Template tag insertion - insert tag at cursor position
    $('.shortcodeglut-tag-item').on('click', function() {
        var tag = $(this).data('tag');
        var textarea = document.getElementById('template_html');

        if (textarea) {
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var text = textarea.value;

            // Insert tag at cursor position
            textarea.value = text.substring(0, start) + tag + text.substring(end);

            // Set cursor position after the inserted tag
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
            textarea.focus();
        }
    });

    // Tab switching functionality
    $('.scg-tab-btn').on('click', function() {
        var tab = $(this).data('tab');

        // Remove active class from all buttons and panes
        $('.scg-tab-btn').removeClass('active');
        $('.scg-tab-pane').removeClass('active');

        // Add active class to clicked button and corresponding pane
        $(this).addClass('active');
        $('#tab-' + tab).addClass('active');

        // Clean up preview styles when leaving preview tab
        if (tab !== 'preview') {
            $('#shortcodeglut-preview-styles').remove();
        }

        // If preview tab, load preview
        if (tab === 'preview') {
            loadTemplatePreview();
        }
    });

    // Function to load template preview
    function loadTemplatePreview() {
        var html = $('#template_html').val();
        var css = $('#template_css').val();
        var tags = $('#template_tags').val();

        if (!html.trim()) {
            $('#scg-template-preview-area').html('<p class="scg-preview-placeholder">Add HTML template and switch to this tab to see the preview</p>');
            return;
        }

        $('#scg-template-preview-area').html('<p class="scg-preview-placeholder">Loading preview...</p>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'shortcodeglut_preview_template',
                html: html,
                css: css,
                tags: tags,
                nonce: shortcodeglutTemplatesList.previewNonce
            },
            success: function(response) {
                if (response.success) {
                    // Inject HTML
                    $('#scg-template-preview-area').html(response.data.html);

                    // Inject CSS into the DOM
                    // Remove any existing preview styles
                    $('#shortcodeglut-preview-styles').remove();

                    // Add new styles if CSS is provided
                    if (response.data.css) {
                        $('head').append('<style id="shortcodeglut-preview-styles">' + response.data.css + '</style>');
                    }
                } else {
                    $('#scg-template-preview-area').html('<p class="scg-preview-placeholder">Error loading preview</p>');
                }
            },
            error: function() {
                $('#scg-template-preview-area').html('<p class="scg-preview-placeholder">Error loading preview</p>');
            }
        });
    }

    // Handle form submission
    $('#template-editor-form').on('submit', function() {
        // Form submits normally
        return true;
    });

    // ========== Editor Preview Modal ==========

    // Open preview modal when button is clicked
    $('#shortcodeglut-editor-preview-btn').on('click', function() {
        loadEditorPreviewModal();
    });

    // Close preview modal
    $('#shortcodeglut-preview-modal-close, .shortcodeglut-preview-modal-overlay').on('click', function() {
        $('#shortcodeglut-editor-preview-modal').removeClass('active');
        // Clean up preview styles
        $('#shortcodeglut-editor-preview-styles').remove();
    });

    // Prevent closing when clicking inside the modal content
    $('.shortcodeglut-preview-modal-container').on('click', function(e) {
        e.stopPropagation();
    });

    // Close modal on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#shortcodeglut-editor-preview-modal').hasClass('active')) {
            $('#shortcodeglut-editor-preview-modal').removeClass('active');
            $('#shortcodeglut-editor-preview-styles').remove();
        }
    });

    // Function to load and show preview in modal
    function loadEditorPreviewModal() {
        var html = $('#template_html').val();
        var css = $('#template_css').val();
        var templateId = $('input[name="template_id"]').val();

        if (!html || !html.trim()) {
            $('#shortcodeglut-preview-modal-content').html('<p style="text-align:center; color:#6b7280; padding:40px;">Add HTML template to see the preview</p>');
            $('#shortcodeglut-preview-modal-loading').hide();
            $('#shortcodeglut-editor-preview-modal').addClass('active');
            return;
        }

        // Show modal with loading state
        $('#shortcodeglut-preview-modal-loading').show();
        $('#shortcodeglut-preview-modal-content').hide();
        $('#shortcodeglut-editor-preview-modal').addClass('active');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'shortcodeglut_load_editor_preview',
                html: html,
                css: css,
                template_id: templateId,
                nonce: shortcodeglutTemplatesList.previewNonce
            },
            success: function(response) {
                $('#shortcodeglut-preview-modal-loading').hide();
                if (response.success) {
                    $('#shortcodeglut-preview-modal-content').html(response.data.html);
                    $('#shortcodeglut-preview-modal-content').show();

                    // Inject CSS into the DOM
                    $('#shortcodeglut-editor-preview-styles').remove();
                    if (response.data.css) {
                        $('head').append('<style id="shortcodeglut-editor-preview-styles">' + response.data.css + '</style>');
                    }
                } else {
                    $('#shortcodeglut-preview-modal-content').html('<p style="text-align:center; color:#dc2626; padding:40px;">' + (response.data.message || 'Error loading preview') + '</p>');
                    $('#shortcodeglut-preview-modal-content').show();
                }
            },
            error: function() {
                $('#shortcodeglut-preview-modal-loading').hide();
                $('#shortcodeglut-preview-modal-content').html('<p style="text-align:center; color:#dc2626; padding:40px;">Error loading preview</p>');
                $('#shortcodeglut-preview-modal-content').show();
            }
        });
    }

    // ========== Tag Search Functionality ==========

    // Tag search input handler
    $('#shortcodeglut-tag-search-input').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        var $tagsContainer = $('#shortcodeglut-tags-container');
        var $noResults = $('#shortcodeglut-no-results');
        var $allTags = $('.shortcodeglut-tag-item');

        if (searchTerm === '') {
            // Show all tags when search is empty
            $allTags.removeClass('hidden');
            $noResults.hide();
            return;
        }

        var visibleCount = 0;

        // Filter tags based on search term
        $allTags.each(function() {
            var $tag = $(this);
            var tagName = $tag.data('tag').toLowerCase();
            var tagDescription = $tag.data('description') || '';
            var tagDescriptionText = tagDescription.toLowerCase();

            // Check if search term matches tag name or description
            if (tagName.indexOf(searchTerm) !== -1 || tagDescriptionText.indexOf(searchTerm) !== -1) {
                $tag.removeClass('hidden');
                visibleCount++;
            } else {
                $tag.addClass('hidden');
            }
        });

        // Show/hide no results message
        if (visibleCount === 0) {
            $noResults.show();
            $tagsContainer.hide();
        } else {
            $noResults.hide();
            $tagsContainer.show();
        }
    });

    // Clear search on icon click (optional enhancement)
    $('.shortcodeglut-search-icon').on('click', function() {
        $('#shortcodeglut-tag-search-input').val('').trigger('input');
    });
});
