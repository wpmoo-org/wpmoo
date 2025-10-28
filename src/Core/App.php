<?php
/**
 * Main application bootstrap for WPMoo.
 *
 * @package WPMoo\Core
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Core;

/**
 * Handles plugin bootstrap, hook registration, and shared utilities.
 */
final class App {

	/**
	 * Cached singleton instance.
	 *
	 * @var App|null
	 */
	private static $instance = null;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	protected $basename;

	/**
	 * Base filesystem path.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Base URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Text domain used for translations.
	 *
	 * @var string
	 */
	protected $textdomain;

	/**
	 * Retrieve the shared instance.
	 *
	 * @return App
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the framework for a plugin file.
	 *
	 * @param string $file       Main plugin file.
	 * @param string $textdomain Text domain slug.
	 * @return void
	 */
	public function boot( $file, $textdomain ) {
		if ( function_exists( 'plugin_basename' ) ) {
			$this->basename = plugin_basename( $file );
			$this->path     = plugin_dir_path( $file );
			$this->url      = plugin_dir_url( $file );
		} else {
			$this->basename = '';
			$this->path     = dirname( $file ) . '/';
			$this->url      = $this->path;
		}

		$this->textdomain = $textdomain;

		$this->define_constants( $file );
		$this->register_hooks();
	}

	/**
	 * Define shared constants for the framework.
	 *
	 * @param string $file Plugin file path.
	 * @return void
	 */
	protected function define_constants( $file ) {
		if ( ! defined( 'WPMOO_FILE' ) ) {
			define( 'WPMOO_FILE', $file );
		}

		$library_path = dirname( __DIR__, 2 ) . '/';

		if ( ! defined( 'WPMOO_PATH' ) ) {
			define( 'WPMOO_PATH', $library_path );
		}

		if ( ! defined( 'WPMOO_URL' ) ) {
			$library_path_normalized = str_replace( '\\', '/', $library_path );
			$url                     = '';

			if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
				$content_dir = str_replace( '\\', '/', WP_CONTENT_DIR );

				if ( 0 === strpos( $library_path_normalized, $content_dir ) ) {
					$url = WP_CONTENT_URL . '/' . ltrim( substr( $library_path_normalized, strlen( $content_dir ) ), '/' );
				}
			}

			if ( '' === $url && function_exists( 'plugins_url' ) && defined( 'WPMOO_FILE' ) ) {
				// Resolve assets from the installed Composer package path.
				// Package name is "wpmoo/wpmoo" so vendor path is "vendor/wpmoo/wpmoo/".
				$url = plugins_url( 'vendor/wpmoo/wpmoo/', WPMOO_FILE );
			}

			define( 'WPMOO_URL', rtrim( $url, '/\\' ) . '/' );
		}
	}

	/**
	 * Load the plugin textdomain if available.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		static $loaded = false;

		if ( $loaded ) {
			return;
		}

		if ( function_exists( 'load_plugin_textdomain' ) && $this->basename ) {
			load_plugin_textdomain( $this->textdomain, false, dirname( $this->basename ) . '/languages' );
			$loaded = true;
		}
	}

	/**
	 * Register core hooks for the framework lifecycle.
	 *
	 * @return void
	 */
	protected function register_hooks() {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'init', array( $this, 'load_textdomain' ), 0 );
			add_action( 'init', array( $this, 'init' ) );
		}

		if ( function_exists( 'register_activation_hook' ) ) {
			register_activation_hook( WPMOO_FILE, array( self::class, 'activate' ) );
		}

		if ( function_exists( 'register_deactivation_hook' ) ) {
			register_deactivation_hook( WPMOO_FILE, array( self::class, 'deactivate' ) );
		}
	}

	/**
	 * Trigger the activation event.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpmoo_activate' );
		}
	}

	/**
	 * Trigger the deactivation event.
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpmoo_deactivate' );
		}
	}

	/**
	 * Trigger the init event for consumers.
	 *
	 * @return void
	 */
	public function init() {
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpmoo_init' );
		}
	}

	/**
	 * Retrieve the plugin path, optionally appending a relative segment.
	 *
	 * @param string $append Relative path to append.
	 * @return string
	 */
	public function path( $append = '' ) {
		return $this->path . ltrim( $append, '/' );
	}

	/**
	 * Retrieve the plugin URL, optionally appending a relative segment.
	 *
	 * @param string $append Relative path to append.
	 * @return string
	 */
	public function url( $append = '' ) {
		return $this->url . ltrim( $append, '/' );
	}

	/**
	 * Retrieve the configured text domain.
	 *
	 * @return string
	 */
	public function textdomain() {
		return $this->textdomain;
	}
}
