<?php
namespace Shortcodeglut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShortcodeglutBase {

	// Declare properties to fix PHP 8.2+ deprecation warnings
	public $menu_slug;

	public function __construct() {

		// Initialize core components
		ShortcodeglutDatabase::shortcodeglut_initialize();
		ShortcodeglutRegisterScripts::get_instance();
		ShortcodeglutRegisterMenu::get_instance();

		// Add actions
		add_action( 'init', array( $this, 'shortcodeglutInitialFunctions' ), 9 );
		add_filter( 'update_footer', array( $this, 'shortcodeglut_admin_footer_version' ), 999 );

		// Add commercial plugin notice
		add_action( 'admin_notices', array( $this, 'shortcodeglut_commercial_plugin_notice' ) );

		// AJAX handler for dismissing commercial notice
		add_action( 'wp_ajax_shortcodeglut_dismiss_commercial_notice', array( $this, 'ajax_dismiss_commercial_notice' ) );
	}

	public function shortcodeglutInitialFunctions() {
		// Load shortcodeShowcase with universal loader
		require_once SHORTCODEGLUT_PATH . 'src/shortcodeShowcase/loader.php';
	}

	public function shortcodeglut_admin_footer_version() {
		return '<span id="shortcodeglut-footer-version" style="display: none;">ShortcodeGlut ' . SHORTCODEGLUT_VERSION . '</span>';
	}

	/**
	 * Display commercial plugin notice on ShortcodeGlut admin pages
	 */
	public function shortcodeglut_commercial_plugin_notice() {
		// Only show on ShortcodeGlut admin pages
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'shortcodeglut' ) === false ) {
			return;
		}

		// Check if user has already dismissed this notice
		if ( get_user_meta( get_current_user_id(), 'shortcodeglut_commercial_notice_dismissed', true ) ) {
			return;
		}
		?>
		<div class="notice shortcodeglut-commercial-notice" style="border-left-color: #667eea; padding: 15px 20px; position: relative;">
			<div class="shortcodeglut-commercial-notice-content" style="display: flex; align-items: start; gap: 15px;">
				<div class="shortcodeglut-commercial-icon" style="flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
					<span class="dashicons dashicons-store" style="font-size: 20px; width: 20px; height: 20px; color: #ffffff;"></span>
				</div>
				<div class="shortcodeglut-commercial-text" style="flex: 1;">
					<h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1d2327;">
						<?php esc_html_e( 'Commercial Plugin - Premium Features Available', 'shortcodeglut' ); ?>
					</h3>
					<p style="margin: 0 0 10px 0; font-size: 14px; color: #475569; line-height: 1.5;">
						<?php esc_html_e( 'This plugin is free to use, but premium upgrades and commercial support are available for advanced features and priority assistance.', 'shortcodeglut' ); ?>
					</p>
					<div class="shortcodeglut-commercial-links" style="display: flex; gap: 10px; flex-wrap: wrap;">
						<a href="<?php echo esc_url( SHORTCODEGLUT_PRO_URL ); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500; transition: all 0.2s ease;">
							<span class="dashicons dashicons-arrow-up-alt2" style="font-size: 14px; width: 14px; height: 14px;"></span>
							<?php esc_html_e( 'Upgrade to PRO', 'shortcodeglut' ); ?>
						</a>
						<a href="<?php echo esc_url( SHORTCODEGLUT_SUPPORT_URL ); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #f1f5f9; color: #475569; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid #e2e8f0; transition: all 0.2s ease;">
							<span class="dashicons dashicons-sos" style="font-size: 14px; width: 14px; height: 14px;"></span>
							<?php esc_html_e( 'Get Support', 'shortcodeglut' ); ?>
						</a>
					</div>
				</div>
				<button type="button" class="notice-dismiss shortcodeglut-commercial-dismiss" style="text-decoration: none; position: absolute; top: 10px; right: 10px;" data-nonce="<?php echo esc_attr( wp_create_nonce( 'shortcodeglut_dismiss_commercial_notice' ) ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'shortcodeglut' ); ?></span>
				</button>
			</div>
		</div>
		<style>
			.shortcodeglut-commercial-notice:hover {
				box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
			}
			.shortcodeglut-commercial-notice a:hover {
				transform: translateY(-1px);
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
			}
		</style>
		<script>
		jQuery(document).ready(function($) {
			$('.shortcodeglut-commercial-dismiss').on('click', function(e) {
				e.preventDefault();
				var $notice = $(this).closest('.shortcodeglut-commercial-notice');
				var nonce = $(this).data('nonce');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'shortcodeglut_dismiss_commercial_notice',
						nonce: nonce
					},
					success: function() {
						$notice.fadeOut(300, function() {
							$(this).remove();
						});
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler for dismissing commercial plugin notice
	 */
	public function ajax_dismiss_commercial_notice() {
		// Verify nonce
		check_ajax_referer( 'shortcodeglut_dismiss_commercial_notice', 'nonce', true );

		// Get current user ID
		$user_id = get_current_user_id();

		if ( $user_id ) {
			// Mark notice as dismissed for this user
			update_user_meta( $user_id, 'shortcodeglut_commercial_notice_dismissed', current_time( 'mysql' ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => 'User not authenticated' ) );
		}
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
