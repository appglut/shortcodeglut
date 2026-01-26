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
	 * Render the welcome page content
	 */
	public function render_welcome_content() {
		// Check if user can access
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shortcodeglut' ) );
		}
		?>
		<div class="wrap scg-wrap-full shortcodeglut-welcome-page">
			<style>
				/* Hide default WP title and add custom header */
				.shortcodeglut-welcome-page > h1 {
					display: none;
				}

				.scg-welcome-wrapper {
					max-width: 1000px;
					margin: 20px auto;
					background: #ffffff;
					border-radius: 12px;
					box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
					overflow: hidden;
				}

				.scg-welcome-header {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					padding: 50px 40px;
					text-align: center;
					color: #ffffff;
				}

				.scg-welcome-logo {
					width: 100px;
					height: 100px;
					background: rgba(255, 255, 255, 0.2);
					border-radius: 24px;
					margin: 0 auto 20px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.scg-welcome-logo svg {
					width: 70px;
					height: 70px;
				}

				.scg-welcome-header h1 {
					font-size: 36px;
					font-weight: 700;
					margin: 0 0 10px 0;
					letter-spacing: -0.5px;
					color: #ffffff;
					display: block !important;
				}

				.scg-welcome-subtitle {
					font-size: 18px;
					opacity: 0.95;
					font-weight: 400;
				}

				.scg-welcome-content {
					padding: 40px;
				}

				.scg-welcome-thank-you {
					text-align: center;
					margin-bottom: 40px;
				}

				.scg-welcome-thank-you h2 {
					font-size: 24px;
					color: #1d2327;
					margin: 0 0 12px 0;
					font-weight: 600;
				}

				.scg-welcome-thank-you p {
					font-size: 15px;
					color: #475569;
					line-height: 1.6;
					max-width: 550px;
					margin: 0 auto;
				}

				.scg-welcome-features {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 24px;
					margin-bottom: 40px;
				}

				.scg-welcome-feature {
					text-align: center;
					padding: 24px 16px;
					background: #f8fafc;
					border-radius: 12px;
					transition: all 0.2s ease;
					border: 1px solid #e2e8f0;
				}

				.scg-welcome-feature:hover {
					transform: translateY(-3px);
					box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
					border-color: #cbd5e1;
				}

				.scg-welcome-feature-icon {
					width: 50px;
					height: 50px;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					border-radius: 12px;
					margin: 0 auto 16px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.scg-welcome-feature-icon .dashicons {
					font-size: 28px;
					width: 28px;
					height: 28px;
					color: #ffffff;
				}

				.scg-welcome-feature h3 {
					font-size: 16px;
					color: #1d2327;
					margin: 0 0 8px 0;
					font-weight: 600;
				}

				.scg-welcome-feature p {
					font-size: 13px;
					color: #64748b;
					line-height: 1.5;
					margin: 0;
				}

				.scg-welcome-quick-start {
					background: #fef3c7;
					border: 1px solid #fcd34d;
					border-radius: 12px;
					padding: 24px;
					margin-bottom: 30px;
				}

				.scg-welcome-quick-start h3 {
					font-size: 18px;
					color: #92400e;
					margin: 0 0 16px 0;
					display: flex;
					align-items: center;
					gap: 8px;
				}

				.scg-welcome-quick-start h3 .dashicons {
					font-size: 22px;
				}

				.scg-welcome-quick-start ol {
					margin: 0 0 0 20px;
					padding: 0;
				}

				.scg-welcome-quick-start li {
					font-size: 14px;
					color: #78350f;
					margin-bottom: 10px;
					line-height: 1.5;
				}

				.scg-welcome-quick-start li:last-child {
					margin-bottom: 0;
				}

				.scg-welcome-quick-start code {
					background: #fffbeb;
					padding: 2px 6px;
					border-radius: 4px;
					font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
					font-size: 12px;
					color: #b45309;
					border: 1px solid #fde68a;
				}

				.scg-welcome-actions {
					display: flex;
					gap: 16px;
					justify-content: center;
				}

				.scg-welcome-btn {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					padding: 12px 24px;
					font-size: 14px;
					font-weight: 600;
					border-radius: 8px;
					text-decoration: none;
					transition: all 0.2s ease;
				}

				.scg-welcome-btn--primary {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					color: #ffffff;
					box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
				}

				.scg-welcome-btn--primary:hover {
					transform: translateY(-1px);
					box-shadow: 0 4px 12px rgba(102, 126, 234, 0.35);
				}

				.scg-welcome-btn--secondary {
					background: #f1f5f9;
					color: #475569;
					border: 1px solid #e2e8f0;
				}

				.scg-welcome-btn--secondary:hover {
					background: #e2e8f0;
					border-color: #cbd5e1;
				}

				.scg-welcome-btn .dashicons {
					font-size: 16px;
					width: 16px;
					height: 16px;
				}

				@media (max-width: 1200px) {
					.scg-welcome-wrapper {
						margin: 0;
						border-radius: 0;
					}
				}

				@media (max-width: 782px) {
					.scg-welcome-features {
						grid-template-columns: 1fr;
					}

					.scg-welcome-header {
						padding: 40px 24px;
					}

					.scg-welcome-content {
						padding: 24px;
					}

					.scg-welcome-header h1 {
						font-size: 28px;
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
