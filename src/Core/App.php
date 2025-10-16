<?php
/**
 * Main application bootstrap for WPMoo.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Core
 * @since 0.1.0
 */

namespace WPMoo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) );
}

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
			$this->basename = basename( $file );
			$this->path     = dirname( $file ) . '/';
			$this->url      = $this->path;
		}

		$this->textdomain = $textdomain;

		$this->define_constants( $file );
		$this->maybe_load_textdomain();
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

		if ( ! defined( 'WPMOO_PATH' ) ) {
			$path = $this->path ? $this->path : dirname( $file ) . '/';
			define( 'WPMOO_PATH', $path );
		}

		if ( ! defined( 'WPMOO_URL' ) ) {
			$url = $this->url ? $this->url : '';
			define( 'WPMOO_URL', $url );
		}
	}

	/**
	 * Load the plugin textdomain if available.
	 *
	 * @return void
	 */
	protected function maybe_load_textdomain() {
		if ( function_exists( 'load_plugin_textdomain' ) && $this->basename ) {
			load_plugin_textdomain( $this->textdomain, false, dirname( $this->basename ) . '/languages' );
		}
	}

	/**
	 * Register core hooks for the framework lifecycle.
	 *
	 * @return void
	 */
	protected function register_hooks() {
		if ( function_exists( 'add_action' ) ) {
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
