<?php
/**
 * String helper utilities.
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

	/**
	 * Generate a slug using WordPress helpers when available.
	 *
	 * @param string $value Raw input value.
	 * @return string
	 */
	public static function slug( $value ) {
		$value = (string) $value;

		if ( '' === $value ) {
			return '';
		}

		if ( function_exists( 'sanitize_title' ) ) {
			return sanitize_title( $value );
		}

		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );

		return trim( (string) $value, '-' );
	}
}
