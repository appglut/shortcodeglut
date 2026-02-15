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
	}

	public function shortcodeglutInitialFunctions() {
		// Load shortcodeShowcase with universal loader
		require_once SHORTCODEGLUT_PATH . 'src/shortcodeShowcase/loader.php';
	}

	public function shortcodeglut_admin_footer_version() {
		return '<span id="shortcodeglut-footer-version" style="display: none;">ShortcodeGlut ' . SHORTCODEGLUT_VERSION . '</span>';
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
