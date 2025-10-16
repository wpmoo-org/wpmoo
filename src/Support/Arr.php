<?php
/**
 * Array helper utilities.
 * This file is part of WPMoo (https://wpmoo.org)
 *
 * Licensed under the GNU General Public License v3.0
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Support
 * @since 0.1.0
 */

namespace WPMoo\Support;

/**
 * Provides convenience methods for array manipulation.
 */
class Arr {

	/**
	 * Retrieve a value using dot-notation.
	 *
	 * @param mixed       $array   Source array.
	 * @param string      $key     Dot-notated key.
	 * @param mixed|null  $default Default fallback.
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
