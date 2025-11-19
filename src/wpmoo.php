<?php
/**
 * Plugin Name: WPMoo Framework
 * Plugin URI:  https://wpmoo.org
 * Description: Core framework utilities for WPMoo-based plugins.
 * Version:     0.1.1
 * Author:      Ahmet Cangir
 * Author URI:  https://wpmoo.org
 * Text Domain: wpmoo
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WPMoo
 */

if ( ! defined( 'WPMOO_FILE' ) ) {
	define( 'WPMOO_FILE', __FILE__ );
}

if ( ! defined( 'WPMOO_PATH' ) ) {
	define( 'WPMOO_PATH', __DIR__ . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'WPMOO_VERSION' ) ) {
	if ( function_exists( 'get_plugin_data' ) ) {
		$plugin_data = get_plugin_data( __FILE__ );
		define( 'WPMOO_VERSION', $plugin_data['Version'] );
	} else {
		define( 'WPMOO_VERSION', '0.1.1' );
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

// For reliable loading, include the samples directly
include_once WPMOO_PATH . 'samples/settings.php';
