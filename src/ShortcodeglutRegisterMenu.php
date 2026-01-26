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
	 * Render welcome page
	 */
	public function render_welcome_page() {
		$welcome_page = new \Shortcodeglut\WelcomePage();
		$welcome_page->render_welcome_content();
	}

	private function getMenuIcon() {
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
