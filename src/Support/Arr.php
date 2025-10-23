<?php
/**
 * Array helper utilities.
 *
 * @package WPMoo\Support
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides convenience methods for array manipulation.
 */
class Arr {

	/**
	 * Retrieve a value using dot-notation.
	 *
	 * @param mixed      $array   Source array.
	 * @param string     $key     Dot-notated key.
	 * @param mixed|null $default Default fallback.
	 * @return mixed
	 */
	public static function get( $array, $key, $default = null ) {
		if ( ! is_array( $array ) ) {
			return $default;
		}

		if ( array_key_exists( $key, $array ) ) {
			return $array[ $key ];
		}

		$segments = explode( '.', $key );

		foreach ( $segments as $segment ) {
			if ( is_array( $array ) && array_key_exists( $segment, $array ) ) {
				$array = $array[ $segment ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	/**
	 * Set a value using dot-notation.
	 *
	 * @param array<string, mixed> $array Target array (passed by reference).
	 * @param string               $key   Dot-notated key.
	 * @param mixed                $value Value to assign.
	 * @return void
	 */
	public static function set( &$array, $key, $value ) {
		$segments = explode( '.', $key );

		while ( count( $segments ) > 1 ) {
			$segment = array_shift( $segments );

			if ( ! isset( $array[ $segment ] ) || ! is_array( $array[ $segment ] ) ) {
				$array[ $segment ] = array();
			}

			$array = &$array[ $segment ];
		}

		$array[ array_shift( $segments ) ] = $value;
	}
}
