<?php
namespace Shortcodeglut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\tools\ShortcodeglutTools;

class ShortcodeglutRegisterMenu {

	private $menut_slug = 'shortcodeglut_tools';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'shortcodeglutMenuRegister' ) );
		add_action( 'load-shortcodeglut_page_shortcodeglut_tools', array( $this, 'shortcodeglutToolsScreenOptions' ) );
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

	private function getMenuIcon() {
		return 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="#c7009c" width="100" height="100"/><text x="50%" y="50%" font-size="50" fill="white" text-anchor="middle" dominant-baseline="middle" font-family="sans-serif">SG</text></svg>' );
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
