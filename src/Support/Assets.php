<?php
/**
 * Asset resolver utilities.
 *
 * @package WPMoo\Support
 * @since 0.3.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

		$library_dir = str_replace( '\\', '/', dirname( __DIR__, 2 ) );
		$assets_path = $library_dir . '/assets/';

		if ( defined( 'WPMOO_FILE' ) && function_exists( 'plugins_url' ) ) {
			$plugin_dir = str_replace( '\\', '/', plugin_dir_path( WPMOO_FILE ) );
			$vendor_path = $plugin_dir . 'vendor/wpmoo-org/wpmoo/assets/';

			if ( file_exists( $vendor_path ) ) {
				self::$base_url = self::trail( plugins_url( 'vendor/wpmoo-org/wpmoo/assets/', WPMOO_FILE ) );

				return self::$base_url;
			}

			$plugin_real = str_replace( '\\', '/', dirname( WPMOO_FILE ) );

			if ( 0 === strpos( $assets_path, $plugin_real ) ) {
				$relative = ltrim( substr( $assets_path, strlen( $plugin_real ) ), '/' );
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
		return self::base_url() . ltrim( $path, '/' );
	}

	/**
	 * Ensure a trailing slash regardless of available helper functions.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	protected static function trail( string $value ): string {
		return rtrim( $value, "\\/" ) . '/';
	}
}
