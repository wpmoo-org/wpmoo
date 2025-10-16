<?php
/**
 * String helper utilities.
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
 * Provides convenience string helpers.
 */
class Str {

	/**
	 * Determine if a string contains a given substring.
	 *
	 * @param string $haystack Source string.
	 * @param string $needle   Substring to check.
	 * @return bool
	 */
	public static function contains( $haystack, $needle ) {
		return '' === $needle || false !== strpos( $haystack, $needle );
	}

	/**
	 * Determine if a string starts with a given substring.
	 *
	 * @param string $haystack Source string.
	 * @param string $needle   Prefix substring.
	 * @return bool
	 */
	public static function startsWith( $haystack, $needle ) {
		return '' === $needle || 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}

	/**
	 * Determine if a string ends with a given substring.
	 *
	 * @param string $haystack Source string.
	 * @param string $needle   Suffix substring.
	 * @return bool
	 */
	public static function endsWith( $haystack, $needle ) {
		if ( '' === $needle ) {
			return true;
		}

		$length = strlen( $haystack ) - strlen( $needle );

		if ( $length < 0 ) {
			return false;
		}

		return false !== strpos( $haystack, $needle, $length );
	}
}
