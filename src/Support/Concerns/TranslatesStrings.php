<?php
/**
 * Shared translation helpers with WordPress fallbacks.
 *
 * @package WPMoo\Support\Concerns
 * @since 0.4.4
 */

namespace WPMoo\Support\Concerns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides translation helpers that remain safe outside WordPress runtime.
 */
trait TranslatesStrings {
	/**
	 * Translate a string using WordPress helpers when available.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Optional text-domain, defaults to framework domain.
	 * @return string
	 */
	protected function translate( string $text, string $domain = 'wpmoo' ): string {
		return function_exists( '__' ) ? \__( $text, $domain ) : $text;
	}
}
