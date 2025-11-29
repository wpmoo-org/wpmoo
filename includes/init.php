<?php
/**
 * WPMoo Framework Loader.
 *
 * This file defines constants, prevents double loading, and registers autoloader.
 * Path and URL constants should be defined by the plugin that includes this file.
 *
 * @package WPMoo
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

// If the main Moo class exists, it means the framework has already been loaded.
// We check without triggering autoloading since autoloading might trigger a circular require.
if ( class_exists( 'WPMoo\\Moo', false ) ) {
	return;
}

// 1. Define framework constants.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.1.0' );
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ . '/..' );
}
if ( ! defined( 'WPMOO_URL' ) ) {
	define( 'WPMOO_URL', plugin_dir_url( __DIR__ . '/wpmoo.php' ) );
}

// 2. Register our own autoloader to ensure we can load the framework classes.
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

// 3. Define that the framework is loaded directly as a plugin.
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	define( 'WPMOO_PLUGIN_LOADED', true );
}

// 4. Boot the framework when loaded as a plugin.
\WPMoo\WordPress\Bootstrap::instance()->boot( __DIR__ . '/../wpmoo.php', 'wpmoo' );
