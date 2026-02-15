<?php
namespace Shortcodeglut\tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ShortcodeglutTools {

	public $not_implemented;

	public function __construct() {

		add_filter( 'admin_body_class', array( $this, 'shortcodeglutBodyClass' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueWooTemplatesAssets' ) );

        $this->not_implemented = true;

	}

	/**
	 * Enqueue assets for Woo Templates admin page
	 */
	public function enqueueWooTemplatesAssets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for script loading
		if ( isset( $_GET['page'] ) && 'shortcodeglut' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for view type verification
			$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

			if ( 'woo_templates' === $view ) {
				// Enqueue styles
				wp_enqueue_style(
					'shortcodeglut-woo-templates-admin',
					SHORTCODEGLUT_URL . 'src/tools/woo-templates-admin.css',
					array(),
					SHORTCODEGLUT_VERSION
				);

				// Enqueue scripts
				wp_enqueue_script(
					'shortcodeglut-woo-templates-admin',
					SHORTCODEGLUT_URL . 'src/tools/woo-templates-admin.js',
					array( 'jquery' ),
					SHORTCODEGLUT_VERSION,
					true
				);

				// Localize script
				wp_localize_script( 'shortcodeglut-woo-templates-admin', 'shortcodeglutWooTemplates', array(
					'nonce' => wp_create_nonce( 'shortcodeglut_duplicate_template' ),
					'demoImage' => esc_url( SHORTCODEGLUT_URL . 'global-assets/images/demo-image.png' ),
					'i18n' => array(
						'confirmDuplicate' => esc_html__( 'Are you sure you want to duplicate this template?', 'shortcodeglut' ),
						'duplicating' => esc_html__( 'Duplicating...', 'shortcodeglut' ),
						'duplicate' => esc_html__( 'Duplicate', 'shortcodeglut' ),
						'duplicateFailed' => esc_html__( 'Failed to duplicate template.', 'shortcodeglut' ),
						'ajaxError' => esc_html__( 'AJAX Error:', 'shortcodeglut' ),
						'tryAgain' => esc_html__( 'Please try again.', 'shortcodeglut' ),
					)
				) );
			}
		}
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
							// Check user permissions before accessing template editor
							if ( ! current_user_can( 'manage_options' ) ) {
								wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
							}
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

		// Only return active menu for shortcodeglut pages
		if ( strpos( $page, 'shortcodeglut' ) !== false ) {
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
