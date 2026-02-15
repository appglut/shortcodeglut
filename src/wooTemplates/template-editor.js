/**
 * Template Editor JavaScript
 */
jQuery(document).ready(function($) {
	'use strict';

	var htmlEditor, cssEditor;
	var listUrl = shortcodeglutTemplateEditor.listUrl;

	// Tab switching functionality
	$('.scg-tab-btn').on('click', function() {
		var tab = $(this).data('tab');

		// Update tabs
		$('.scg-tab-btn').removeClass('active');
		$(this).addClass('active');

		// Update panels
		$('.scg-tab-pane').removeClass('active');
		$('#tab-' + tab).addClass('active');

		// Trigger preview if preview tab is clicked
		if (tab === 'preview') {
			updatePreview();
		}
	});

	// Tag click functionality
	$('.scg-tag-item').on('click', function() {
		var tag = $(this).data('tag');

		// Insert into HTML editor (CodeMirror or textarea)
		if (htmlEditor && htmlEditor.codemirror) {
			var cm = htmlEditor.codemirror;
			var cursor = cm.getCursor();
			cm.replaceSelection(tag);
			cm.focus();
		} else {
			// Fallback to textarea
			var textarea = $('#template_html')[0];
			var start = textarea.selectionStart;
			var end = textarea.selectionEnd;
			var text = textarea.value;
			var before = text.substring(0, start);
			var after = text.substring(end, text.length);

			textarea.value = before + tag + after;
			textarea.selectionStart = textarea.selectionEnd = start + tag.length;
			textarea.focus();
		}
	});

	// Cancel button functionality
	$('#scg-cancel-edit').on('click', function() {
		if (confirm(shortcodeglutTemplateEditor.i18n.confirmCancel)) {
			window.location.href = listUrl;
		}
	});

	// Form submit handling - sync CodeMirror values before submit
	$('#template-editor-form').on('submit', function() {
		$('#scg-save-template').prop('disabled', true).text(shortcodeglutTemplateEditor.i18n.saving);

		// Sync CodeMirror values to textareas before submit
		if (htmlEditor && htmlEditor.codemirror) {
			htmlEditor.codemirror.save();
		}
		if (cssEditor && cssEditor.codemirror) {
			cssEditor.codemirror.save();
		}
	});

	// Initialize CodeMirror if available
	if (typeof wp !== 'undefined' && wp.codeEditor) {
		htmlEditor = wp.codeEditor.initialize('template_html', {
			codemirror: {
				lineNumbers: true,
				mode: 'htmlmixed',
				indentUnit: 4,
				tabSize: 4,
			}
		});

		cssEditor = wp.codeEditor.initialize('template_css', {
			codemirror: {
				lineNumbers: true,
				mode: 'css',
				indentUnit: 4,
				tabSize: 4,
			}
		});
	}

	// Helper function to get editor content
	function getEditorContent(editor) {
		if (editor && editor.codemirror) {
			return editor.codemirror.getValue();
		}
		return $('#' + editor.settings.id).val();
	}

	// Preview update function
	function updatePreview() {
		var html = getEditorContent(htmlEditor);
		var css = getEditorContent(cssEditor);
		var previewArea = $('#scg-template-preview-area');

		if (!html || !html.trim()) {
			previewArea.html('<p class="scg-preview-placeholder">' + shortcodeglutTemplateEditor.i18n.addHtmlTemplate + '</p>');
			return;
		}

		previewArea.html('<div class="scg-preview-loading"><div class="scg-preview-loading-spinner"></div><div class="scg-preview-loading-text">' + shortcodeglutTemplateEditor.i18n.loadingPreview + '</div></div>');

		// Send AJAX request for preview
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'shortcodeglut_preview_template',
				nonce: shortcodeglutTemplateEditor.previewNonce,
				html: html,
				css: css
			},
			success: function(response) {
				if (response.success) {
					previewArea.html('<style>' + css + '</style>' + response.data.html);
				} else {
					previewArea.html('<p class="scg-preview-placeholder">' + shortcodeglutTemplateEditor.i18n.errorLoadingPreview + ' ' + (response.data && response.data.message ? response.data.message : shortcodeglutTemplateEditor.i18n.unknownError) + '</p>');
				}
			},
			error: function() {
				previewArea.html('<p class="scg-preview-placeholder">' + shortcodeglutTemplateEditor.i18n.errorLoadingPreview + '</p>');
			}
		});
	}
});
