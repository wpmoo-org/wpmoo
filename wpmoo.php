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

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

// Define framework constants directly in the main plugin file.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.1.0' );
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ );
}
if ( ! defined( 'WPMOO_URL' ) ) {
	define( 'WPMOO_URL', plugin_dir_url( __FILE__ ) );
}

// If this file is present, it means WPMoo is installed in a development environment.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Load the framework guard file to prevent double-loading.
require_once __DIR__ . '/init.php';

// Define a constant to indicate that WPMoo is loaded as a standalone plugin.
// This allows the framework to enable plugin-specific features like sample pages.
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	define( 'WPMOO_PLUGIN_LOADED', true );
}

// Boot the framework. This call is what registers all the WordPress hooks.
\WPMoo\WordPress\Bootstrap::instance()->boot( __FILE__, 'wpmoo' );
