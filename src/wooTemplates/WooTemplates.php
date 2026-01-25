<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

class WooTemplates {
	private static $instance = null;
	private $menu_slug = 'shortcodeglut_tools';
	private static $settings_page = null;

	public function __construct() {
		// Add body class for the template editor page
		add_filter( 'admin_body_class', array( $this, 'wooTemplatesBodyClass' ) );
	}

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add body class for the template editor
	 */
	public function wooTemplatesBodyClass( $classes ) {
		$current_screen = get_current_screen();

		if ( empty( $current_screen ) ) {
			return $classes;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class assignment, no form processing
		if ( isset( $_GET['page'] ) && $this->menu_slug === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['editor'] ) && 'woo_template' === sanitize_text_field( wp_unslash( $_GET['editor'] ) ) && isset( $_GET['template_id'] ) ) {
			$classes .= ' shortcodeglut-woo-template-editor';
		}

		return $classes;
	}

	/**
	 * Render the main templates page or editor based on URL parameters
	 */
	public function renderTemplatesPage() {
		// SettingsPage should be initialized in constructor
		$woo_templates_settings = self::$settings_page;

		// Fallback - create if not exists (shouldn't happen after get_instance)
		if ( ! $woo_templates_settings ) {
			$woo_templates_settings = new SettingsPage();
			self::$settings_page = $woo_templates_settings;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for page routing, no form processing
		if ( isset( $_GET['page'] ) && $this->menu_slug === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['editor'] ) && 'woo_template' === sanitize_text_field( wp_unslash( $_GET['editor'] ) ) && isset( $_GET['template_id'] ) ) {
			// Render template editor
			$woo_templates_settings->templateEditorPage();
		} else {
			// Render templates list
			//$this->templatesListPage();
		}
	}

	/**
	 * Display the page header
	 */
	public function pageHeader( $active_menu ) {
		$logo_url = SHORTCODEGLUT_URL . 'global-assets/images/header-logo.svg';
		?>
		<div class="shortcodeglut-page-header">
			<div class="shortcodeglut-page-header-wrap">
				<div class="shortcodeglut-page-header-banner shortcodeglut-pro shortcodeglut-no-submenu">
					<div class="shortcodeglut-page-header-banner__logo">
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="">
					</div>
					<div class="shortcodeglut-page-header-banner__helplinks">
						<span><a rel="noopener"
								href="https://shopglut.appglut.com/?utm_source=shoglutplugin-admin&utm_medium=referral&utm_campaign=adminmenu"
								target="_blank">
								<span class="dashicons dashicons-admin-page"></span>
								<?php echo esc_html__( 'Documentation', 'shortcodeglut' ); ?>
							</a></span>
						<span><a class="shortcodeglut-active" rel="noopener"
								href="https://www.appglut.com/plugin/shopglut/?utm_source=shoglutplugin-admin&utm_medium=referral&utm_campaign=upgrade"
								target="_blank">
								<span class="dashicons dashicons-unlock"></span>
								<?php echo esc_html__( 'Unlock Pro Edition', 'shortcodeglut' ); ?>
							</a></span>
						<span><a rel="noopener"
								href="https://www.appglut.com/support/?utm_source=shoglutplugin-admin&utm_medium=referral&utm_campaign=support"
								target="_blank">
								<span class="dashicons dashicons-share-alt"></span>
								<?php echo esc_html__( 'Support', 'shortcodeglut' ); ?>
							</a></span>
					</div>
					<div class="clear"></div>
					<div class="shortcodeglut-header-menus">
						<nav class="shortcodeglut-nav-tab-wrapper nav-tab-wrapper">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut_tools' ) ); ?>" class="shortcodeglut-nav-tab nav-tab">
								🔧 <?php echo esc_html__( 'All Tools', 'shortcodeglut' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut_tools&view=shortcode_showcase' ) ); ?>" class="shortcodeglut-nav-tab nav-tab">
								💻 <?php echo esc_html__( 'Shortcode Showcase', 'shortcodeglut' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut_tools&view=woo_templates' ) ); ?>" class="shortcodeglut-nav-tab nav-tab shortcodeglut-nav-active">
								📋 <?php echo esc_html__( 'Woo Templates', 'shortcodeglut' ); ?>
							</a>
						</nav>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}