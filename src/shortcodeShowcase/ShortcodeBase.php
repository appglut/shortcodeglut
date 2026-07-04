<?php
/**
 * Shortcode Base Class
 *
 * Provides common functionality for all shortcode classes.
 * Child classes extend this and define their own shortcode_slug,
 * shortcode_name, and get_default_atts() method.
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

namespace Shortcodeglut\shortcodeShowcase;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class ShortcodeBase {

	/**
	 * Shortcode slug (tag)
	 * @var string
	 */
	protected $shortcode_slug = '';

	/**
	 * Shortcode name (for display)
	 * @var string
	 */
	protected $shortcode_name = '';

	/**
	 * Shortcode counter for unique IDs
	 * @var int
	 */
	protected $shortcode_counter = 0;

	/**
	 * Constructor
	 * Registers the shortcode with WordPress
	 */
	protected function __construct() {
		add_shortcode( $this->shortcode_slug, array( $this, 'render' ) );
	}

	/**
	 * Main shortcode render method
	 * Child classes can override this for custom rendering logic
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Rendered output
	 */
	public function render( $atts ) {
		// Sanitize attributes to handle copy-paste issues
		$atts = $this->sanitize_shortcode_attributes( $atts );

		// Merge attributes with defaults
		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->shortcode_slug );

		// Increment counter for unique IDs
		$this->shortcode_counter++;

		// Allow child classes to override
		return $this->render_shortcode( $atts );
	}

	/**
	 * Sanitize shortcode attributes to handle common copy-paste issues
	 * Removes HTML artifacts, hidden characters, and malformed data
	 *
	 * @param array $atts Raw shortcode attributes
	 * @return array Sanitized attributes
	 */
	private function sanitize_shortcode_attributes( $atts ) {
		if ( empty( $atts ) || ! is_array( $atts ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $atts as $key => $value ) {
			// Skip numeric keys with empty values or HTML artifacts
			if ( is_int( $key ) ) {
				// Skip empty strings, slashes, HTML closing tags, etc.
				if ( empty( $value ) || in_array( trim( $value ), array( '', '/', '/>', '>', '\\', '&#gt;', '&#62;', '&lt;', '&gt;', '&amp;' ), true ) ) {
					continue;
				}
				// Try to extract key=value from numeric keys (malformed attributes)
				if ( strpos( $value, '=' ) !== false ) {
					$parts = explode( '=', $value, 2 );
					if ( count( $parts ) === 2 ) {
						$new_key = trim( $parts[0] );
						$new_value = trim( $parts[1], '"\'' ); // Remove quotes
						$sanitized[ $new_key ] = $new_value;
						continue;
					}
				}
			}

			// Convert curly quotes to straight quotes
			if ( is_string( $value ) ) {
				// Define curly quote characters using chr() for safety
				$curly_double_open  = chr( 0xC2 ) . chr( 0x93 ); // "
				$curly_double_close = chr( 0xC2 ) . chr( 0x94 ); // "
				$curly_single_open  = chr( 0xC2 ) . chr( 0x91 ); // '
				$curly_single_close = chr( 0xC2 ) . chr( 0x92 ); // '

				$value = str_replace(
					array( $curly_double_open, $curly_double_close, $curly_single_open, $curly_single_close, "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99" ),
					array( '"', '"', "'", "'", '"', '"', "'", "'" ),
					$value
				);
				// Decode HTML entities
				$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
				// Remove invisible characters except normal whitespace
				$value = preg_replace( '/[^\x20-\x7E\t\n\r]/', '', $value );
				// Trim whitespace
				$value = trim( $value );
			}

			$sanitized[ $key ] = $value;
		}

		return $sanitized;
	}

	/**
	 * Get default shortcode attributes
	 * Child classes must override this method
	 *
	 * @return array Default attributes
	 */
	abstract protected function get_default_atts();

	/**
	 * Render the shortcode
	 * Child classes override this method for custom rendering
	 *
	 * @param array $atts Parsed shortcode attributes
	 * @return string Rendered output
	 */
	public function render_shortcode( $atts ) {
		return '';
	}

	/**
	 * Get the shortcode slug
	 *
	 * @return string Shortcode slug
	 */
	public function get_shortcode_slug() {
		return $this->shortcode_slug;
	}

	/**
	 * Get the shortcode name
	 *
	 * @return string Shortcode name
	 */
	public function get_shortcode_name() {
		return $this->shortcode_name;
	}

	/**
	 * Get current counter value
	 *
	 * @return int Current counter value
	 */
	public function get_counter() {
		return $this->shortcode_counter;
	}

	/**
	 * Reset counter (useful for testing)
	 */
	public function reset_counter() {
		$this->shortcode_counter = 0;
	}
}
