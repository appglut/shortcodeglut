<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

class SettingsPage {
	private $menu_slug = 'shortcodeglut_tools';
	private $template_tags = [ 
		'product' => [ 
			'[product_title]' => 'Product Title - Displays the main product title',
			'[product_price]' => 'Product Price - Shows current price (sale or regular)',
			'[product_regular_price]' => 'Regular Price - Displays the regular product price',
			'[product_sale_price]' => 'Sale Price - Shows the discounted price when on sale',
			'[product_short_description]' => 'Short Description - Brief product summary',
			'[product_description]' => 'Full Description - Complete product details',
			'[product_image]' => 'Product Image - Main product featured image',
			'[product_gallery]' => 'Product Gallery - All product images as a gallery',
			'[product_sku]' => 'Product SKU - Stock keeping unit identifier',
			'[product_stock]' => 'Stock Status - Shows if product is in stock',
			'[product_categories]' => 'Categories - List of product categories',
			'[product_tags]' => 'Tags - List of product tags'
		],
		'buttons' => [ 
			'[btn_cart]' => 'Add to Cart Button - Button to add product to cart',
			'[btn_view]' => 'View Product Button - Link to product page',
			'[btn_wishlist]' => 'Add to Wishlist Button - Save product to wishlist',
			'[btn_compare]' => 'Compare Button - Add product to comparison list'
		],
		'ratings' => [ 
			'[product_rating]' => 'Product Rating - Star rating display',
			'[product_rating_count]' => 'Rating Count - Number of customer reviews'
		],
		'attributes' => [ 
			'[product_attributes]' => 'All Attributes - Complete list of product attributes',
			'[product_dimensions]' => 'Dimensions - Product size information',
			'[product_weight]' => 'Weight - Product weight information'
		]
	];

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueEditorScripts' ) );
		add_action( 'wp_ajax_save_woo_template', array( $this, 'ajaxSaveTemplate' ) );
		add_action( 'wp_ajax_shopglut_preview_template', array( $this, 'ajaxPreviewTemplate' ) );
		add_action( 'wp_ajax_shopglut_duplicate_template', array( $this, 'ajaxDuplicateTemplate' ) );
		add_action( 'admin_init', array( $this, 'handleTemplateActions' ) );
	}

	/**
	 * Enqueue scripts and styles for the template editor
	 */
	public function enqueueEditorScripts( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for script loading
		if ( ! isset( $_GET['page'] ) || $this->menu_slug !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for editor type verification
		if ( ! isset( $_GET['editor'] ) || 'woo_template' !== sanitize_text_field( wp_unslash( $_GET['editor'] ) ) ) {
			return;
		}

		// Enqueue CodeMirror for both HTML and CSS editors
		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
	}

	/**
	 * Save template data from form submission
	 */
	private function saveTemplate( $post_data, $template_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_woo_templates';

		// Get form data
		$template_name = isset( $post_data['template_name'] ) ? sanitize_text_field( $post_data['template_name'] ) : '';
		$template_slug = isset( $post_data['template_slug'] ) ? sanitize_key( $post_data['template_slug'] ) : '';
		$template_html = isset( $post_data['template_html'] ) ? wp_unslash( $post_data['template_html'] ) : '';
		$template_css = isset( $post_data['template_css'] ) ? wp_unslash( $post_data['template_css'] ) : '';
		$template_tags = isset( $post_data['template_tags'] ) ? wp_unslash( $post_data['template_tags'] ) : json_encode( $this->template_tags );

		// Validate required fields
		if ( empty( $template_name ) || empty( $template_slug ) ) {
			add_settings_error( 'shopglut_templates', 'required-fields', esc_html__( 'Template name and ID are required.', 'shopglut' ), 'error' );
			return false;
		}

		// Check if template ID already exists for a different template
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for template validation
		$existing_template = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}shopglut_woo_templates WHERE template_id = %s AND id != %d",
			$template_slug,
			$template_id ? $template_id : 0
		) );

		if ( $existing_template ) {
			add_settings_error( 'shopglut_templates', 'duplicate-template-id', esc_html__( 'Template ID must be unique. This ID is already in use.', 'shopglut' ), 'error' );
			return false;
		}

		// Prepare data for database
		$data = array(
			'template_name' => $template_name,
			'template_id' => $template_slug,
			'template_html' => $template_html,
			'template_css' => $template_css,
			'template_tags' => $template_tags,
			'updated_at' => current_time( 'mysql' )
		);

		$success = false;

		if ( $template_id ) {
			// Check if the record exists based on the primary key (id)
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query to check template existence for update
			$existing_record = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}shopglut_woo_templates WHERE id = %d",
				$template_id
			) );

			// Prevent editing of default templates
			if ( $existing_record && isset( $existing_record->is_default ) && $existing_record->is_default == 1 ) {
				add_settings_error( 'shopglut_templates', 'default-template-readonly', esc_html__( 'Cannot edit prebuilt templates. Please create a copy first.', 'shopglut' ), 'error' );
				return false;
			}

			if ( $existing_record ) {
				// Update existing template
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table existence check with caching, safe table name from internal function
				$success = $wpdb->update( $table_name, $data, array( 'id' => $template_id ) );
			} else {
				// Insert new template
				$data['created_at'] = current_time( 'mysql' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert operation
				$success = $wpdb->insert( $table_name, $data );
				$template_id = $wpdb->insert_id;
			}
		} else {
			// Insert new template
			$data['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert operation
			$success = $wpdb->insert( $table_name, $data );
			$template_id = $wpdb->insert_id;
		}

		if ( $success ) {
			add_settings_error( 'shopglut_templates', 'template-saved', esc_html__( 'Template saved successfully.', 'shopglut' ), 'success' );
			return $template_id;
		} else {
			add_settings_error( 'shopglut_templates', 'save-failed', esc_html__( 'Failed to save template.', 'shopglut' ), 'error' );
			return false;
		}
	}

	/**
	 * Display the template editor page
	 */
	public function templateEditorPage() {
		global $wpdb;

		// Get template_id from the URL
		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
		$template = null;

		// If template_id is provided and not 0, fetch the template data from the database
		if ( $template_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query to fetch template data for editing
			$template = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}shopglut_woo_templates WHERE id = %d",
				$template_id
			), ARRAY_A ); // Fetch as associative array

			// Check if this is a default template and prevent editing
			if ( $template && isset( $template['is_default'] ) && $template['is_default'] == 1 ) {
				// Show admin notice for default templates
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-warning"><p>';
					echo esc_html__( 'This is a prebuilt template. To edit it, create a copy first.', 'shopglut' );
					echo '</p></div>';
				} );
			}
		}

		// Handle form submission
		if ( isset( $_POST['save_template'] ) && isset( $_POST['template_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['template_nonce'] ) ), 'save_woo_template' ) ) {
			$saved_template_id = $this->saveTemplate( $_POST, $template_id > 0 ? $template_id : null );
			if ( $saved_template_id ) {
				// Redirect to edit page with template_id to preserve URL and refresh data
				wp_safe_redirect( admin_url( 'admin.php?page=' . $this->menu_slug . '&editor=woo_template&template_id=' . $saved_template_id . '&saved=1' ) );
				exit;
			}
		}

		// Get template data
		$template_name = $template ? $template['template_name'] : '';
		$template_slug = $template ? $template['template_id'] : '';
		$template_html = $template ? $template['template_html'] : $this->getDefaultTemplateHTML();
		$template_css = $template ? $template['template_css'] : $this->getDefaultTemplateCSS();
		$template_tags_json = $template ? $template['template_tags'] : json_encode( $this->template_tags );

		// Format HTML and CSS for better editing experience
		$template_html = $this->formatHTML( $template_html );
		$template_css = $this->formatCSS( $template_css );

		// Display the editor interface
		$this->displayEditorInterface( $template_id, $template_name, $template_slug, $template_html, $template_css, $template_tags_json );
	}
	/**
	 * Display the template editor interface
	 */
	private function displayEditorInterface( $template_id, $template_name, $template_slug, $template_html, $template_css, $template_tags_json ) {
		$is_new = ! $template_id;
		$page_title = $is_new ? esc_html__( 'Add New Template', 'shopglut' ) : esc_html__( 'Edit Template', 'shopglut' );
		$button_text = $is_new ? esc_html__( 'Save Template', 'shopglut' ) : esc_html__( 'Update Template', 'shopglut' );
		$list_url = admin_url( 'admin.php?page=' . $this->menu_slug . '&view=woo_templates' );

		// Display settings errors
		settings_errors( 'shopglut_templates' );

		// Show success message if template was just saved
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for success message display only
		if ( isset( $_GET['saved'] ) && $_GET['saved'] == '1' ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html__( 'Template saved successfully!', 'shopglut' );
				echo '</p></div>';
			} );
		}
		?>
		<div class="wrap scg-template-editor-page">
			<!-- Header -->
			<div class="scg-editor-header">
				<div class="scg-header-left">
					<a href="<?php echo esc_url( $list_url ); ?>" class="scg-back-link">&larr; Back to Templates</a>
				</div>
				<div class="scg-header-center">
					<h1><?php echo esc_html( $page_title ); ?></h1>
				</div>
				<div class="scg-header-actions">
					<button type="button" class="scg-btn scg-btn--secondary" id="scg-cancel-edit">Cancel</button>
					<button type="submit" form="template-editor-form" class="scg-btn scg-btn--primary" id="scg-save-template"><?php echo esc_html( $button_text ); ?></button>
				</div>
			</div>

			<!-- Save Message -->
			<div class="scg-save-message" id="scg-save-message" style="display:none;">
				<span class="scg-save-message-text"></span>
			</div>

			<form method="post" id="template-editor-form">
				<?php wp_nonce_field( 'save_woo_template', 'template_nonce' ); ?>
				<input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>">
				<input type="hidden" name="save_template" value="1">

				<!-- Template Name and ID -->
				<div class="scg-form-section">
					<div class="scg-form-row">
						<div class="scg-form-group scg-form-half">
							<label for="template_name">Template Name <span class="required">*</span></label>
							<input type="text" id="template_name" name="template_name"
								value="<?php echo esc_attr( $template_name ); ?>" placeholder="e.g., My Custom Product Card" required>
						</div>
						<div class="scg-form-group scg-form-half">
							<label for="template_slug">Template ID <span class="required">*</span></label>
							<input type="text" id="template_slug" name="template_slug"
								value="<?php echo esc_attr( $template_slug ); ?>" placeholder="e.g., my_custom_card"
								<?php echo $template_id ? 'readonly' : ''; ?> required>
							<small>Use lowercase letters, numbers, and underscores only. <?php echo $template_id ? 'Cannot be changed after creation.' : 'Used in shortcode: [shopglut_template id="template-id"]'; ?></small>
						</div>
					</div>
				</div>

				<!-- Spacer for better spacing -->
				<div style="height: 16px;"></div>

				<!-- Tabs Navigation -->
				<div class="scg-tabs-nav">
					<button type="button" class="scg-tab-btn active" data-tab="editor">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/></svg>
						Template Editor
					</button>
					<button type="button" class="scg-tab-btn" data-tab="preview">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
						Live Preview
					</button>
				</div>

				<!-- Tab Content -->
				<div class="scg-tabs-content">
					<!-- Editor Tab -->
					<div class="scg-tab-pane active" id="tab-editor">
						<div class="scg-editor-body">
							<!-- HTML & CSS Editors -->
							<div class="scg-editors-section">
								<div class="scg-form-group">
									<label for="template_html">HTML Template <span class="required">*</span></label>
									<textarea id="template_html" class="scg-code-editor" rows="20" placeholder="Enter your HTML template here..." name="template_html"><?php echo esc_textarea( $template_html ); ?></textarea>
								</div>

								<div class="scg-form-group">
									<label for="template_css">Custom CSS</label>
									<textarea id="template_css" class="scg-code-editor" rows="15" placeholder="Enter custom CSS for this template..." name="template_css"><?php echo esc_textarea( $template_css ); ?></textarea>
								</div>

								<input type="hidden" name="template_tags" id="template_tags" value="<?php echo esc_attr( $template_tags_json ); ?>">
							</div>

							<!-- Available Tags Sidebar -->
							<div class="scg-tags-sidebar">
								<div class="scg-tags-header">
									<h3>Available Tags</h3>
									<p>Click to insert into template</p>
								</div>
								<div class="scg-tags-list">
									<?php foreach ( $this->template_tags as $category => $tags ) : ?>
										<div class="scg-tag-section">
											<h4><?php echo esc_html( ucfirst( $category ) ); ?></h4>
											<?php foreach ( $tags as $tag => $description ) : ?>
												<?php
												$tag_parts = explode( ' - ', $description );
												$tag_name = $tag_parts[0];
												$tag_desc = isset( $tag_parts[1] ) ? $tag_parts[1] : '';
												?>
												<div class="scg-tag-item" data-tag="<?php echo esc_attr( $tag ); ?>">
													<code><?php echo esc_html( $tag ); ?></code>
													<small><?php echo esc_html( $tag_desc ? $tag_desc : $tag_name ); ?></small>
												</div>
											<?php endforeach; ?>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Preview Tab -->
					<div class="scg-tab-pane" id="tab-preview">
						<div class="scg-preview-wrapper">
							<div class="scg-preview-header">
								<h3>Template Preview</h3>
								<p class="scg-preview-desc">See how your template will look with sample products (3 items shown in a grid)</p>
							</div>
							<div id="scg-template-preview-area" class="scg-preview-area">
								<p class="scg-preview-placeholder">Add HTML template and switch to this tab to see the preview</p>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>

		<style>
			html.wp-toolbar {
				padding: 0px;
			}
			.scg-template-editor-page {
				font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
			}
			.scg-editor-header {
				display: grid;
				grid-template-columns: auto 1fr auto;
				align-items: center;
				margin-bottom: 24px;
				padding: 20px 24px;
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				gap: 20px;
			}
			.scg-header-left {
				display: flex;
				align-items: center;
			}
			.scg-back-link {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				color: #64748b;
				text-decoration: none;
				font-size: .875rem;
				font-weight: 500;
				transition: all .15s ease;
				width: fit-content;
				padding: 6px 12px;
				background: #f1f5f9;
				border-radius: 6px;
				border: 1px solid #e2e8f0;
			}
			.scg-back-link:hover {
				color: #0284c7;
				background: #f0f9ff;
				border-color: #0284c7;
			}
			.scg-header-center {
				text-align: center;
			}
			.scg-header-center h1 {
				font-size: 1.25rem;
				margin: 0;
				color: #111827;
				font-weight: 600;
			}
			.scg-header-actions {
				display: flex;
				gap: 10px;
				flex-shrink: 0;
				justify-content: flex-end;
			}
			.scg-btn {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				padding: 10px 20px;
				font-size: .875rem;
				font-weight: 500;
				border-radius: 8px;
				border: none;
				cursor: pointer;
				transition: all .15s ease;
				text-decoration: none;
				line-height: 1;
			}
			.scg-btn svg {
				flex-shrink: 0;
			}
			.scg-btn--primary {
				background: #0284c7;
				color: #fff;
				box-shadow: 0 1px 2px rgba(2,132,199,.1);
			}
			.scg-btn--primary:hover {
				background: #0272b6;
				color: #fff;
				box-shadow: 0 2px 4px rgba(2,132,199,.15);
			}
			.scg-btn--secondary {
				background: #f1f5f9;
				color: #475569;
				border: 1px solid #e2e8f0;
			}
			.scg-btn--secondary:hover {
				background: #e2e8f0;
				color: #334155;
			}
			.scg-save-message {
				background: #d1fae5;
				border: 1px solid #10b981;
				color: #065f46;
				padding: 12px 16px;
				border-radius: 8px;
				margin-bottom: 16px;
				display: none;
				align-items: center;
				gap: 8px;
			}
			.scg-save-message.show {
				display: flex;
			}
			.scg-save-message-text {
				font-size: .875rem;
				font-weight: 500;
			}
			.scg-form-section {
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				padding: 24px;
				margin-bottom: 24px;
			}
			.scg-form-row {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 20px;
			}
			.scg-form-group {
				margin-bottom: 20px;
			}
			.scg-form-group label {
				display: block;
				font-size: .875rem;
				font-weight: 600;
				color: #374151;
				margin-bottom: 8px;
				padding-left: 16px;
			}
			.scg-form-group label .required {
				color: #ef4444;
			}
			.scg-form-group input {
				width: 100%;
				padding: 12px 16px;
				border: 1px solid #d1d5db;
				border-radius: 8px;
				font-size: 14px;
				font-family: inherit;
				transition: border-color .15s ease;
			}
			.scg-form-group textarea {
				width: 100%;
				border: 1px solid #d1d5db;
				border-radius: 8px;
				font-size: 14px;
				font-family: inherit;
				transition: border-color .15s ease;
			}
			textarea.scg-code-editor {
				font-family: ui-monospace, SFMono-Regular, monospace;
				font-size: 13px;
				line-height: 1.5;
				resize: vertical;
				min-height: 300px;
				padding: 16px !important;
				padding-left: 20px !important;
			}
			.scg-form-group input:focus, .scg-form-group textarea:focus {
				outline: none;
				border-color: #0284c7;
				box-shadow: 0 0 0 3px rgba(2,132,199,.1);
			}
			.scg-form-group input[readonly] {
				background: #f9fafb;
				color: #6b7280;
			}
			.scg-form-group small {
				display: block;
				font-size: .75rem;
				color: #6b7280;
				margin-top: 4px;
			}
			.scg-code-editor {
				width: 100%;
				font-family: ui-monospace, SFMono-Regular, monospace;
				font-size: 13px;
				line-height: 1.5;
				resize: vertical;
				min-height: 300px;
				padding: 16px !important;
				padding-left: 20px !important;
			}
			.scg-tabs-nav {
				display: flex;
				gap: 2px;
				background: #f3f4f6;
				padding: 6px;
				border-radius: 10px;
				margin-bottom: 24px;
				width: fit-content;
			}
			.scg-tab-btn {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 14px 24px;
				font-size: .875rem;
				font-weight: 600;
				color: #6b7280;
				background: transparent;
				border: none;
				border-radius: 8px;
				cursor: pointer;
				transition: all .2s ease;
			}
			.scg-tab-btn svg {
				width: 18px;
				height: 18px;
				stroke-width: 2;
			}
			.scg-tab-btn:hover {
				color: #1d2327;
				background: #e5e7eb;
			}
			.scg-tab-btn.active {
				color: #0284c7;
				background: #fff;
				box-shadow: 0 1px 3px rgba(0,0,0,.1);
			}
			.scg-tabs-content {
				display: block;
			}
			.scg-tab-pane {
				display: none;
			}
			.scg-tab-pane.active {
				display: block;
			}
			.scg-editor-body {
				display: grid;
				grid-template-columns: 1fr 320px;
				gap: 32px;
				align-items: start;
			}
			.scg-editors-section {
				display: flex;
				flex-direction: column;
				gap: 20px;
			}
			.scg-tags-sidebar {
				position: sticky;
				top: 24px;
			}
			.scg-tags-header {
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				padding: 20px;
				margin-bottom: 16px;
				text-align: center;
			}
			.scg-tags-header h3 {
				font-size: 1rem;
				margin: 0 0 4px 0;
				color: #111827;
				font-weight: 600;
			}
			.scg-tags-header p {
				font-size: .875rem;
				color: #6b7280;
				margin: 0;
			}
			.scg-tags-list {
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				padding: 20px;
				max-height: calc(100vh - 250px);
				overflow-y: auto;
			}
			.scg-tag-section {
				margin-bottom: 20px;
			}
			.scg-tag-section:last-child {
				margin-bottom: 0;
			}
			.scg-tag-section h4 {
				font-size: .75rem;
				margin: 0 0 12px 0;
				color: #6b7280;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: .05em;
			}
			.scg-tag-item {
				background: #f9fafb;
				border: 1px solid #e5e7eb;
				border-radius: 8px;
				padding: 12px;
				margin-bottom: 8px;
				cursor: pointer;
				transition: all .15s ease;
				display: flex;
				flex-direction: column;
				gap: 6px;
			}
			.scg-tag-item:hover {
				border-color: #0284c7;
				background: #e0f2fe;
				transform: translateX(-2px);
				box-shadow: 0 2px 8px rgba(2,132,199,.1);
			}
			.scg-tag-item code {
				font-family: ui-monospace, monospace;
				font-size: .8rem;
				background: #fff;
				padding: 6px 10px;
				border-radius: 6px;
				color: #0284c7;
				border: 1px solid #bae6fd;
				display: block;
				font-weight: 600;
				text-align: center;
				word-break: break-all;
			}
			.scg-tag-item small {
				color: #4b5563;
				font-size: .875rem;
				text-align: center;
				display: block;
				line-height: 1.3;
			}
			.scg-preview-wrapper {
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				padding: 32px;
			}
			.scg-preview-header {
				margin-bottom: 24px;
				padding-bottom: 16px;
				border-bottom: 1px solid #e5e7eb;
			}
			.scg-preview-header h3 {
				font-size: 1.25rem;
				margin: 0 0 8px 0;
				color: #111827;
				font-weight: 600;
			}
			.scg-preview-desc {
				font-size: .875rem;
				color: #6b7280;
				margin: 0;
			}
			.scg-preview-area {
				min-height: 400px;
				background: #f9fafb;
				border: 1px dashed #d1d5db;
				border-radius: 8px;
				padding: 32px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.scg-preview-placeholder {
				color: #9ca3af;
				font-size: .875rem;
				text-align: center;
			}
			.scg-preview-loading {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 12px;
				color: #64748b;
			}
			.scg-preview-loading-spinner {
				width: 40px;
				height: 40px;
				border: 3px solid #e2e8f0;
				border-top-color: #0284c7;
				border-radius: 50%;
				animation: scg-spin .8s linear infinite;
			}
			@keyframes scg-spin {
				to {
					transform: rotate(360deg);
				}
			}
			.scg-preview-loading-text {
				font-size: .875rem;
				font-weight: 500;
			}
			@media (max-width: 1200px) {
				.scg-editor-body {
					grid-template-columns: 1fr;
				}
				.scg-form-row {
					grid-template-columns: 1fr;
				}
			}
			@media (max-width: 768px) {
				.scg-tags-sidebar {
					position: static;
				}
				.scg-editor-header {
					grid-template-columns: 1fr;
					gap: 12px;
				}
				.scg-header-left {
					justify-content: flex-start;
				}
				.scg-header-center {
					text-align: left;
				}
				.scg-header-actions {
					width: 100%;
					justify-content: flex-start;
				}
			}
		</style>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var htmlEditor, cssEditor;

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
				if (confirm('<?php echo esc_js( __( 'Are you sure you want to cancel? Any unsaved changes will be lost.', 'shopglut' ) ); ?>')) {
					window.location.href = '<?php echo esc_js( $list_url ); ?>';
				}
			});

			// Form submit handling - sync CodeMirror values before submit
			$('#template-editor-form').on('submit', function() {
				$('#scg-save-template').prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'shopglut' ) ); ?>');

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
					previewArea.html('<p class="scg-preview-placeholder">Add HTML template to see the preview</p>');
					return;
				}

				previewArea.html('<div class="scg-preview-loading"><div class="scg-preview-loading-spinner"></div><div class="scg-preview-loading-text">Loading preview...</div></div>');

				// Send AJAX request for preview
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'shopglut_preview_template',
						nonce: '<?php echo wp_create_nonce( 'shopglut_preview_nonce' ); ?>',
						html: html,
						css: css
					},
					success: function(response) {
						if (response.success) {
							previewArea.html('<style>' + css + '</style>' + response.data.html);
						} else {
							previewArea.html('<p class="scg-preview-placeholder">Error loading preview: ' + (response.data && response.data.message ? response.data.message : 'Unknown error') + '</p>');
						}
					},
					error: function() {
						previewArea.html('<p class="scg-preview-placeholder">Error loading preview</p>');
					}
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Format HTML with proper indentation
	 */
	private function formatHTML( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		$formatted = '';
		$indent = 0;
		$indent_str = '    '; // 4 spaces

		// Remove extra whitespace
		$html = trim( preg_replace( '/>\s+</', '><', $html ) );

		// Split into tokens
		preg_match_all( '/(<[^>]+>|[^<]+)/', $html, $matches );
		$tokens = $matches[0];

		foreach ( $tokens as $token ) {
			$token = trim( $token );
			if ( empty( $token ) ) {
				continue;
			}

			// Check if it's a tag
			if ( substr( $token, 0, 1 ) === '<' ) {
				if ( substr( $token, 0, 2 ) === '</' ) {
					// Closing tag
					$indent = max( 0, $indent - 1 );
					$formatted .= str_repeat( $indent_str, $indent ) . $token . "\n";
				} elseif ( substr( $token, -2 ) === '/>' ) {
					// Self-closing tag
					$formatted .= str_repeat( $indent_str, $indent ) . $token . "\n";
				} else {
					// Opening tag
					$formatted .= str_repeat( $indent_str, $indent ) . $token . "\n";
					$indent++;
				}
			} else {
				// Text content
				$formatted .= str_repeat( $indent_str, $indent ) . $token . "\n";
			}
		}

		return trim( $formatted );
	}

	/**
	 * Format CSS with proper indentation
	 */
	private function formatCSS( $css ) {
		if ( empty( $css ) ) {
			return $css;
		}

		// If CSS already appears formatted, return as-is
		if ( strpos( $css, "\n" ) !== false ) {
			return $css;
		}

		// Remove comments first
		$css = preg_replace( '#/\*.*?\*/#s', '', $css );

		// Parse CSS and format with proper indentation
		$formatted = '';
		$indent = 0;
		$indent_str = '    '; // 4 spaces

		// Split into tokens preserving braces
		$tokens = preg_split( '/([{};])/', $css, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$in_selector = true;
		$property_buffer = '';

		foreach ( $tokens as $token ) {
			$token = trim( $token );

			if ( empty( $token ) && $token !== '0' ) {
				continue;
			}

			if ( $token === '{' ) {
				if ( ! empty( $property_buffer ) ) {
					$formatted .= trim( $property_buffer ) . " {\n";
					$property_buffer = '';
				} else {
					$formatted .= " {\n";
				}
				$indent++;
				$in_selector = false;
			} elseif ( $token === '}' ) {
				$indent = max( 0, $indent - 1 );
				$formatted .= str_repeat( $indent_str, $indent ) . "}\n\n";
				$in_selector = true;
			} elseif ( $token === ';' ) {
				if ( ! empty( $property_buffer ) ) {
					$formatted .= str_repeat( $indent_str, $indent ) . trim( $property_buffer ) . ";\n";
					$property_buffer = '';
				}
			} elseif ( $in_selector ) {
				$property_buffer .= $token;
			} else {
				// Property - add to buffer until we hit semicolon
				$property_buffer .= $token;
			}
		}

		return trim( $formatted );
	}

	/**
	 * Render a code editor field
	 */
	private function renderCodeEditor( $args ) {
		$defaults = array(
			'id' => '',
			'title' => '',
			'settings' => array(),
			'value' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Format HTML before rendering
		$is_html = isset( $args['settings']['mode'] ) && $args['settings']['mode'] !== 'css';
		if ( $is_html && ! empty( $args['value'] ) ) {
			$args['value'] = $this->formatHTML( $args['value'] );
		}

		// Create a unique ID for the editor
		$editor_id = ! empty( $args['id'] ) ? $args['id'] : 'shopglut-code-editor-' . uniqid();

		// Set up CodeMirror settings based on mode
		$is_html = $args['settings']['mode'] !== 'css';
		$code_editor_settings = array(
			'type' => $is_html ? 'text/html' : 'text/css',
			'codemirror' => array(
				'lineNumbers' => true,
				'mode' => $is_html ? 'htmlmixed' : 'css',
				'indentUnit' => 4,
				'tabSize' => 4,
				'autoCloseTags' => true,
				'autoCloseBrackets' => true,
				'matchTags' => true,
				'matchBrackets' => true,
				'styleActiveLine' => true,
				'extraKeys' => array(
					'Ctrl-Space' => 'autocomplete',
				),
			),
		);
		?>
		<div class="shopglut-code-editor-field">
			<?php if ( ! empty( $args['title'] ) ) : ?>
				<h3 class="shopglut-code-editor-title"><?php echo esc_html( $args['title'] ); ?></h3>
			<?php endif; ?>

			<div class="shopglut-code-editor-container">
				<textarea id="<?php echo esc_attr( $editor_id ); ?>" name="<?php echo esc_attr( $editor_id ); ?>"
					class="shopglut-code-editor large-text code"
					rows="20"
					style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $args['value'] ); ?></textarea>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						if (typeof wp !== 'undefined' && wp.codeEditor) {
							wp.codeEditor.initialize('<?php echo esc_js( $editor_id ); ?>', <?php echo wp_json_encode( $code_editor_settings ); ?>);
						}
					});
				</script>
			</div>
		</div>
		<?php
	}

	/**
	 * Get default template HTML
	 */
	private function getDefaultTemplateHTML() {
		return '<div class="shopglut-product-template">
    <div class="product-image">
        [product_image]
    </div>
    <div class="product-details">
        <h2 class="product-title">[product_title]</h2>
        <div class="product-price">
            [product_price]
        </div>
        <div class="product-rating">
            [product_rating]
        </div>
        <div class="product-description">
            [product_short_description]
        </div>
        <div class="product-actions">
            [btn_cart]
            [btn_view]
        </div>
    </div>
</div>';
	}

	/**
	 * Get default template CSS
	 */
	private function getDefaultTemplateCSS() {
		return '.shopglut-product-template {
    display: flex;
    flex-wrap: wrap;
    max-width: 100%;
    margin-bottom: 30px;
    border: 1px solid #eee;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.product-image {
    flex: 0 0 40%;
    max-width: 40%;
    padding-right: 20px;
}

