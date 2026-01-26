<?php
namespace Shortcodeglut\tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ShortcodeglutTools {

	public $not_implemented;

	public function __construct() {

		add_filter( 'admin_body_class', array( $this, 'shortcodeglutBodyClass' ) );

        $this->not_implemented = true;

	}

	public function shortcodeglutBodyClass( $classes ) {
			$current_screen = get_current_screen();

			if ( empty( $current_screen ) ) {
				return $classes;
			}

			if ( false !== strpos( $current_screen->id, 'shortcodeglut_' ) ) {
				$classes .= ' shortcodeglut-admin';
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for body class only
			if ( isset( $_GET['page'] ) && 'shortcodeglut' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['editor'] ) ) {
				$classes .= '-shortcodeglut-editor-collapse ';
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class addition only
			if ( isset( $_GET['page'] ) && 'shortcodeglut' === sanitize_text_field( wp_unslash($_GET['page']) ) ) {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class addition only
				if ( isset( $_GET['editor'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for template type
					$editor = sanitize_text_field( wp_unslash($_GET['editor']) );

					switch ( $editor ) {
						case 'woo_template':
							$classes .= ' shortcodeglut-woo-template-editor';
							break;
					}
				}
			}

			return $classes;
	}

	public function rendertoolsPages() {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for routing only
			if ( ! isset( $_GET['page'] ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'shortcodeglut' ) );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for routing only
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for routing only
			$editor = isset( $_GET['editor'] ) ? sanitize_text_field( wp_unslash( $_GET['editor'] ) ) : '';

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for routing only
			$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

			// Handle shortcodeglut page
			if ( 'shortcodeglut' === $page ) {

				// Editor routes
				if ( ! empty( $editor )) {
					switch ( $editor ) {
						case 'woo_template':
							$woo_template_settings = \Shortcodeglut\wooTemplates\SettingsPage::get_instance();
							$woo_template_settings->templateEditorPage();
							break;
						default:
							wp_die( esc_html__( 'Invalid editor type.', 'shortcodeglut' ) );
					}
				}
				// View routes
				elseif ( ! empty( $view ) ) {
					switch ( $view ) {
						case 'shortcode_showcase':
							$this->renderShortcodeShowcase();
							break;
						case 'woo_templates':
							$this->renderProductTemplates();
							break;
						default:
							$this->renderWooCommerceTools();
							break;
					}
				}
				// Default shortcodeglut page - redirect to shortcode showcase
				else {
					$this->renderShortcodeShowcase();
				}
			}
			else {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'shortcodeglut' ) );
			}
	}


	public function settingsPageHeader( $active_menu ) {
		$logo_url = SHORTCODEGLUT_URL . 'global-assets/images/header-logo.svg';
		?>
		<div class="shortcodeglut-page-header">
			<div class="shortcodeglut-page-header-wrap">
				<div class="shortcodeglut-page-header-banner shortcodeglut-pro shortcodeglut-no-submenu">
					<div class="shortcodeglut-page-header-banner__logo">
						<img src="<?php echo esc_url( $logo_url );// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" alt="">
					</div>
					<div class="shortcodeglut-page-header-banner__helplinks">
						<span><a rel="noopener"
								href="https://documentation.appglut.com/?utm_source=shortcodeglut-plugin-admin&utm_medium=referral&utm_campaign=adminmenu"
								target="_blank">
								<span class="dashicons dashicons-admin-page"></span>
								<?php echo esc_html__( 'Documentation', 'shortcodeglut' ); ?>
							</a></span>
						<span><a class="shortcodeglut-active" rel="noopener"
								href="https://www.appglut.com/plugin/shortcodeglut/?utm_source=shortcodeglut-plugin-admin&utm_medium=referral&utm_campaign=upgrade"
								target="_blank">
								<span class="dashicons dashicons-unlock"></span>
								<?php echo esc_html__( 'Unlock Pro Edition', 'shortcodeglut' ); ?>
							</a></span>
						<span><a rel="noopener"
								href="https://www.appglut.com/support/?utm_source=shortcodeglut-plugin-admin&utm_medium=referral&utm_campaign=support"
								target="_blank">
								<span class="dashicons dashicons-share-alt"></span>
								<?php echo esc_html__( 'Support', 'shortcodeglut' ); ?>
							</a></span>
					</div>
					<div class="clear"></div>
					<?php $this->settingsPageHeaderMenus( $active_menu ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function settingsPageHeaderMenus( $active_menu ) {

		$menus = $this->headerMenuTabs();

		if ( count( $menus ) < 2 ) {
			return;
		}

		?>
		<div class="shortcodeglut-header-menus">
			<nav class="shortcodeglut-nav-tab-wrapper nav-tab-wrapper">
				<?php foreach ( $menus as $menu ) : ?>
					<?php $id = $menu['id'];
					$url = esc_url_raw( ! empty( $menu['url'] ) ? $menu['url'] : '' );
					?>
					<a href="<?php echo esc_url( remove_query_arg( wp_removable_query_args(), $url ) ); ?>"
						class="shortcodeglut-nav-tab nav-tab<?php echo esc_attr( $id ) == esc_attr( $active_menu ) ? ' shortcodeglut-nav-active' : ''; ?>">
						<?php echo esc_html( $menu['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}

	public function defaultHeaderMenu() {
		return 'shortcode_showcase';
	}

	public function headerMenuTabs() {
		$tabs = [
			10 => [ 'id' => 'shortcode_showcase', 'url' => admin_url( 'admin.php?page=shortcodeglut&view=shortcode_showcase' ), 'label' => '💻 ' . esc_html__( 'Shortcode Showcase', 'shortcodeglut' ) ],
			15 => [ 'id' => 'woo_templates', 'url' => admin_url( 'admin.php?page=shortcodeglut&view=woo_templates' ), 'label' => '📋 ' . esc_html__( 'Woo Templates', 'shortcodeglut' ) ],
		];

		ksort( $tabs );

		return $tabs;
	}

	public function activeMenuTab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for menu display only
		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for menu display only
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for menu display only
		$nonce_check = isset( $_GET['url_nonce_check'] ) ? sanitize_text_field( wp_unslash( $_GET['url_nonce_check'] ) ) : '';

		if ( ( ! wp_verify_nonce( $nonce_check, 'url_nonce_value' ) ) && ( strpos( $page, 'shortcodeglut' ) !== false ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for menu display only
			return isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : $this->defaultHeaderMenu();
		}

		return false;
  }

	public function renderShortcodeShowcase() {
		$active_menu = $this->activeMenuTab();
		$this->settingsPageHeader( $active_menu );
		?>
		<?php
		// Include the shortcode showcase
		require_once SHORTCODEGLUT_PATH . 'src/shortcodeShowcase/AdminPage.php';
		$shortcodeShowcase = new \Shortcodeglut\shortcodeShowcase\AdminPage();

		// Render the shortcode showcase content only
		$shortcodeShowcase->renderShortcodeShowcaseContent();
		?>
		<?php
	}

	public function renderProductTemplates() {
		$active_menu = $this->activeMenuTab();
		$this->settingsPageHeader( $active_menu );
		?>
		<?php
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}
		?>

		<?php
		// Include the woo templates system
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplates.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesListTable.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesEntity.php';

		// Initialize WooTemplates
		\Shortcodeglut\wooTemplates\WooTemplates::get_instance();

		// Ensure default templates exist
		\Shortcodeglut\wooTemplates\WooTemplatesEntity::insert_default_templates();

		// Handle individual delete action
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for action routing
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['template_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for template ID
			$template_id = absint( $_GET['template_id'] );

			// Verify nonce
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is performed here
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_template_' . $template_id ) ) {
				// Delete the template
				\Shortcodeglut\wooTemplates\WooTemplatesEntity::delete_template( $template_id );

				// Redirect to avoid resubmission
				wp_safe_redirect( admin_url( 'admin.php?page=shortcodeglut&view=woo_templates&deleted=true' ) );
				exit;
			} else {
				wp_die( esc_html__( 'Security check failed.', 'shortcodeglut' ) );
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for success message display only
		if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === 'true' ) {
			echo '<div class="updated notice"><p>' . esc_html__( 'Template deleted successfully.', 'shortcodeglut' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for error message display only
		if ( isset( $_GET['error'] ) && $_GET['error'] === 'default_template' ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Cannot delete prebuilt templates.', 'shortcodeglut' ) . '</p></div>';
		}

		$templates_table = new \Shortcodeglut\wooTemplates\WooTemplatesListTable();
		$templates_table->prepare_items();
		?>

		<div class="wrap shortcodeglut-admin-contents">
			<h2><?php echo esc_html__( 'Woo Templates', 'shortcodeglut' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut&editor=woo_template' ) ); ?>">
					<span class="add-new-h2"><?php echo esc_html__( 'Add New Template', 'shortcodeglut' ); ?></span>
				</a>
			</h2>
			<form method="post">
				<?php $templates_table->display(); ?>
			</form>
		</div>

		<script type="text/javascript">
		(function($) {
			'use strict';

			// Make sure jQuery is loaded
			if (typeof $ === 'undefined') {
				console.error('ShortcodeGlut: jQuery not loaded');
				return;
			}

			// Copy template ID functionality
			$(document).on('click', '.shopglut-copy-id-btn', function(e) {
				e.preventDefault();
				var templateId = $(this).data('template-id');
				var $button = $(this);
				var $wrapper = $button.closest('.shopglut-template-id-wrapper');

				// Create temporary input for copying
				var tempInput = $('<input>');
				$('body').append(tempInput);
				tempInput.val(templateId).select();
				document.execCommand('copy');
				tempInput.remove();

				// Visual feedback
				$wrapper.addClass('shopglut-copied');
				$button.find('.dashicons').addClass('dashicons-yes').removeClass('dashicons-admin-page');

				// Reset after 2 seconds
				setTimeout(function() {
					$wrapper.removeClass('shopglut-copied');
					$button.find('.dashicons').addClass('dashicons-admin-page').removeClass('dashicons-yes');
				}, 2000);
			});

			// Sample product data for preview
			var sampleProduct = {
				title: 'Premium Wireless Headphones',
				permalink: '#',
				image: '<?php echo esc_url( SHORTCODEGLUT_URL . 'global-assets/images/demo-image.png' ); ?>',
				price: '$149.00',
				regularPrice: '$199.00',
				salePrice: '$149.00',
				onSale: true,
				rating: 4.5,
				ratingCount: 128,
				shortDesc: 'Experience premium sound quality with our wireless headphones. Features active noise cancellation, 30-hour battery life, and comfortable over-ear design.',
				categories: '<a href="#">Electronics</a>, <a href="#">Audio</a>',
				inStock: true,
				stockStatus: 'In Stock',
				addToCartUrl: '#',
				sku: 'WH-PREMIUM-001'
			};

			// Template tag replacement function
			function replaceTemplateTags(html, product) {
				var replaced = html
					.replace(/\[product_title\]/g, product.title)
					.replace(/\[product_permalink\]/g, product.permalink)
					.replace(/\[product_price\]/g, product.onSale ? '<del>' + product.regularPrice + '</del> <ins>' + product.salePrice + '</ins>' : product.regularPrice)
					.replace(/\[product_regular_price\]/g, product.regularPrice)
					.replace(/\[product_sale_price\]/g, product.salePrice)
					.replace(/\[product_short_description\]/g, product.shortDesc)
					.replace(/\[product_categories\]/g, product.categories)
					.replace(/\[product_stock\]/g, product.stockStatus)
					.replace(/\[product_sku\]/g, product.sku)
					.replace(/\[product_rating\]/g, generateStarRating(product.rating))
					.replace(/\[btn_cart\]/g, '<a href="' + product.addToCartUrl + '" class="button add_to_cart_button">Add to Cart</a>')
					.replace(/\[btn_view\]/g, '<a href="' + product.permalink + '" class="button">View Product</a>')
					.replace(/\[product_image\]/g, generateProductImage(product));

				return replaced;
			}

			// Generate star rating HTML
			function generateStarRating(rating) {
				var fullStars = Math.floor(rating);
				var halfStar = rating % 1 >= 0.5 ? true : false;
				var emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
				var html = '<div class="shortcodeglut-star-rating">';

				for (var i = 0; i < fullStars; i++) {
					html += '<span class="dashicons dashicons-star-filled"></span>';
				}
				if (halfStar) {
					html += '<span class="dashicons dashicons-star-half"></span>';
				}
				for (var i = 0; i < emptyStars; i++) {
					html += '<span class="dashicons dashicons-star-empty"></span>';
				}
				html += '</div>';
				return html;
			}

			// Generate product image HTML
			function generateProductImage(product) {
				var imageSrc = product.image || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300"%3E%3Crect fill="%23e5e7eb" width="300" height="300"/%3E%3Ctext fill="%239ca3af" x="50%25" y="50%25" text-anchor="middle" dy=".3em" font-size="16"%3EProduct%20Image%3C/text%3E%3C/svg%3E';
				return '<img src="' + imageSrc + '" alt="' + product.title + '" style="max-width:100%;height:auto;display:block;" />';
			}

			// Preview template functionality
			$(document).on('click', '.shopglut-preview-btn', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $button = $(this);
				var templateName = $button.data('template-name');
				var templateHtml = $button.data('template-html');
				var templateCss = $button.data('template-css');

				console.log('Preview clicked:', templateName);

				// Show loader
				$('#shortcodeglut-preview-modal').show();
				$('#shortcodeglut-preview-content').html('<div class="shopglut-preview-loader"><span class="spinner is-active"></span></div>');
				$('body').css('overflow', 'hidden');

				// Simulate loading and render preview
				setTimeout(function() {
					// Replace template tags with sample product data
					var renderedHtml = replaceTemplateTags(templateHtml, sampleProduct);

					// Update modal content
					$('#shortcodeglut-preview-modal-title').text(templateName);
					$('#shortcodeglut-preview-content').html('<div class="shortcodeglut-preview-content-inner">' + renderedHtml + '</div>');
					$('#shortcodeglut-preview-styles').html('<style>' + templateCss + '</style>');
				}, 300);
			});

			// Close modal when clicking overlay (outside content)
			$(document).on('click', '#shortcodeglut-preview-modal-overlay', function() {
				$('#shortcodeglut-preview-modal').hide();
				$('body').css('overflow', '');
			});

			// Prevent closing when clicking inside modal container
			$(document).on('click', '#shortcodeglut-preview-modal-container', function(e) {
				e.stopPropagation();
			});

			// Close modal on close button (using event delegation)
			$(document).on('click', '#shortcodeglut-preview-modal-close', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$('#shortcodeglut-preview-modal').hide();
				$('body').css('overflow', '');
			});

			// Close modal on ESC key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && $('#shortcodeglut-preview-modal').is(':visible')) {
					$('#shortcodeglut-preview-modal').hide();
					$('body').css('overflow', '');
				}
			});

			// Duplicate template functionality
			$(document).on('click', '.shopglut-duplicate-btn', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var templateId = $(this).data('template-id');
				var $button = $(this);

				console.log('Duplicate clicked:', templateId);

				if (!confirm('Are you sure you want to duplicate this template?')) {
					return;
				}

				// Show loading state
				$button.text('Duplicating...').prop('disabled', true);

				// AJAX request to duplicate template
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'shortcodeglut_duplicate_template',
						template_id: templateId,
						nonce: '<?php echo wp_create_nonce('shortcodeglut_duplicate_template'); ?>'
					},
					success: function(response) {
						if (response.success) {
							window.location.reload();
						} else {
							var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to duplicate template.';
							alert(errorMsg);
							$button.html('<span class="dashicons dashicons-admin-page"></span><span>Duplicate</span>').prop('disabled', false);
						}
					},
					error: function(xhr, status, errorThrown) {
						alert('AJAX Error: ' + errorThrown + '. Please try again.');
						$button.html('<span class="dashicons dashicons-admin-page"></span><span>Duplicate</span>').prop('disabled', false);
					}
				});
			});

		})(jQuery);
		</script>

