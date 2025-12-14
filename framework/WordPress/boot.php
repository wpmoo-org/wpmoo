<?php
/**
 * WPMoo Framework Core Bootstrap.
 *
 * This file is loaded ONLY by the "winning" version of the framework,
 * chosen by the WPMoo_Loader. It's responsible for setting up the core
 * services and firing the action that lets consumer plugins initialize.
 *
 * @package WPMoo
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define framework constants if they haven't been defined yet.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.2.0' ); // Update version
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	// This path points to the root of the winning framework instance.
	define( 'WPMOO_PATH', dirname( __DIR__, 2 ) ); 
}

// 1. Load the Composer autoloader for the winning framework version.
if ( file_exists( WPMOO_PATH . '/vendor/autoload.php' ) ) {
	require_once WPMOO_PATH . '/vendor/autoload.php';
} else {
	// Fallback to a simple PSR-4 autoloader for non-composer installs.
	spl_autoload_register(
		function ( $class ) {
			$prefix = 'WPMoo\\';
			$base_dir = __DIR__ . '/../'; // Relative to this boot.php file
			$len = strlen( $prefix );
			if ( strncmp( $class, $prefix, $len ) !== 0 ) {
				  return;
			}
			$relative_class = substr( $class, $len );
			$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

// 2. Boot the WordPress Kernel, which registers all hooks.
if ( class_exists( 'WPMoo\WordPress\Kernel' ) ) {
    \WPMoo\WordPress\Kernel::instance()->boot();
}

// 3. Fire the action to let all consuming plugins know the core is ready.
/**
 * Fires once the WPMoo framework's core is loaded and ready.
 *
 * @since 0.2.0
 */
do_action( 'wpmoo_loaded' );
