<?php
/**
 * Shortcode Showcase Loader
 *
 * Initializes the shortcode showcase module for ShortcodeGlut plugin.
 *
 * @package Shortcodeglut
 * @subpackage ShortcodeShowcase
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the actual init file
require_once __DIR__ . '/init.php';
