<?php
/**
 * Plugin Name: WPMoo Framework
 * Plugin URI:  https://wpmoo.org
 * Description: Core framework utilities for WPMoo-based plugins.
 * Version:     0.1.0
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
	define( 'WPMOO_VERSION', '0.1.0' );
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
if ( function_exists( 'plugin_basename' ) && class_exists( \WPMoo\Core\App::class ) ) {
	\WPMoo\Core\App::instance()->boot( __FILE__, 'wpmoo' );
}

// Dev-time SCSS compiler for wpmoo-ui (only in admin + debug, and when scssphp is available).
if ( function_exists( 'add_action' ) ) {
	add_action(
		'plugins_loaded',
		function () {
			if ( class_exists( '\\WPMoo\\Support\\Dev\\UiCompiler' ) ) {
				\WPMoo\Support\Dev\UiCompiler::register();
			}
		},
		20
	);
}

// Dev-time samples (Text field demo). Loads only in admin and when debug is on
// or explicitly enabled via the WPMOO_SAMPLES constant.
if ( function_exists( 'add_action' ) ) {
	add_action(
		'plugins_loaded',
		function () {
			$enabled = ( defined( 'WPMOO_SAMPLES' ) && WPMOO_SAMPLES )
				|| ( defined( 'WP_DEBUG' ) && WP_DEBUG )
				|| ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

			if ( ! $enabled ) {
				return;
			}

			if ( function_exists( 'is_admin' ) && ! is_admin() ) {
				return;
			}

			if ( class_exists( '\\WPMoo\\Samples\\Fields\\Text' ) ) {
				\WPMoo\Samples\Fields\Text::register();
			}
		},
		25
	);
}
