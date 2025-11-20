<?php
/**
 * Plugin Name: WPMoo Framework
 * Plugin URI:  https://wpmoo.org
 * Description: Core framework utilities for WPMoo-based plugins.
 * Version:     0.1.2
 * Author:      Ahmet Cangir
 * Author URI:  https://wpmoo.org
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WPMoo
 */

if ( ! defined( 'WPMOO_FILE' ) ) {
	define( 'WPMOO_FILE', __FILE__ );
}

// Define that the framework is loaded as a plugin
// Only define this when the file is directly accessed as a plugin (not via Composer)
if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
	// Check if this file is being loaded directly as a plugin by seeing if it's in the plugins directory
	// and if it's the main plugin file being activated (versus a dependency)
	$loaded_as_plugin = false;

	// Determine if this is the main plugin file
	if ( function_exists( 'plugin_basename' ) ) {
		$plugin_relative_path = plugin_basename( __FILE__ );
		// If the file path matches the plugin's main file (not a subfolder of another plugin)
		// and this file is directly in the plugin directory level
		$filename = basename( __FILE__ );
		if ( $plugin_relative_path === $filename || strpos( $plugin_relative_path, $filename . '/' ) === 0 ) {
			$loaded_as_plugin = true;
		}
	}

	define( 'WPMOO_PLUGIN_LOADED', $loaded_as_plugin );
}


if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'WPMOO_VERSION' ) ) {
	// Get the version from the plugin header without using get_plugin_data to avoid early textdomain loading
	$plugin_contents = @file_get_contents( __FILE__ );
	if ( false !== $plugin_contents && preg_match( '/^[ \t\/*#@]*Version:\s*(.*)$/mi', $plugin_contents, $matches ) ) {
		define( 'WPMOO_VERSION', trim( $matches[1] ) );
	} else {
		define( 'WPMOO_VERSION', '0.1.2' );
	}
}

$autoload_paths = array(
	WPMOO_PATH . 'vendor/autoload.php',
	dirname( WPMOO_PATH, 1 ) . '/vendor/autoload.php',
	dirname( WPMOO_PATH, 2 ) . '/vendor/autoload.php',
	dirname( WPMOO_PATH, 3 ) . '/vendor/autoload.php',
);

foreach ( $autoload_paths as $autoload ) {
	if ( file_exists( $autoload ) ) {
		require_once $autoload;
		break;
	}
}

// Only boot inside a real WordPress runtime to avoid side effects under tooling.
if ( function_exists( 'plugin_basename' ) ) {
	\WPMoo\WordPress\Bootstrap::instance()->boot( __FILE__, 'wpmoo' );
}
