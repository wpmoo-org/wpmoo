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
require_once __DIR__ . '/includes/init.php';

// 3. Register our own autoloader to ensure we can load the framework classes.
// We register it regardless of whether Composer loaded the framework to ensure all classes are available.
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

		// Check if the class is already loaded by Composer or another autoloader.
		if ( class_exists( $class, false ) || interface_exists( $class, false ) || trait_exists( $class, false ) ) {
			// Class is already loaded by some other autoloader.
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

// 4. Define that the framework is loaded directly as a plugin.
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	define( 'WPMOO_PLUGIN_LOADED', true );
}

// 5. Boot the framework.
\WPMoo\WordPress\Bootstrap::instance()->boot( __FILE__, 'wpmoo' );
