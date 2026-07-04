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
					SHORTCODEGLUT_URL . 'global-assets/css/woo-templates-admin.css',
					array(),
					SHORTCODEGLUT_VERSION
				);

				// Note: woo-templates-admin.js is no longer loaded for templates list page
				// The new woo-templates-list.js handles all the functionality including preview modal
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class addition only
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

				// Editor routes removed for WordPress.org compliance - redirect to templates list
				if ( ! empty( $editor )) {
					// Redirect to templates list page
					wp_safe_redirect( admin_url( 'admin.php?page=shortcodeglut&view=woo_templates' ) );
					exit;
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
						case 'woo_template_editor':
							$this->renderTemplateEditor();
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
								href="https://www.documentation.appglut.com/shortcodeglut/?utm_source=shortcodeglut-plugin-admin&utm_medium=referral&utm_campaign=adminmenu"
								target="_blank">
								<span class="dashicons dashicons-admin-page"></span>
								<?php echo esc_html__( 'Documentation', 'shortcodeglut' ); ?>
							</a></span>
						<span><a class="shortcodeglut-active" rel="noopener"
								href="<?php echo esc_url( SHORTCODEGLUT_PRO_URL ); ?>"
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
			$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

			// For woo_template_editor, return woo_templates to highlight the Woo Templates menu
			if ( $view === 'woo_template_editor' ) {
				return 'woo_templates';
			}

			return $view ?: $this->defaultHeaderMenu();
		}

		return false;
  }

	public function renderShortcodeShowcase() {
		$active_menu = $this->activeMenuTab();
		$this->settingsPageHeader( $active_menu );

		// Enqueue shortcode showcase assets directly
		wp_enqueue_style(
			'shortcodeglut-shortcode-showcase',
			SHORTCODEGLUT_URL . 'global-assets/css/shortcode-showcase.css',
			array(),
			SHORTCODEGLUT_VERSION
		);
		wp_enqueue_script(
			'shortcodeglut-shortcode-showcase',
			SHORTCODEGLUT_URL . 'global-assets/js/shortcode-showcase.js',
			array( 'jquery' ),
			SHORTCODEGLUT_VERSION,
			true
		);
		wp_enqueue_script(
			'shortcodeglut-shortcode-showcase-admin',
			SHORTCODEGLUT_URL . 'global-assets/js/shortcode-showcase-admin.js',
			array( 'jquery', 'shortcodeglut-shortcode-showcase' ),
			SHORTCODEGLUT_VERSION,
			true
		);
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

		// Include the woo templates system
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplates.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesListTable.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesEntity.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/SettingsPage.php';

		// Initialize WooTemplates and render templates list
		$woo_templates = \Shortcodeglut\wooTemplates\WooTemplates::get_instance();
		$woo_templates->renderTemplatesPage();
		?>
		<?php
	}

	public function renderTemplateEditor() {
		$active_menu = $this->activeMenuTab();
		$this->settingsPageHeader( $active_menu );
		?>
		<?php
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}

		// Include the woo templates system
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplates.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesListTable.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/WooTemplatesEntity.php';
		require_once SHORTCODEGLUT_PATH . 'src/wooTemplates/SettingsPage.php';

		// Initialize SettingsPage and render template editor
		$settings_page = \Shortcodeglut\wooTemplates\SettingsPage::get_instance();
		$settings_page->templateEditorPage();
		?>
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
