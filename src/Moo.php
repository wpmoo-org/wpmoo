<?php

namespace WPMoo;

use WPMoo\Page\Page;        // Main page facade.
use WPMoo\Layout\Layout;    // Main layout facade.
use WPMoo\Field\Field;      // Main field facade.
use WPMoo\WordPress\Managers\FrameworkManager;

/**
 * Main facade for fluent API.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Moo {
	/**
	 * Get the calling plugin slug automatically from the call stack.
	 *
	 * @return string The slug of the plugin that called this method, or 'wpmoo' as fallback.
	 */
	private static function get_calling_plugin_slug(): string {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );

		// Loop through the call stack to find the first plugin file.
		foreach ( $trace as $call ) {
			if ( isset( $call['file'] ) ) {
				$file = $call['file'];

				// Check if the file is part of a plugin (not core framework).
				if ( strpos( $file, '/wp-content/plugins/' ) !== false ) {
					// Exclude the framework's own files.
					if ( str_contains( $file, '/wpmoo-org/wpmoo/' ) ) {
						continue;
					}

					// Extract plugin directory name from the path.
					$plugin_path = explode( '/wp-content/plugins/', $file )[1];
					$plugin_dir = explode( '/', $plugin_path )[0];

					// Handle vendor directories in case of Composer dependencies.
					// Check if this is a WPMoo-based plugin using the framework as a dependency.
					if ( $plugin_dir === 'vendor' && str_contains( $file, '/wpmoo/wpmoo/' ) ) {
						// Extract the full path relative to plugins directory.
						$relative_path = str_replace( '/wp-content/plugins/', '', $file );
						$parts = explode( '/', $relative_path );

						// Find the vendor directory and get the parent plugin.
						$vendor_index = array_search( 'vendor', $parts );
						if ( $vendor_index !== false && $vendor_index > 0 ) {
							// The plugin directory is the one before the vendor directory.
							return $parts[ $vendor_index - 1 ];
						}
					}

					return $plugin_dir;
				}
			}
		}

		return 'wpmoo'; // Default fallback.
	}

	/**
	 * Create a page builder.
	 *
	 * @param string $id Page ID.
	 * @param string $title Page title.
	 * @param string|null $plugin_slug Plugin slug to register the page under. If null, auto-detect.
	 * @return Page
	 */
	public static function page( string $id, string $title, ?string $plugin_slug = null ): Page {
		$plugin_slug = $plugin_slug ?? self::get_calling_plugin_slug();
		$page = new Page( $id, $title );
		FrameworkManager::instance()->add_page( $page, $plugin_slug );
		return $page;
	}

	/**
	 * Create a tabs layout component.
	 *
	 * @param string $id Tabs ID.
	 * @param string|null $plugin_slug Plugin slug to register the layout under. If null, auto-detect.
	 * @return \WPMoo\Layout\Component\Tabs
	 */
	public static function tabs( string $id, ?string $plugin_slug = null ) {
		$plugin_slug = $plugin_slug ?? self::get_calling_plugin_slug();
		$tabs = Layout::tabs( $id );
		FrameworkManager::instance()->add_layout( $tabs, $plugin_slug );
		return $tabs;
	}

	/**
	 * Create a field.
	 *
	 * @param string $type Field type.
	 * @param string $id Field ID.
	 * @param string|null $plugin_slug Plugin slug to register the field under. If null, auto-detect.
	 * @return \WPMoo\Field\Interfaces\FieldInterface
	 */
	public static function field( string $type, string $id, ?string $plugin_slug = null ) {
		$plugin_slug = $plugin_slug ?? self::get_calling_plugin_slug();
		$field = Field::{$type}( $id );
		FrameworkManager::instance()->add_field( $field, $plugin_slug );
		return $field;
	}
}
