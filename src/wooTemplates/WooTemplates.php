<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

class WooTemplates {
	private static $instance = null;
	private $menu_slug = 'shortcodeglut';
	private static $settings_page = null;

	public function __construct() {
		// Body class removed - no longer needed for view-only templates
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
	 * Render the templates list page (editor removed for WordPress.org compliance)
	 */
	public function renderTemplatesPage() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}

		// SettingsPage should be initialized in constructor
		$woo_templates_settings = self::$settings_page;

		// Fallback - create if not exists (shouldn't happen after get_instance)
		if ( ! $woo_templates_settings ) {
			$woo_templates_settings = new SettingsPage();
			self::$settings_page = $woo_templates_settings;
		}

		// Render templates list only - editor removed
		$woo_templates_settings->templatesListPage();
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
								href="https://shortcodeglut.appglut.com/?utm_source=shoglutplugin-admin&utm_medium=referral&utm_campaign=adminmenu"
								target="_blank">
								<span class="dashicons dashicons-admin-page"></span>
								<?php echo esc_html__( 'Documentation', 'shortcodeglut' ); ?>
							</a></span>
						<span><a class="shortcodeglut-active" rel="noopener"
								href="https://www.appglut.com/plugin/shortcodeglut/?utm_source=shoglutplugin-admin&utm_medium=referral&utm_campaign=upgrade"
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
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut&view=shortcode_showcase' ) ); ?>" class="shortcodeglut-nav-tab nav-tab">
								💻 <?php echo esc_html__( 'Shortcode Showcase', 'shortcodeglut' ); ?>
							</a>
							<?php
							// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for CSS class only
							$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
							$is_woo_templates_active = ( 'woo_templates' === $current_view || 'woo_template_editor' === $current_view );
							?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut&view=woo_templates' ) ); ?>" class="shortcodeglut-nav-tab nav-tab<?php echo $is_woo_templates_active ? ' shortcodeglut-nav-active' : ''; ?>">
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