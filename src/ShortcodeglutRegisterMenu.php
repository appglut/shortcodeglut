<?php
namespace Shortcodeglut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\tools\ShortcodeglutTools;

class ShortcodeglutRegisterMenu {

	private $menut_slug = 'shortcodeglut';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'shortcodeglutMenuRegister' ) );
		add_action( 'admin_head', array( $this, 'fix_submenu_highlighting' ) );
		add_action( 'load-toplevel_page_shortcodeglut', array( $this, 'shortcodeglutToolsScreenOptions' ) );
		add_filter( 'admin_body_class', array( $this, 'shortcodeglutAdminBodyClass' ) );
	}

	public function shortcodeglutMenuRegister() {
		$shopt_menu = new ShortcodeglutTools();

		// Add main menu page for tools
		add_menu_page(
			esc_html__( 'ShortcodeGlut', 'shortcodeglut' ),
			esc_html__( 'ShortcodeGlut', 'shortcodeglut' ),
			'manage_options',
			$this->menut_slug,
			array( $shopt_menu, 'rendertoolsPages' ),
			$this->getMenuIcon(),
			56
		);

		// Add Woo Templates submenu
		add_submenu_page(
			$this->menut_slug,
			esc_html__( 'Woo Templates', 'shortcodeglut' ),
			esc_html__( 'Woo Templates', 'shortcodeglut' ),
			'manage_options',
			'shortcodeglut-woo-templates',
			array( $this, 'redirect_to_woo_templates' )
		);

		// Add Welcome submenu
		add_submenu_page(
			$this->menut_slug,
			esc_html__( 'Welcome', 'shortcodeglut' ),
			esc_html__( 'Welcome', 'shortcodeglut' ),
			'manage_options',
			'shortcodeglut-welcome',
			array( $this, 'render_welcome_page' )
		);

	}

	public function shortcodeglutToolsScreenOptions() {
		$option = 'per_page';
		$args = array(
			'label' => esc_html__( 'Templates', 'shortcodeglut' ),
			'default' => 20,
			'option' => 'woo_templates_per_page',
		);
		add_screen_option( $option, $args );
	}

	/**
	 * Add admin body class for shortcodeglut pages
	 */
	public function shortcodeglutAdminBodyClass( $classes ) {
		$screen = get_current_screen();
		if ( $screen && false !== strpos( $screen->id, 'shortcodeglut' ) ) {
			$classes .= ' shortcodeglut-admin';
		}
		return $classes;
	}

	/**
	 * Redirect to the correct Woo Templates URL
	 */
	public function redirect_to_woo_templates() {
		// Redirect to the correct URL with view parameter
		wp_safe_redirect( admin_url( 'admin.php?page=shortcodeglut&view=woo_templates' ) );
		exit;
	}

	/**
	 * Fix submenu highlighting for Woo Templates
	 */
	public function fix_submenu_highlighting() {
		global $parent_file, $submenu_file;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for menu highlighting only
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page parameter check for menu highlighting only
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

		// Highlight Woo Templates submenu when viewing woo_templates or woo_template_editor
		if ( $page === 'shortcodeglut' && ( $view === 'woo_templates' || $view === 'woo_template_editor' ) ) {
			$parent_file = 'shortcodeglut';
			$submenu_file = 'shortcodeglut-woo-templates';
		}
	}

	/**
	 * Render welcome page
	 */
	public function render_welcome_page() {
		// Enqueue welcome page styles directly
		wp_enqueue_style(
			'shortcodeglut-welcome-page',
			SHORTCODEGLUT_URL . 'global-assets/css/welcome-page.css',
			array(),
			defined('SHORTCODEGLUT_VERSION') ? SHORTCODEGLUT_VERSION : '1.0.0'
		);

		$welcome_page = new \Shortcodeglut\WelcomePage();
		$welcome_page->render_welcome_content();
	}

	/**
	 * Get the menu icon SVG
	 */
	private function getMenuIcon() {
		$icon_path = SHORTCODEGLUT_PATH . 'global-assets/images/menu_icon.svg';

		// Check if file exists and read it as data URL to avoid img padding
		if ( file_exists( $icon_path ) ) {
			$svg_content = file_get_contents( $icon_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read
			if ( $svg_content ) {
				return 'data:image/svg+xml;base64,' . base64_encode( $svg_content );
			}
		}

		// Fallback icon if file doesn't exist
		return 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M4.5 4.5C4.5 3.67 5.17 3 6 3H12C12.83 3 13.5 3.67 13.5 4.5V9H4.5V4.5Z" fill="url(#paint0_linear)"/><path d="M4.5 9H13.5V19.5C13.5 20.33 12.83 21 12 21H6C5.17 21 4.5 20.33 4.5 19.5V9Z" fill="url(#paint1_linear)"/><path d="M13.5 4.5C13.5 3.67 14.17 3 15 3H18C18.83 3 19.5 3.67 19.5 4.5V9H13.5V4.5Z" fill="url(#paint2_linear)"/><path d="M13.5 9H19.5V14.5C19.5 15.33 18.83 16 18 16H15C14.17 16 13.5 15.33 13.5 14.5V9Z" fill="url(#paint3_linear)"/><path d="M6 11H7.5V17H6V11Z" fill="white"/><path d="M10.5 11H12V17H10.5V11Z" fill="white"/><defs><linearGradient id="paint0_linear" x1="9" y1="3" x2="9" y2="9" gradientUnits="userSpaceOnUse"><stop stop-color="#FF6B6B"/><stop offset="1" stop-color="#FF8E53"/></linearGradient><linearGradient id="paint1_linear" x1="9" y1="9" x2="9" y2="21" gradientUnits="userSpaceOnUse"><stop stop-color="#FF8E53"/><stop offset="1" stop-color="#FF6B6B"/></linearGradient><linearGradient id="paint2_linear" x1="16.5" y1="3" x2="16.5" y2="9" gradientUnits="userSpaceOnUse"><stop stop-color="#4FACFE"/><stop offset="1" stop-color="#00F2FE"/></linearGradient><linearGradient id="paint3_linear" x1="16.5" y1="9" x2="16.5" y2="16" gradientUnits="userSpaceOnUse"><stop stop-color="#00F2FE"/><stop offset="1" stop-color="#4FACFE"/></linearGradient></defs></svg>' );
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
