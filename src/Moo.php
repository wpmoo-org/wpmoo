<?php

namespace WPMoo;

use WPMoo\Page\Page;        // Main page facade
use WPMoo\Layout\Layout;    // Main layout facade
use WPMoo\Field\Field;      // Main field facade
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

		// Loop through the call stack to find the first plugin file
		foreach ( $trace as $call ) {
			if ( isset( $call['file'] ) ) {
				$file = $call['file'];

				// Check if the file is part of a plugin (not core framework)
				if ( strpos( $file, '/wp-content/plugins/' ) !== false &&
					! str_contains( $file, '/wpmoo-org/wpmoo/' ) ) {

					// Extract plugin directory name from the path
					$plugin_path = explode( '/wp-content/plugins/', $file )[1];
					$plugin_dir = explode( '/', $plugin_path )[0];

					return $plugin_dir;
				}
			}
		}

		return 'wpmoo'; // Default fallback
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
}
