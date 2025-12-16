<?php
/**
 * WPMoo Framework Loader.
 *
 * This file provides a single function, wpmoo_loader(), to manage the framework
 * loading process. It negotiates which version to load and sets up autoloading,
 * using static variables internally to maintain state instead of a global class.
 *
 * @package WPMoo
 */

if ( function_exists( 'wpmoo_loader' ) ) {
	return;
}

/**
 * Manages the WPMoo framework loading, registration, and autoloading.
 *
 * @param string $action The action to perform ('register', 'negotiate_and_boot', 'load_autoloader').
 * @param mixed  ...$args Arguments for the action.
 */
function wpmoo_loader( string $action, ...$args ) {
	static $versions = array();
	static $booted   = false;

	switch ( $action ) {
		case 'register':
			list( $path, $version ) = $args;
			$versions[ $version ]   = array( 'path' => $path );

			if ( ! $booted ) {
				add_action(
					'plugins_loaded',
					function () {
						wpmoo_loader( 'negotiate_and_boot' );
					},
					-100
				);
				$booted = true;
			}
			break;

		case 'negotiate_and_boot':
			if ( empty( $versions ) ) {
				return;
			}

			$version_keys = array_keys( $versions );
			usort( $version_keys, 'version_compare' );
			$winner_version = end( $version_keys );
			$winner_path    = $versions[ $winner_version ]['path'];

			if ( file_exists( $winner_path ) && basename( $winner_path ) === 'boot.php' ) {
				require_once $winner_path;
			}
			break;

		case 'load_autoloader':
			list( $framework_base_path ) = $args;
			$vendor_autoload             = $framework_base_path . '/vendor/autoload.php';

			if ( file_exists( $vendor_autoload ) ) {
				require_once $vendor_autoload;
			} else {
				spl_autoload_register(
					function ( $class ) use ( $framework_base_path ) {
						$prefix   = 'WPMoo\\';
						$base_dir = $framework_base_path . '/';
						$len      = strlen( $prefix );
						if ( strncmp( $class, $prefix, $len ) !== 0 ) {
							return;
						}
						$relative_class = substr( $class, $len );
						$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
						if ( file_exists( $file ) ) {
							require $file;
						}
					}
				);
			}
			break;
	}
}
