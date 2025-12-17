<?php

namespace WPMoo\WordPress\AssetEnqueuers;

/**
 * Base asset enqueuer for managing CSS and JS assets in the WPMoo framework.
 *
 * @package WPMoo\WordPress\AssetEnqueuers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
abstract class AssetEnqueuer {

	/**
	 * Enqueue styles for the framework.
	 *
	 * @param string        $handle Asset handle.
	 * @param string        $src Asset source URL.
	 * @param array<string> $deps Asset dependencies.
	 * @param string|null   $version Asset version.
	 * @param string        $media Media attribute for CSS.
	 * @return void
	 */
	protected function enqueue_style( string $handle, string $src, array $deps = array(), ?string $version = null, string $media = 'all' ): void {
		wp_enqueue_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * Enqueue scripts for the framework.
	 *
	 * @param string        $handle Asset handle.
	 * @param string        $src Asset source URL.
	 * @param array<string> $deps Asset dependencies.
	 * @param string|null   $version Asset version.
	 * @param bool          $in_footer Whether to enqueue in footer.
	 * @return void
	 */
	protected function enqueue_script( string $handle, string $src, array $deps = array(), ?string $version = null, bool $in_footer = true ): void {
		wp_enqueue_script( $handle, $src, $deps, $version, $in_footer );
	}

	/**
	 * Register styles for the framework.
	 *
	 * @param string        $handle Asset handle.
	 * @param string        $src Asset source URL.
	 * @param array<string> $deps Asset dependencies.
	 * @param string|null   $version Asset version.
	 * @param string        $media Media attribute for CSS.
	 * @return void
	 */
	protected function register_style( string $handle, string $src, array $deps = array(), ?string $version = null, string $media = 'all' ): void {
		wp_register_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * Register scripts for the framework.
	 *
	 * @param string        $handle Asset handle.
	 * @param string        $src Asset source URL.
	 * @param array<string> $deps Asset dependencies.
	 * @param string|null   $version Asset version.
	 * @param bool          $in_footer Whether to enqueue in footer.
	 * @return void
	 */
	protected function register_script( string $handle, string $src, array $deps = array(), ?string $version = null, bool $in_footer = true ): void {
		wp_register_script( $handle, $src, $deps, $version, $in_footer );
	}

	/**
	 * Get the URL to the framework resource directory.
	 *
	 * @param string $path Optional. Additional path appended to the resource URL.
	 * @return string The URL to the framework resource directory with optional additional path.
	 */
	protected function get_asset_url( string $path = '' ): string {
		// Get the base URL for the framework directory.
		$base_url = plugin_dir_url(
			dirname( __DIR__, 3 ) . '/wpmoo.php'  // Navigate to the main plugin file.
		);

		// Append the assets path.
		$resource_url = $base_url . 'assets/';

		if ( ! empty( $path ) ) {
			$resource_url .= ltrim( $path, '/' );
		}

		return $resource_url;
	}

	/**
	 * Get the path to the framework assets directory.
	 *
	 * @param string $path Optional. Additional path appended to the assets path.
	 * @return string The path to the framework assets directory with optional additional path.
	 */
	protected function get_asset_path( string $path = '' ): string {
		// Get the base path for the framework directory.
		$base_path = dirname( __DIR__, 3 ) . '/assets';

		if ( ! empty( $path ) ) {
			$base_path .= '/' . ltrim( $path, '/' );
		}

		return $base_path;
	}
}
