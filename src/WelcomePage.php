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

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_welcome_page' ) );
		add_action( 'admin_head', array( $this, 'remove_welcome_menu' ) );
	}

	/**
	 * Add welcome page (hidden from menu)
	 */
	public function add_welcome_page() {
		add_submenu_page(
			null, // No parent menu - hidden
			esc_html__( 'Welcome to ShortcodeGlut', 'shortcodeglut' ),
			esc_html__( 'Welcome', 'shortcodeglut' ),
			'manage_options',
			'shortcodeglut-welcome',
			array( $this, 'render_welcome_page' )
		);
	}

	/**
	 * Remove welcome page from admin menu
	 */
	public function remove_welcome_menu() {
		remove_submenu_page( 'shortcodeglut', 'shortcodeglut-welcome' );
	}

	/**
	 * Render the welcome page
	 */
	public function render_welcome_page() {
		// Check if user can access
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}

		// Dismiss welcome URL
		$dismiss_url = admin_url( 'admin.php?page=shortcodeglut&view=shortcode_showcase' );
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php esc_html_e( 'Welcome to ShortcodeGlut', 'shortcodeglut' ); ?></title>
			<?php
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_script( 'jquery' );
			?>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}

				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 20px;
				}

				.scg-welcome-container {
					max-width: 900px;
					width: 100%;
					background: #ffffff;
					border-radius: 20px;
					box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
					overflow: hidden;
				}

				.scg-welcome-header {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					padding: 60px 40px;
					text-align: center;
					color: #ffffff;
				}

				.scg-welcome-logo {
					width: 120px;
					height: 120px;
					background: rgba(255, 255, 255, 0.2);
					border-radius: 30px;
					margin: 0 auto 30px;
					display: flex;
					align-items: center;
					justify-content: center;
					backdrop-filter: blur(10px);
				}

				.scg-welcome-logo svg {
					width: 80px;
					height: 80px;
					fill: #ffffff;
				}

				.scg-welcome-title {
					font-size: 42px;
					font-weight: 700;
					margin-bottom: 15px;
					letter-spacing: -1px;
				}

				.scg-welcome-subtitle {
					font-size: 20px;
					opacity: 0.95;
					font-weight: 400;
				}

				.scg-welcome-content {
					padding: 50px 40px;
				}

				.scg-welcome-thank-you {
					text-align: center;
					margin-bottom: 50px;
				}

				.scg-welcome-thank-you h2 {
					font-size: 28px;
					color: #1d2327;
					margin-bottom: 15px;
					font-weight: 600;
				}

				.scg-welcome-thank-you p {
					font-size: 16px;
					color: #475569;
					line-height: 1.7;
					max-width: 600px;
					margin: 0 auto;
				}

				.scg-welcome-features {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 30px;
					margin-bottom: 50px;
				}

				.scg-welcome-feature {
					text-align: center;
					padding: 30px 20px;
					background: #f8fafc;
					border-radius: 16px;
					transition: transform 0.3s ease, box-shadow 0.3s ease;
				}

				.scg-welcome-feature:hover {
					transform: translateY(-5px);
					box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
				}

				.scg-welcome-feature-icon {
					width: 60px;
					height: 60px;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					border-radius: 16px;
					margin: 0 auto 20px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.scg-welcome-feature-icon .dashicons {
					font-size: 32px;
					width: 32px;
					height: 32px;
					color: #ffffff;
				}

				.scg-welcome-feature h3 {
					font-size: 18px;
					color: #1d2327;
					margin-bottom: 10px;
					font-weight: 600;
				}

				.scg-welcome-feature p {
					font-size: 14px;
					color: #64748b;
					line-height: 1.6;
				}

				.scg-welcome-quick-start {
					background: #fef3c7;
					border: 2px solid #fbbf24;
					border-radius: 16px;
					padding: 30px;
					margin-bottom: 40px;
				}

				.scg-welcome-quick-start h3 {
					font-size: 20px;
					color: #92400e;
					margin-bottom: 20px;
					display: flex;
					align-items: center;
					gap: 10px;
				}

				.scg-welcome-quick-start h3 .dashicons {
					font-size: 24px;
				}

				.scg-welcome-quick-start ol {
					margin-left: 20px;
				}

				.scg-welcome-quick-start li {
					font-size: 15px;
					color: #78350f;
					margin-bottom: 12px;
					line-height: 1.6;
				}

				.scg-welcome-quick-start code {
					background: #fffbeb;
					padding: 3px 8px;
					border-radius: 4px;
					font-family: ui-monospace, SFMono-Regular, monospace;
					font-size: 13px;
					color: #b45309;
					border: 1px solid #fcd34d;
				}

				.scg-welcome-actions {
					display: flex;
					gap: 20px;
					justify-content: center;
				}

				.scg-welcome-btn {
					display: inline-flex;
					align-items: center;
					gap: 10px;
					padding: 16px 32px;
					font-size: 16px;
					font-weight: 600;
					border-radius: 12px;
					text-decoration: none;
					transition: all 0.3s ease;
					border: none;
					cursor: pointer;
				}

				.scg-welcome-btn--primary {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					color: #ffffff;
					box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
				}

				.scg-welcome-btn--primary:hover {
					transform: translateY(-2px);
					box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
				}

				.scg-welcome-btn--secondary {
					background: #f1f5f9;
					color: #475569;
					border: 2px solid #e2e8f0;
				}

				.scg-welcome-btn--secondary:hover {
					background: #e2e8f0;
					border-color: #cbd5e1;
				}

				@media (max-width: 768px) {
					.scg-welcome-features {
						grid-template-columns: 1fr;
					}

					.scg-welcome-header {
						padding: 40px 20px;
					}

					.scg-welcome-content {
						padding: 30px 20px;
					}

					.scg-welcome-title {
						font-size: 32px;
					}

					.scg-welcome-actions {
						flex-direction: column;
					}

					.scg-welcome-btn {
						width: 100%;
						justify-content: center;
					}
				}
			</style>
		</head>
		<body>
			<div class="scg-welcome-container">
				<div class="scg-welcome-header">
					<div class="scg-welcome-logo">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 97.96 97.96"><defs><linearGradient id="a" x1="49.31" x2="48.25" y1="52.13" y2="51.18" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#ffb900"/><stop offset=".42" stop-color="#f70"/><stop offset=".74" stop-color="#d900b5"/><stop offset="1" stop-color="#d900b5"/></linearGradient><linearGradient id="b" x1="49.31" x2="48.25" y1="52.13" y2="51.18" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#ffb900"/><stop offset=".42" stop-color="#f70"/><stop offset=".74" stop-color="#d900b5"/><stop offset="1" stop-color="#d900b5"/></linearGradient></defs><path fill="url(#a)" d="M9.1 13.58a8.85 8.85 0 0 1 4.69-7.87 9.27 9.27 0 0 1 8.76.31l24.31 14.8a9.28 9.28 0 0 0 9.53 0l24.31-14.8a9.27 9.27 0 0 1 8.76-.31 8.85 8.85 0 0 1 4.69 7.87v51.36a8.85 8.85 0 0 1-4.69 7.87 9.27 9.27 0 0 1-8.76-.31L56.21 57.7a9.28 9.28 0 0 0-9.53 0L22.37 72.5a9.27 9.27 0 0 1-8.76.31 8.85 8.85 0 0 1-4.69-7.87Z"/><path fill="#fff" d="M46.48 67.54 22.37 52.83a2.34 2.34 0 0 1-1.17-2V26.59a2.17 2.17 0 0 1 .16-.81 2.32 2.32 0 0 1 1.57-1.42 2.44 2.44 0 0 1 1.6.09l24.11 14.69a2.33 2.33 0 0 1 1.12 2v24.25a2.33 2.33 0 0 1-1.12 2 2.44 2.44 0 0 1-1.16.15Zm-23.23-17 23.23 14.15V43.24L23.25 29.09Z"/></svg>
					</div>
					<h1 class="scg-welcome-title"><?php esc_html_e( 'Welcome to ShortcodeGlut', 'shortcodeglut' ); ?></h1>
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
		</body>
		</html>
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
