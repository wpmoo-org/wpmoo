<?php
/**
 * Plugin Name: WPMoo Framework
 * Plugin URI: https://wpmoo.org
 * Description: A Simple and Lightweight WordPress Option Framework for Themes and Plugins.
 * Author: WPMoo
 * Author URI: https://wpmoo.org
 * Version: 0.1.0
 * Text Domain: wpmoo
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 *
 * @package WPMoo
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

// Define framework constants required for registration and asset loading.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.1.0' );
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ );
}
if ( ! defined( 'WPMOO_URL' ) ) {
	define( 'WPMOO_URL', plugin_dir_url( __FILE__ ) );
}

// Load the Composer autoloader to make the Bootstrap class available.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Register this plugin with the WPMoo loader.
// The loader will decide which version of the framework to actually boot on the 'plugins_loaded' hook.
if ( class_exists( 'WPMoo\\WordPress\\Bootstrap' ) ) {
	\WPMoo\WordPress\Bootstrap::initialize( __FILE__, 'wpmoo', WPMOO_VERSION );
}

// If this instance is the "winner" chosen by the loader, boot the framework.
if ( defined( 'WPMOO_IS_LOADING_WINNER' ) ) {
	\WPMoo\WordPress\Bootstrap::instance()->boot( __FILE__, 'wpmoo' );
}
