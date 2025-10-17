<?php
/**
 * Repository wrapper around WordPress options.
 *
 * WPMoo — WordPress Micro Object-Oriented Framework.
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 */

namespace WPMoo\Options;

use WPMoo\Support\Arr;

/**
 * Provides consistent read/write access to option arrays.
 */
class OptionRepository {

	/**
	 * Option key used for storage.
	 *
	 * @var string
	 */
	protected $option_key;

	/**
	 * Default values for the option set.
	 *
	 * @var array<string, mixed>
	 */
	protected $defaults;

	/**
	 * Cached option payload.
	 *
	 * @var array<string, mixed>|null
	 */
	protected $cache;

	/**
	 * Constructor.
	 *
	 * @param string               $option_key Option name.
	 * @param array<string, mixed> $defaults   Default values.
	 */
	public function __construct( $option_key, array $defaults = array() ) {
		$this->option_key = $option_key;
		$this->defaults   = $defaults;
		$this->cache      = null;
	}

	/**
	 * Returns the option key.
	 *
	 * @return string
	 */
	public function option_key() {
		return $this->option_key;
	}

	/**
	 * Returns the default values.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults() {
		return $this->defaults;
	}

	/**
	 * Retrieve all option values merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all() {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$stored = array();

		if ( function_exists( 'get_option' ) ) {
			$stored = get_option( $this->option_key, array() );
		}

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$this->cache = array_merge( $this->defaults, $stored );

		return $this->cache;
	}

	/**
	 * Retrieve a value from the option payload.
	 *
	 * @param string $key     Value key (dot notation supported).
	 * @param mixed  $default Default fallback.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$values = $this->all();

		if ( false !== strpos( $key, '.' ) ) {
			return Arr::get( $values, $key, $default );
		}

		return array_key_exists( $key, $values ) ? $values[ $key ] : $default;
	}

	/**
	 * Persist new option values.
	 *
	 * @param array<string, mixed> $values Values to store.
	 * @return array<string, mixed>
	 */
	public function save( array $values ) {
		$data        = array_merge( $this->defaults, $values );
		$this->cache = $data;

		if ( function_exists( 'update_option' ) ) {
			update_option( $this->option_key, $data );
		}

		return $data;
	}
}
