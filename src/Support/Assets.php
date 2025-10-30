<?php
/**
 * Asset resolver utilities.
 *
 * @package WPMoo\Support
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Support;

use WPMoo\Core\App;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Resolves URLs to framework assets regardless of installation path.
 */
class Assets {
	/**
	 * Base assets URL cache.
	 *
	 * @var string|null
	 */
	protected static $base_url = null;

	/**
	 * Retrieve the base URL for framework assets.
	 *
	 * @return string
	 */
	public static function base_url(): string {
		if ( null !== self::$base_url ) {
			return self::$base_url;
		}

		$app         = App::instance();
		$plugin_path = $app->path();
		$plugin_url  = $app->url();
		if ( ! empty( $plugin_path ) && ! empty( $plugin_url ) ) {
			$plugin_path        = self::normalize_path( $plugin_path ) . '/';
			$plugin_url         = self::trail( $plugin_url );
			$vendor_assets_path = $plugin_path . 'vendor/wpmoo/wpmoo/assets/';
			$direct_assets_path = $plugin_path . 'assets/';
			if ( is_dir( $vendor_assets_path ) ) {
				self::$base_url = self::trail( $plugin_url . 'vendor/wpmoo/wpmoo/assets/' );
				return self::$base_url;
			}
			if ( is_dir( $direct_assets_path ) ) {
				self::$base_url = self::trail( $plugin_url . 'assets/' );
				return self::$base_url;
			}
		}

		$library_dir = str_replace( '\\', '/', dirname( __DIR__, 2 ) );
		$assets_path = $library_dir . '/assets/';

		if ( defined( 'WPMOO_FILE' ) && function_exists( 'plugins_url' ) && self::is_within_plugin_directory( WPMOO_FILE ) ) {
			$plugin_dir         = str_replace( '\\', '/', plugin_dir_path( WPMOO_FILE ) );
			$direct_assets_path = $plugin_dir . 'assets/';
			$vendor_assets_path = $plugin_dir . 'vendor/wpmoo/wpmoo/assets/';

			if ( file_exists( $vendor_assets_path ) ) {
				self::$base_url = self::trail( plugins_url( 'vendor/wpmoo/wpmoo/assets/', WPMOO_FILE ) );
				return self::$base_url;
			}

			if ( file_exists( $direct_assets_path ) ) {
				self::$base_url = self::trail( plugins_url( 'assets/', WPMOO_FILE ) );
				return self::$base_url;
			}

			$plugin_real = str_replace( '\\', '/', dirname( WPMOO_FILE ) );

			if ( 0 === strpos( $assets_path, $plugin_real ) ) {
				$relative       = ltrim( substr( $assets_path, strlen( $plugin_real ) ), '/' );
				self::$base_url = self::trail( plugins_url( $relative, WPMOO_FILE ) );

				return self::$base_url;
			}
		}

		if ( function_exists( 'plugin_dir_url' ) ) {
			self::$base_url = self::trail( plugin_dir_url( __DIR__ ) . '../assets/' );
		} else {
			self::$base_url = ''; // Cannot resolve in this context.
		}

		return self::$base_url;
	}

	/**
	 * Build a URL to an asset relative to the framework assets directory.
	 *
	 * @param string $path Relative path from the assets directory.
	 * @return string
	 */
	public static function url( string $path = '' ): string {
		$asset_path = ltrim( $path, '/' );

		if ( self::should_use_minified() ) {
			$asset_path = self::maybe_minified( $asset_path );
		}

		return self::base_url() . $asset_path;
	}

	/**
	 * Ensure a trailing slash regardless of available helper functions.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	protected static function trail( string $value ): string {
		return rtrim( $value, '\\/' ) . '/';
	}

	/**
	 * Determine whether minified assets should be preferred.
	 *
	 * @return bool
	 */
	protected static function should_use_minified(): bool {
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			return ! SCRIPT_DEBUG;
		}

		return true;
	}

	/**
	 * Convert an asset path to its minified counterpart if available.
	 *
	 * @param string $asset_path Relative asset path.
	 * @return string
	 */
	protected static function maybe_minified( string $asset_path ): string {
		if ( preg_match( '/\.(css|js)$/', $asset_path, $matches ) ) {
			$extension = $matches[1];
			$minified  = preg_replace( '/\.' . preg_quote( $extension, '/' ) . '$/', '.min.' . $extension, $asset_path );

			if ( self::asset_exists( $minified ) ) {
				return $minified;
			}
		}

		return $asset_path;
	}

	/**
	 * Check whether an asset exists within the framework bundle.
	 *
	 * @param string $asset_path Relative asset path.
	 * @return bool
	 */
	protected static function asset_exists( string $asset_path ): bool {
		return file_exists( App::instance()->path( 'assets/' . $asset_path ) );
	}

	/**
	 * Normalize a filesystem path to use forward slashes without a trailing slash.
	 *
	 * @param string $path Filesystem path.
	 * @return string
	 */
	protected static function normalize_path( string $path ): string {
		if ( '' === $path ) {
			return '';
		}

		return rtrim( str_replace( '\\', '/', $path ), '/' );
	}

	/**
	 * Determine whether a file or directory path resides within a plugins directory.
	 *
	 * @param string $path Filesystem path to evaluate.
	 * @return bool
	 */
	protected static function is_within_plugin_directory( string $path ): bool {
		if ( '' === $path ) {
			return false;
		}

		$normalized_path = self::normalize_path( $path );
		$plugin_roots    = array();

		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$plugin_roots[] = self::normalize_path( WP_PLUGIN_DIR );
		}

		if ( defined( 'WPMU_PLUGIN_DIR' ) ) {
			$plugin_roots[] = self::normalize_path( WPMU_PLUGIN_DIR );
		}

		foreach ( $plugin_roots as $root ) {
			if ( '' !== $root && 0 === strpos( $normalized_path, $root ) ) {
				return true;
			}
		}

		return false;
	}
}
