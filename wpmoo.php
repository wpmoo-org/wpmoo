<?php
/**
 * Plugin Name: WPMoo Framework
 * Plugin URI: https://wpmoo.org
 * Description: A Simple and Lightweight WordPress Option Framework for Themes and Plugins.
 * Author: WPMoo
 * Author URI: https://wpmoo.org
 * Version: 0.1.3
 * Text Domain: wpmoo
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 *
 * @package WPMoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

// 1. Define framework constants.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.1.0' );
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ );
}
if ( ! defined( 'WPMOO_URL' ) ) {
	define( 'WPMOO_URL', plugin_dir_url( __FILE__ ) );
}

// 2. Include the guard file to prevent double loading.
require_once __DIR__ . '/init.php';

// 3. If no Composer autoloader has loaded the framework, register our own.
if ( ! class_exists( 'WPMoo\\Moo' ) ) {
	/**
	 * Simple PSR-4 autoloader for the WPMoo framework.
	 * This allows the plugin to run standalone without a vendor directory.
	 *
	 * @param string $class The fully-qualified class name.
	 * @return void
	 */
	spl_autoload_register(
		function ( $class ) {
			// Base namespace for the framework.
			$prefix = 'WPMoo\\';

			// Does the class use the namespace prefix?
			$len = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				// No, move to the next registered autoloader.
				return;
			}

			// Get the relative class name.
			$relative_class = substr( $class, $len );

			// Build the file path.
			$file = WPMOO_PATH . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';

			// If the file exists, require it.
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

// 4. Define that the framework is loaded directly as a plugin.
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	define( 'WPMOO_PLUGIN_LOADED', true );
}

// 5. Boot the framework.
\WPMoo\WordPress\Bootstrap::instance()->boot( __FILE__, 'wpmoo' );