.product-details {
    flex: 0 0 60%;
    max-width: 60%;
}

.product-title {
    font-size: 24px;
    margin-top: 0;
    margin-bottom: 10px;
}

.product-price {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

.product-description {
    margin-bottom: 20px;
    color: #666;
}

.product-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .shopglut-product-template {
        flex-direction: column;
    }

    .product-image,
    .product-details {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .product-image {
        padding-right: 0;
        margin-bottom: 20px;
    }
}';
	}

	/**
	 * AJAX handler for saving template data
	 */
	public function ajaxSaveTemplate() {
		// Check nonce for security
		if ( ! isset( $_POST['template_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['template_nonce'] ) ), 'save_woo_template' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
			return;
		}

		// Get template ID
		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : null;

		// Save the template$
		global $wpdb;

		$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
		$template_slug = isset( $_POST['template_slug'] ) ? sanitize_key( wp_unslash( $_POST['template_slug'] ) ) : '';
		$template_html = isset( $_POST['template_html'] ) ? wp_kses_post( wp_unslash( $_POST['template_html'] ) ) : '';
		$template_css = isset( $_POST['template_css'] ) ? sanitize_textarea_field( wp_unslash( $_POST['template_css'] ) ) : '';
		$template_tags = isset( $_POST['template_tags'] ) ? sanitize_textarea_field( wp_unslash( $_POST['template_tags'] ) ) : json_encode( $this->template_tags );

		// Validate required fields
		if ( empty( $template_name ) || empty( $template_slug ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Template name and ID are required.', 'shopglut' ) ) );
			return;
		}

		// Check if template ID already exists for a different template
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for template validation in AJAX handler
		$existing_template = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}shopglut_woo_templates WHERE template_id = %s AND id != %d",
			$template_slug,
			$template_id ? $template_id : 0
		) );

		if ( $existing_template ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Template ID must be unique. This ID is already in use.', 'shopglut' ) ) );
			return;
		}

		// Prepare data for database
		$data = array(
			'template_name' => $template_name,
			'template_id' => $template_slug,
			'template_html' => $template_html,
			'template_css' => $template_css,
			'template_tags' => $template_tags,
			'updated_at' => current_time( 'mysql' )
		);

		$success = false;
		$new_id = 0;

		if ( $template_id ) {
			// Check if the record exists based on the primary key (id)
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query to check template existence for AJAX update
			$existing_record = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}shopglut_woo_templates WHERE id = %d",
				$template_id
			) );

			if ( $existing_record ) {
				// Update existing template
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table existence check with caching, safe table name from internal function
				$success = $wpdb->update( $table_name, $data, array( 'id' => $template_id ) );

			} else {
				// Insert new template
				$data['created_at'] = current_time( 'mysql' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert operation
				$success = $wpdb->insert( $table_name, $data );
				$new_id = $wpdb->insert_id;
			}
		} else {
			// Insert new template
			$data['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert operation
			$success = $wpdb->insert( $table_name, $data );
			$new_id = $wpdb->insert_id;
		}

		if ( $success ) {
			wp_send_json_success( array( 'message' => esc_html__( 'Template saved successfully.', 'shopglut' ), 'template_id' => $new_id ? $new_id : $template_id ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to save template.', 'shopglut' ) ) );
		}
	}

	/**
	 * AJAX handler for template preview
	 */
	public function ajaxPreviewTemplate() {
		// Check nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'shopglut_preview_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
			return;
		}

		$html = isset( $_POST['html'] ) ? wp_kses_post( wp_unslash( $_POST['html'] ) ) : '';
		$css = isset( $_POST['css'] ) ? wp_kses_post( wp_unslash( $_POST['css'] ) ) : '';

		if ( empty( $html ) ) {
			wp_send_json_error( array( 'message' => 'HTML template is required.' ) );
			return;
		}

		// Get a sample product for preview
		$product = $this->get_sample_product_for_preview();

		// Process template tags with product data
		$preview_html = $this->process_template_tags_for_preview( $html, $product );

		wp_send_json_success( array( 'html' => $preview_html ) );
	}

	/**
	 * Get sample product for preview
	 */
	private function get_sample_product_for_preview() {
		// Try to get a real WooCommerce product
		if ( class_exists( 'WooCommerce' ) ) {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			);
			$product_ids = get_posts( $args );
			if ( ! empty( $product_ids ) ) {
				return wc_get_product( $product_ids[0] );
			}
		}

		// Return a sample product object
		return $this->create_mock_product();
	}

	/**
	 * Create a mock product for preview
	 */
	private function create_mock_product() {
		$mock = new stdClass();

		$mock->ID = 0;
		$mock->post_title = 'Sample Product';
		$mock->post_excerpt = 'This is a sample product for preview purposes.';
		$mock->post_content = 'This is the full description of the sample product.';
		$mock->post_name = 'sample-product';
		$mock->regular_price = '29.99';
		$mock->sale_price = '';
		$mock->price = '29.99';
		$mock->price_html = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span>29.99</bdi></span>';
		$mock->sku = 'SAMPLE-001';
		$mock->stock_status = 'instock';
		$mock->average_rating = '4.5';
		$mock->rating_count = 15;
		$mock->image_id = 0;
		$mock->image_url = 'https://via.placeholder.com/300x300?text=Sample+Product';
		$mock->permalink = '#';
		$mock->add_to_cart_url = '#';
		$mock->add_to_cart_text = 'Add to cart';

		return $mock;
	}

	/**
	 * Process template tags for preview
	 */
	private function process_template_tags_for_preview( $html, $product ) {
		$is_wc_product = is_object( $product ) && method_exists( $product, 'get_id' );

		// Helper function to get product data
		$get_data = function( $key, $default = '' ) use ( $product, $is_wc_product ) {
			if ( $is_wc_product ) {
				switch ( $key ) {
					case 'id':
						return $product->get_id();
					case 'title':
						return $product->get_name();
					case 'price_html':
						return $product->get_price_html();
					case 'short_description':
						return $product->get_short_description();
					case 'description':
						return $product->get_description();
					case 'sku':
						return $product->get_sku();
					case 'average_rating':
						return $product->get_average_rating();
					case 'image_id':
						return $product->get_image_id();
					case 'permalink':
						return get_permalink( $product->get_id() );
					case 'add_to_cart_url':
						return $product->add_to_cart_url();
					case 'add_to_cart_text':
						return $product->add_to_cart_text();
					default:
						return $default;
				}
			} else {
				// Mock product
				switch ( $key ) {
					case 'id':
						return isset( $product->ID ) ? $product->ID : $default;
					case 'title':
						return isset( $product->post_title ) ? $product->post_title : $default;
					case 'price_html':
						return isset( $product->price_html ) ? $product->price_html : $default;
					case 'short_description':
						return isset( $product->post_excerpt ) ? $product->post_excerpt : $default;
					case 'description':
						return isset( $product->post_content ) ? $product->post_content : $default;
					case 'sku':
						return isset( $product->sku ) ? $product->sku : $default;
					case 'average_rating':
						return isset( $product->average_rating ) ? $product->average_rating : $default;
					case 'image_id':
						return isset( $product->image_id ) ? $product->image_id : 0;
					case 'image_url':
						return isset( $product->image_url ) ? $product->image_url : '';
					case 'permalink':
						return isset( $product->permalink ) ? $product->permalink : '#';
					default:
						return $default;
				}
			}
		};

		// Build replacements array
		$replacements = array(
			'[product_title]' => $get_data( 'title' ),
			'[product_price]' => $get_data( 'price_html' ),
			'[product_short_description]' => $get_data( 'short_description' ),
			'[product_description]' => $get_data( 'description' ),
			'[product_sku]' => $get_data( 'sku' ),
			'[product_rating]' => $is_wc_product ? wc_get_rating_html( $get_data( 'average_rating' ) ) : '<div class="star-rating" role="img" aria-label="Rated 4.5 out of 5"><span style="width:90%">Rated 4.5 out of 5</span></div>',
		);

		// Product image
		if ( strpos( $html, '[product_image]' ) !== false ) {
			$image_id = $get_data( 'image_id' );
			if ( $is_wc_product && $image_id ) {
				$image = wp_get_attachment_image( $image_id, 'medium' );
			} else {
				$image_url = $is_wc_product ? wc_placeholder_img_src( 'medium' ) : ( isset( $product->image_url ) ? $product->image_url : 'https://via.placeholder.com/300x300?text=Sample+Product' );
				$image = sprintf( '<img src="%s" alt="%s" class="attachment-medium size-medium" />', esc_url( $image_url ), esc_attr( $get_data( 'title' ) ) );
			}
			$replacements['[product_image]'] = $image;
		}

		// Buttons
		if ( strpos( $html, '[btn_cart]' ) !== false ) {
			if ( $is_wc_product ) {
				$cart_btn = sprintf(
					'<a href="%s" class="button" data-product_id="%d">%s</a>',
					esc_url( $get_data( 'add_to_cart_url' ) ),
					$get_data( 'id' ),
					esc_html( $get_data( 'add_to_cart_text' ) )
				);
			} else {
				$cart_btn = '<button class="button" disabled>Add to Cart</button>';
			}
			$replacements['[btn_cart]'] = $cart_btn;
		}

		if ( strpos( $html, '[btn_view]' ) !== false ) {
			$view_btn = sprintf(
				'<a href="%s" class="button">View Product</a>',
				esc_url( $get_data( 'permalink' ) )
			);
			$replacements['[btn_view]'] = $view_btn;
		}

		// Replace all tags
		foreach ( $replacements as $tag => $replacement ) {
			$html = str_replace( $tag, $replacement, $html );
		}

		return $html;
	}

	/**
	 * AJAX handler for duplicating a template
	 */
	public function ajaxDuplicateTemplate() {
		// Check nonce for security
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce check before sanitization
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'shopglut_duplicate_template' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
			return;
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => 'Invalid template ID.' ) );
			return;
		}

		// Get the original template
		$template = WooTemplatesEntity::get_template( $template_id );

		if ( ! $template ) {
			wp_send_json_error( array( 'message' => 'Template not found.' ) );
			return;
		}

		// Note: We allow duplicating default templates - the copy will have is_default = 0

		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_woo_templates';

		// Generate unique template ID by adding copy suffix
		$template_id_slug = $template['template_id'];
		$new_template_id = $template_id_slug . '-copy';
		$count = 1;

		// Check if the new ID exists and increment until we find a unique one
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE template_id = %s", $new_template_id ) ) ) {
			$new_template_id = $template_id_slug . '-copy-' . $count;
			$count++;
		}

		// Prepare new template data
		$new_template = array(
			'template_name' => $template['template_name'] . ' (Copy)',
			'template_id' => $new_template_id,
			'template_html' => $template['template_html'] ?? '',
			'template_css' => $template['template_css'] ?? '',
			'template_tags' => $template['template_tags'] ?? '',
			'is_default' => 0,
		);

		// Insert the new template
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert operation
		$result = $wpdb->insert( $table_name, $new_template );

		if ( $result ) {
			// Clear cache
			wp_cache_delete( 'shopglut_woo_templates_count' );
			wp_cache_flush();

			wp_send_json_success( array(
				'message' => 'Template duplicated successfully.',
				'new_template_id' => $wpdb->insert_id
			) );
		} else {
			// Log error for debugging
			error_log( 'ShopGlut: Failed to duplicate template. Last error: ' . $wpdb->last_error );
			wp_send_json_error( array( 'message' => 'Failed to duplicate template. DB Error: ' . $wpdb->last_error ) );
		}
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * Handle template actions like delete
	 */
	public function handleTemplateActions() {
		// Check if we're on the templates page
		if ( ! isset( $_GET['page'] ) || $this->menu_slug !== $_GET['page'] ) {
			return;
		}

		// Handle delete action
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['template_id'] ) ) {
			$template_id = intval( $_GET['template_id'] );

			// Verify nonce
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_template_' . $template_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'shopglut' ) );
			}

			// Check if this is a default template
			$template = WooTemplatesEntity::get_template( $template_id );
			if ( $template && isset( $template['is_default'] ) && $template['is_default'] == 1 ) {
				// Redirect back with error message
				wp_safe_redirect( add_query_arg(
					array(
						'page' => $this->menu_slug,
						'view' => 'woo_templates',
						'error' => 'default_template'
					),
					admin_url( 'admin.php' )
				) );
				exit;
			}

			// Delete the template
			WooTemplatesEntity::delete_template( $template_id );

			// Redirect back to the templates list with a success message
			wp_safe_redirect( add_query_arg(
				array(
					'page' => $this->menu_slug,
					'view' => 'woo_templates',
					'deleted' => '1'
				),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		// Handle bulk delete action
		if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['template_ids'] ) && is_array( $_POST['template_ids'] ) ) {
			// Verify nonce
			check_admin_referer( 'bulk-templates' );

			$deleted = 0;
			$template_ids = array_map( 'sanitize_text_field', wp_unslash( $_POST['template_ids'] ) );
			foreach ( $template_ids as $template_id ) {
				$template_id = intval( $template_id );

				// Check if this is a default template
				$template = WooTemplatesEntity::get_template( $template_id );
				if ( $template && isset( $template['is_default'] ) && $template['is_default'] == 1 ) {
					continue; // Skip default templates
				}

				WooTemplatesEntity::delete_template( $template_id );
				$deleted++;
			}

			// Redirect back to the templates list with a success message
			wp_safe_redirect( add_query_arg(
				array(
					'page' => $this->menu_slug,
					'view' => 'woo_templates',
					'deleted' => $deleted
				),
				admin_url( 'admin.php' )
			) );
			exit;
		}
	}


}