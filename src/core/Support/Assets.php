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
	 * Resolve URL to the UI stylesheet (wpmoo-ui/css/wpmoo.css), preferring Composer vendor.
	 * Falls back to the legacy framework CSS under assets/css/wpmoo.css when vendor is absent.
	 *
	 * @return string Empty string if not resolvable.
	 */
	public static function ui_css_url(): string {
		// Prefer local compiled assets in this package during development,
		// then legacy bundle, and only then external UI packages.
		$candidates = array(
			'src/assets/css/wpmoo.css',                 // New unified src assets location (preferred)
			'assets/css/wpmoo.css',                     // Legacy framework bundle
			'vendor/wpmoo/wpmoo-ui/css/wpmoo.css',      // External UI package (optional)
			'vendor/wpmoo-org/wpmoo-ui/css/wpmoo.css',  // Legacy/vendor-alt (defensive)
		);

		// 1) WordPress-aware resolution (most reliable in plugins):
		if (
			defined( 'WPMOO_FILE' )
			&& function_exists( 'plugins_url' )
			&& function_exists( 'plugin_dir_path' )
			&& self::is_within_plugin_directory( WPMOO_FILE )
		) {
			$plugin_dir = str_replace( '\\', '/', plugin_dir_path( WPMOO_FILE ) );
			$base_url   = self::trail( plugins_url( '/', WPMOO_FILE ) );

			foreach ( $candidates as $rel ) {
				if ( file_exists( $plugin_dir . $rel ) ) {
					$url = $base_url . $rel;
					return function_exists( 'apply_filters' ) ? (string) apply_filters( 'wpmoo_ui_css_url', $url, $candidates ) : $url;
				}
			}
		}

		// 2) Fallback: use App information (may not always map cleanly to URLs).
		// 2a) Symlink-friendly heuristic: if plugin is not under plugins dir, try resolving by slug under WP_PLUGIN_DIR.
		if (
			defined( 'WP_PLUGIN_DIR' )
			&& defined( 'WP_PLUGIN_URL' )
			&& defined( 'WPMOO_FILE' )
			&& function_exists( 'plugins_url' )
			&& ! self::is_within_plugin_directory( WPMOO_FILE )
		) {
			$slug = basename( dirname( WPMOO_FILE ) );
			foreach ( $candidates as $rel ) {
				$abs = rtrim( str_replace( '\\', '/', WP_PLUGIN_DIR ), '/\\' ) . '/' . $slug . '/' . $rel;
				if ( file_exists( $abs ) ) {
					$url = plugins_url( $slug . '/' . $rel );
					return function_exists( 'apply_filters' ) ? (string) apply_filters( 'wpmoo_ui_css_url', $url, $candidates ) : $url;
				}
			}
		}

		// 2b) Last resort: use App information (path/url pair as provided at boot time).
		$app         = App::instance();
		$plugin_path = self::normalize_path( $app->path() );
		$plugin_url  = self::trail( $app->url() );

		if ( '' !== $plugin_path && '' !== $plugin_url ) {
			foreach ( $candidates as $rel ) {
				$abs = $plugin_path . '/' . $rel;
				if ( file_exists( $abs ) ) {
					$url = $plugin_url . str_replace( '\\', '/', $rel );
					return function_exists( 'apply_filters' ) ? (string) apply_filters( 'wpmoo_ui_css_url', $url, $candidates ) : $url;
				}
			}
		}

		$url = '';
		return function_exists( 'apply_filters' ) ? (string) apply_filters( 'wpmoo_ui_css_url', $url, $candidates ) : $url;
	}

	// Intentionally no admin augmentation: fully Pico via wpmoo-ui.

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
			$src_assets_path    = $plugin_path . 'src/assets/';
			if ( is_dir( $vendor_assets_path ) ) {
				self::$base_url = self::trail( $plugin_url . 'vendor/wpmoo/wpmoo/assets/' );
				return self::$base_url;
			}
			if ( is_dir( $direct_assets_path ) ) {
				self::$base_url = self::trail( $plugin_url . 'assets/' );
				return self::$base_url;
			}
			if ( is_dir( $src_assets_path ) ) {
				self::$base_url = self::trail( $plugin_url . 'src/assets/' );
				return self::$base_url;
			}
		}

		$library_dir = str_replace( '\\', '/', dirname( __DIR__, 2 ) );
		$assets_path = $library_dir . '/assets/';

		if ( defined( 'WPMOO_FILE' ) && function_exists( 'plugins_url' ) && self::is_within_plugin_directory( WPMOO_FILE ) ) {
			$plugin_dir         = str_replace( '\\', '/', plugin_dir_path( WPMOO_FILE ) );
			$direct_assets_path = $plugin_dir . 'assets/';
			$vendor_assets_path = $plugin_dir . 'vendor/wpmoo/wpmoo/assets/';
			$src_assets_path    = $plugin_dir . 'src/assets/';

			if ( file_exists( $vendor_assets_path ) ) {
				self::$base_url = self::trail( plugins_url( 'vendor/wpmoo/wpmoo/assets/', WPMOO_FILE ) );
				return self::$base_url;
			}

			if ( file_exists( $direct_assets_path ) ) {
				self::$base_url = self::trail( plugins_url( 'assets/', WPMOO_FILE ) );
				return self::$base_url;
			}

			if ( file_exists( $src_assets_path ) ) {
				self::$base_url = self::trail( plugins_url( 'src/assets/', WPMOO_FILE ) );
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
		$app = App::instance();
		if ( file_exists( $app->path( 'assets/' . $asset_path ) ) ) {
			return true;
		}
		if ( file_exists( $app->path( 'src/assets/' . $asset_path ) ) ) {
			return true;
		}

		return false;
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
