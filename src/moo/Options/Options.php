<?php
/**
 * Public API for registering WPMoo option pages.
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Options;

use WPMoo\Fields\Manager;
use WPMoo\Options\Builder as PageBuilder;
use WPMoo\Page\Page;
use WPMoo\Options\OptionRepository;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Facade for interacting with the options subsystem.
 */
class Options {

	/**
	 * Whether the subsystem has been booted.
	 *
	 * @var bool
	 */
	protected static $booted = false;

	/**
	 * Shared field manager instance.
	 *
	 * @var Manager
	 */
	protected static $field_manager;

	/**
	 * Registered option pages.
	 *
	 * @var Page[]
	 */
	protected static $pages = array();

	/**
	 * Cached repositories keyed by option name.
	 *
	 * @var array<string, OptionRepository>
	 */
	protected static $repositories = array();

	/**
	 * Start a new options page builder.
	 *
	 * @param string        $option_key Option key.
	 * @param callable|null $callback Optional configurator executed with the builder.
	 * @return PageBuilder
	 */
	public static function create( string $option_key, ?callable $callback = null ): PageBuilder {
		self::boot();

		$builder = new PageBuilder( $option_key, self::$field_manager );

		$register_callback = static function () use ( $builder ) {
			$builder->register();
		};

		if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
			$doing_init = function_exists( 'doing_action' ) && doing_action( 'init' );

			if ( $doing_init && function_exists( 'add_action' ) ) {
				add_action( 'init', $register_callback, 99 );
			} else {
				$register_callback();
			}
		} elseif ( function_exists( 'add_action' ) ) {
			add_action( 'init', $register_callback, 15 );
		}

		if ( null !== $callback ) {
			$callback( $builder );
		}

		return $builder;
	}

	/**
	 * Register a new options page (backward compatibility).
	 *
	 * @param string|array<string, mixed> $option_key_or_config Option key or full config array.
	 * @return PageBuilder|Page
	 */
	public static function register( $option_key_or_config ) {
		self::boot();

		// Backward compatibility: if array is passed, use old method.
		if ( is_array( $option_key_or_config ) ) {
			return self::registerFromArray( $option_key_or_config );
		}

		// New fluent API: return Builder.
		return self::create( (string) $option_key_or_config );
	}

	/**
	 * Register from array configuration (backward compatibility).
	 *
	 * @param array<string, mixed> $config Configuration array.
	 * @return Page
	 */
	protected static function registerFromArray( array $config ): Page {
		$page = new Page( $config, self::$field_manager );

		self::$pages[] = $page;
		$page->boot();

		$repository = $page->repository();

		if ( $repository ) {
			self::$repositories[ $repository->option_key() ] = $repository;
		}

		return $page;
	}

	/**
	 * Internal method to register a page from Builder.
	 *
	 * @param Page $page Page instance.
	 * @return void
	 */
	public static function registerPage( Page $page ): void {
		self::$pages[] = $page;

		$repository = $page->repository();

		if ( $repository ) {
			self::$repositories[ $repository->option_key() ] = $repository;
		}
	}

	/**
	 * Retrieve registered pages.
	 *
	 * @return Page[]
	 */
	public static function pages() {
		return self::$pages;
	}

	/**
	 * Return the shared field manager.
	 *
	 * @return Manager
	 */
	public static function field_manager() {
		self::boot();

		return self::$field_manager;
	}

	/**
	 * Retrieve a repository by option name.
	 *
	 * @param string $option_key Option identifier.
	 * @return OptionRepository|null
	 */
	public static function repository( $option_key ) {
		return isset( self::$repositories[ $option_key ] ) ? self::$repositories[ $option_key ] : null;
	}

	/**
	 * Get an option payload with defaults merged in.
	 *
	 * @param string               $option_key Option identifier.
	 * @param array<string, mixed> $default    Fallback values.
	 * @return array<string, mixed>
	 */
	public static function get( $option_key, $default = array() ) {
		$repository = self::repository( $option_key );

		if ( $repository ) {
			return $repository->all();
		}

		if ( function_exists( 'get_option' ) ) {
			$stored = get_option( $option_key, $default );

			if ( ! is_array( $stored ) ) {
				return $default;
			}

				return array_merge( $default, $stored );
		}

			return $default;
	}

		/**
		 * Replace entire option payload (persist immediately).
		 *
		 * @param string               $option_key Option identifier.
		 * @param array<string, mixed> $data       New data.
		 * @return bool True on success.
		 */
	public static function set( string $option_key, array $data ): bool {
		if ( function_exists( 'update_option' ) ) {
			return (bool) update_option( $option_key, $data );
		}

		return false;
	}

		/**
		 * Patch option payload with partial data (merge and persist).
		 *
		 * @param string               $option_key Option identifier.
		 * @param array<string, mixed> $patch      Partial data to merge.
		 * @return bool True on success.
		 */
	public static function update( string $option_key, array $patch ): bool {
		$current = self::get( $option_key, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$merged = array_merge( $current, $patch );
		return self::set( $option_key, $merged );
	}

		/**
		 * Delete an option payload.
		 *
		 * @param string $option_key Option identifier.
		 * @return bool True on success.
		 */
	public static function delete( string $option_key ): bool {
		if ( function_exists( 'delete_option' ) ) {
			return (bool) delete_option( $option_key );
		}

		return false;
	}

	/**
	 * Retrieve a single option value.
	 *
	 * @param string $option_key Option identifier.
	 * @param string $key        Value key.
	 * @param mixed  $default    Fallback value.
	 * @return mixed
	 */
	public static function value( $option_key, $key, $default = null ) {
		$repository = self::repository( $option_key );

		if ( $repository ) {
			return $repository->get( $key, $default );
		}

		$settings = self::get( $option_key, array() );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Ensure the subsystem is initialized.
	 *
	 * @return void
	 */
	protected static function boot() {
		if ( self::$booted ) {
			return;
		}

		self::$field_manager = Manager::instance();

		self::$booted = true;
	}
}