		<style>
		/* Default Badge */
		.scg-badge {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 12px;
			font-size: 0.75rem;
			font-weight: 500;
			margin-left: 8px;
		}
		.scg-badge--default {
			background: #d1fae5;
			color: #10b981;
			border: 1px solid #a7f3d0;
		}

		/* Template ID Wrapper with Copy Button */
		.shopglut-template-id-wrapper {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.shopglut-template-id-code {
			background: #f0f0f1;
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 13px;
			color: #1d2327;
		}

		.shopglut-copy-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-width: 28px;
			height: 28px;
			padding: 0 6px;
			background: #f6f7f7;
			border: 1px solid #dcdcde;
			border-radius: 4px;
			cursor: pointer;
			transition: all 0.1s ease;
		}

		.shopglut-copy-btn:hover {
			background: #e9e9eb;
			border-color: #50575e;
		}

		.shopglut-copy-btn .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			color: #50575e;
		}

		.shopglut-template-id-wrapper.shopglut-copied .shopglut-copy-btn {
			background: #d1fae5;
			border-color: #10b981;
		}

		.shopglut-template-id-wrapper.shopglut-copied .shopglut-copy-btn .dashicons {
			color: #10b981;
		}

		/* Template Actions Buttons */
		.shopglut-template-actions {
			display: flex;
			gap: 6px;
			flex-wrap: wrap;
		}

		.shopglut-action-btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			background: #fff;
			border: 1px solid #dcdcde;
			border-radius: 4px;
			font-size: 13px;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.1s ease;
			text-decoration: none;
		}

		/* Preview Button - Blue */
		.shopglut-preview-btn {
			background: #e7f3ff;
			border-color: #b8daff;
			color: #0066cc;
		}

		.shopglut-preview-btn:hover {
			background: #cce5ff;
			border-color: #0066cc;
			color: #0052a3;
		}

		.shopglut-preview-btn .dashicons {
			color: #0066cc;
		}

		/* Duplicate Button - Green */
		.shopglut-duplicate-btn {
			background: #d1fae5;
			border-color: #a7f3d0;
			color: #059669;
		}

		.shopglut-duplicate-btn:hover {
			background: #a7f3d0;
			border-color: #059669;
			color: #047857;
		}

		.shopglut-duplicate-btn .dashicons {
			color: #059669;
		}

		/* Preview Modal */
		#shortcodeglut-preview-modal {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			z-index: 100000;
		}

		#shortcodeglut-preview-modal-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.5);
		}

		#shortcodeglut-preview-modal-container {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			width: 90%;
			max-width: 900px;
			max-height: 85vh;
			background: #fff;
			border: 1px solid #8c8f94;
			border-radius: 8px;
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
			display: flex;
			flex-direction: column;
		}

		#shortcodeglut-preview-modal-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 16px 20px;
			border-bottom: 1px solid #8c8f94;
		}

		#shortcodeglut-preview-modal-title {
			font-size: 18px;
			font-weight: 600;
			margin: 0;
			color: #1d2327;
		}

		#shortcodeglut-preview-modal-close {
			background: none;
			border: none;
			cursor: pointer;
			padding: 4px;
			border-radius: 4px;
			transition: background 0.1s;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		#shortcodeglut-preview-modal-close:hover {
			background: #f0f0f1;
		}

		#shortcodeglut-preview-modal-close .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
			color: #1d2327;
		}

		#shortcodeglut-preview-modal-body {
			flex: 1;
			overflow: auto;
			padding: 24px;
			background: #f6f7f7;
		}

		#shortcodeglut-preview-content {
			background: #fff;
			border-radius: 8px;
			padding: 32px;
			border: 1px solid #8c8f94;
		}

		#shortcodeglut-preview-content-inner {
			/* Wrapper for template content */
		}

		/* Control image sizes in preview */
		#shortcodeglut-preview-content img {
			max-width: 100%;
			height: auto;
			display: block;
		}

		#shortcodeglut-preview-content-inner img {
			max-width: 300px;
			height: auto;
		}

		#shortcodeglut-preview-styles {
			display: none;
		}

		/* Preview Loader */
		.shopglut-preview-loader {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 60px 20px;
			background: #fff;
			border-radius: 8px;
			border: 1px solid #8c8f94;
		}

		.shopglut-preview-loader .spinner {
			float: none;
			margin: 0;
		}

		/* Star rating for preview */
		.shopglut-star-rating {
			color: #ffb400;
			font-size: 16px;
			display: flex;
			gap: 2px;
		}
		.shopglut-star-rating .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
		}
		</style>

		<!-- Preview Modal -->
		<div id="shortcodeglut-preview-modal">
			<div id="shortcodeglut-preview-modal-overlay"></div>
			<div id="shortcodeglut-preview-modal-container">
				<div id="shortcodeglut-preview-modal-header">
					<h2 id="shortcodeglut-preview-modal-title"><?php esc_html_e('Template Preview', 'shortcodeglut'); ?></h2>
					<button type="button" id="shortcodeglut-preview-modal-close">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div id="shortcodeglut-preview-modal-body">
					<div id="shortcodeglut-preview-content"></div>
				</div>
			</div>
		</div>
		<div id="shortcodeglut-preview-styles"></div>

		<?php
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
