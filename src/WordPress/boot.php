<?php
/**
 * WPMoo Framework Bootstrap.
 *
 * This file handles constant definitions, autoloading, and framework initialization.
 *
 * @package WPMoo
 */

namespace WPMoo\WordPress;

use WPMoo\WordPress\Bootstrap;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

// Define framework constants.
if ( ! defined( 'WPMOO_VERSION' ) ) {
	define( 'WPMOO_VERSION', '0.1.0' );
}
if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', dirname( __DIR__, 2 ) ); // Points to the plugin root.
}
if ( ! defined( 'WPMOO_URL' ) ) {
	define( 'WPMOO_URL', plugin_dir_url( WPMOO_PATH . '/wpmoo.php' ) );
}

// Define that this is the main WPMoo plugin.
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	define( 'WPMOO_PLUGIN_LOADED', true );
}

// Use Composer autoloader if available, otherwise use our own simple autoloader.
if ( file_exists( WPMOO_PATH . '/vendor/autoload.php' ) ) {
	require_once WPMOO_PATH . '/vendor/autoload.php';
} else {
	// Fallback to a simple PSR-4 autoloader for the distributed version.
	spl_autoload_register(
		function ( $class ) {
			$prefix = 'WPMoo\\';
			$base_dir = WPMOO_PATH . '/src/';
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

// Register this plugin with the WPMoo loader.
if ( class_exists( 'WPMoo\\WordPress\\Bootstrap' ) ) {
	Bootstrap::initialize( WPMOO_PATH . '/wpmoo.php', 'wpmoo', WPMOO_VERSION );
}

// If this instance is the "winner" chosen by the loader, boot the framework.
if ( defined( 'WPMOO_IS_LOADING_WINNER' ) ) {
	Bootstrap::instance()->boot( WPMOO_PATH . '/wpmoo.php', 'wpmoo' );
}
