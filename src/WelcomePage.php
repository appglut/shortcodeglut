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
		if ( isset( $_GET['page'] ) && 'shortcodeglut' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_style(
				'shortcodeglut-welcome-page',
				SHORTCODEGLUT_URL . 'src/welcome-page.css',
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
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
							<path d="M4.5 4.5C4.5 3.67 5.17 3 6 3H12C12.83 3 13.5 3.67 13.5 4.5V9H4.5V4.5Z" fill="#FF6B6B"/>
							<path d="M4.5 9H13.5V19.5C13.5 20.33 12.83 21 12 21H6C5.17 21 4.5 20.33 4.5 19.5V9Z" fill="#FF8E53"/>
							<path d="M13.5 4.5C13.5 3.67 14.17 3 15 3H18C18.83 3 19.5 3.67 19.5 4.5V9H13.5V4.5Z" fill="#4FACFE"/>
							<path d="M13.5 9H19.5V14.5C19.5 15.33 18.83 16 18 16H15C14.17 16 13.5 15.33 13.5 14.5V9Z" fill="#00F2FE"/>
							<path d="M6 11H7.5V17H6V11Z" fill="white"/>
							<path d="M10.5 11H12V17H10.5V11Z" fill="white"/>
						</svg>
					</div>
					<h1><?php esc_html_e( 'Welcome to ShortcodeGlut', 'shortcodeglut' ); ?></h1>
					<p class="scg-welcome-subtitle"><?php esc_html_e( 'Powerful WooCommerce Product Shortcodes', 'shortcodeglut' ); ?></p>
				</div>

				<div class="scg-welcome-content">
					<div class="scg-welcome-thank-you">
						<h2><?php esc_html_e( 'Thank you for installing ShortcodeGlut!', 'shortcodeglut' ); ?></h2>
						<p><?php esc_html_e( 'You\'re just a few steps away from creating beautiful product displays with our powerful shortcodes.', 'shortcodeglut' ); ?></p>
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
							<li><?php esc_html_e( 'Example: <code>[shopglut_sale_products limit="8" columns="4"]</code>', 'shortcodeglut' ); ?></li>
						</ol>
					</div>

					<div class="scg-welcome-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=shortcodeglut&view=shortcode_showcase' ) ); ?>" class="scg-welcome-btn scg-welcome-btn--primary">
							<span class="dashicons dashicons-arrow-right-alt"></span>
							<?php esc_html_e( 'Get Started', 'shortcodeglut' ); ?>
						</a>
						<a href="https://documentation.appglut.com/?utm_source=shortcodeglut-welcome&utm_medium=referral&utm_campaign=welcome" target="_blank" class="scg-welcome-btn scg-welcome-btn--secondary">
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
