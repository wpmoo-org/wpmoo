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
	wp_die(); }

// Check if the main Moo class exists, if so, the framework has already been loaded.
if ( class_exists( 'WPMoo\\Moo', false ) ) {
	return;
}

// 1. Define framework constants.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.1.0' ); // Note: This might need to be dynamic or loaded from composer.json later.
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ ); // Plugin root directory.
}
if ( ! defined( 'WPMOO_URL' ) ) {
	define( 'WPMOO_URL', plugin_dir_url( __FILE__ ) ); // Plugin base URL.
}

// 2. Load Composer's autoloader.
// If using Composer, the autoloader should be available.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// 3. Define that the framework is loaded directly as a plugin.
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	define( 'WPMOO_PLUGIN_LOADED', true );
}

// 4. Boot the framework when loaded as a plugin.
// Ensure the Bootstrap class is available via Composer's autoloader.
\WPMoo\WordPress\Bootstrap::instance()->boot( __FILE__, 'wpmoo' );
