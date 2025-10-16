<?php
/**
 * Public API for registering WPMoo option pages.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 */

namespace WPMoo\Options;

use WPMoo\Fields\Checkbox\Checkbox as CheckboxField;
use WPMoo\Fields\Manager;
use WPMoo\Fields\Text\Text as TextField;
use WPMoo\Fields\Textarea\Textarea as TextareaField;

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
	 * Register a new options page.
	 *
	 * @param array<string, mixed> $config Configuration array.
	 * @return Page
	 */
	public static function register( array $config ) {
		self::boot();

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
	 * @param string              $option_key Option identifier.
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

		self::$field_manager = new Manager();
		self::register_default_fields();

		self::$booted = true;
	}

	/**
	 * Register the default field types.
	 *
	 * @return void
	 */
	protected static function register_default_fields() {
		self::$field_manager->register( 'text', TextField::class );
		self::$field_manager->register( 'textarea', TextareaField::class );
		self::$field_manager->register( 'checkbox', CheckboxField::class );
	}
}
