<?php
/**
 * Welcome Page for ShortcodeGlut
 *
 * @package Shortcodeglut
 */

namespace Shortcodeglut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WelcomePage {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueWelcomeStyles' ) );
	}

	/**
	 * Enqueue styles for welcome page
	 */
	public function enqueueWelcomeStyles( $hook ) {
		// Only load on our welcome page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for script loading
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'shortcodeglut' === $page || 'shortcodeglut-welcome' === $page ) {
			wp_enqueue_style(
				'shortcodeglut-welcome-page',
				SHORTCODEGLUT_URL . 'global-assets/css/welcome-page.css',
				array(),
				SHORTCODEGLUT_VERSION
			);
		}
	}

	/**
	 * Render the welcome page content
	 */
	public function render_welcome_content() {
		// Check if user can access
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}
		?>
		<div class="wrap scg-wrap-full shortcodeglut-welcome-page">
			<div class="scg-welcome-wrapper">
				<div class="scg-welcome-header">
					<div class="scg-welcome-logo">
						<img src="<?php echo esc_url( SHORTCODEGLUT_URL . 'global-assets/images/menu_icon.svg' ); ?>" alt="<?php esc_attr_e( 'ShortcodeGlut Logo', 'shortcodeglut' ); ?>" width="80" height="80">
					</div>
					<h1><?php esc_html_e( 'Welcome to ShortcodeGlut', 'shortcodeglut' ); ?></h1>
					<p class="scg-welcome-subtitle"><?php esc_html_e( 'Powerful WooCommerce Product Shortcodes', 'shortcodeglut' ); ?></p>
				</div>

				<div class="scg-welcome-content">
					<div class="scg-welcome-thank-you">
						<h2><?php esc_html_e( 'Thank you for installing ShortcodeGlut!', 'shortcodeglut' ); ?></h2>
						<p><?php esc_html_e( 'You\'re just a few steps away from creating beautiful product displays with our powerful shortcodes.', 'shortcodeglut' ); ?></p>
					</div>

					<div class="scg-welcome-video">
						<h3>
							<span class="dashicons dashicons-video-alt3"></span>
							<?php esc_html_e( 'Watch How to Use ShortcodeGlut', 'shortcodeglut' ); ?>
						</h3>
						<div class="scg-video-wrapper">
							<iframe width="560" height="315" src="https://www.youtube.com/embed/cb8yLcT51J4" title="<?php esc_attr_e( 'How to Use ShortcodeGlut - Video Tutorial', 'shortcodeglut' ); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
						</div>
						<p class="scg-video-description"><?php esc_html_e( 'Learn how to use ShortcodeGlut shortcodes to create stunning product displays on your WooCommerce store.', 'shortcodeglut' ); ?></p>
					</div>

					<div class="scg-welcome-features">
						<div class="scg-welcome-feature">
							<div class="scg-welcome-feature-icon">
								<span class="dashicons dashicons-grid-view"></span>
							</div>
							<h3><?php esc_html_e( 'Product Shortcodes', 'shortcodeglut' ); ?></h3>
							<p><?php esc_html_e( 'Display sale products, categories, and more with beautiful layouts.', 'shortcodeglut' ); ?></p>
						</div>

						<div class="scg-welcome-feature">
							<div class="scg-welcome-feature-icon">
								<span class="dashicons dashicons-superhero"></span>
							</div>
							<h3><?php esc_html_e( 'Custom Templates', 'shortcodeglut' ); ?></h3>
							<p><?php esc_html_e( 'Create and customize product display templates to match your brand.', 'shortcodeglut' ); ?></p>
						</div>

						<div class="scg-welcome-feature">
							<div class="scg-welcome-feature-icon">
								<span class="dashicons dashicons-universal-access"></span>
							</div>
							<h3><?php esc_html_e( 'Easy to Use', 'shortcodeglut' ); ?></h3>
							<p><?php esc_html_e( 'Simple shortcodes that work anywhere on your WordPress site.', 'shortcodeglut' ); ?></p>
						</div>
					</div>

					<div class="scg-welcome-quick-start">
						<h3>
							<span class="dashicons dashicons-superhero-alt"></span>
							<?php esc_html_e( 'Quick Start Guide', 'shortcodeglut' ); ?>
						</h3>
						<ol>
							<li><?php esc_html_e( 'Navigate to the Shortcode Showcase page to see available shortcodes', 'shortcodeglut' ); ?></li>
							<li><?php esc_html_e( 'Copy any shortcode and paste it into any page, post, or widget area', 'shortcodeglut' ); ?></li>
							<li><?php esc_html_e( 'Use the Woo Templates page to create custom product display templates', 'shortcodeglut' ); ?></li>
							<li><?php esc_html_e( 'Customize shortcode attributes to control columns, rows, filtering, and more', 'shortcodeglut' ); ?></li>
							<li><?php esc_html_e( 'Example: <code>[shortcodeglut_sale_products limit="8" columns="4"]</code>', 'shortcodeglut' ); ?></li>
						</ol>
					</div>

					<div class="scg-welcome-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut&view=shortcode_showcase' ) ); ?>" class="scg-welcome-btn scg-welcome-btn--primary">
							<span class="dashicons dashicons-arrow-right-alt"></span>
							<?php esc_html_e( 'Get Started', 'shortcodeglut' ); ?>
						</a>
						<a href="https://www.documentation.appglut.com/shortcodeglut/?utm_source=shortcodeglut-welcome&utm_medium=referral&utm_campaign=welcome" target="_blank" class="scg-welcome-btn scg-welcome-btn--secondary">
							<span class="dashicons dashicons-book"></span>
							<?php esc_html_e( 'View Documentation', 'shortcodeglut' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
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
