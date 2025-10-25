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

	/**
	 * Translate a string with context.
	 *
	 * @param string $text    Text to translate.
	 * @param string $context Context describing the usage.
	 * @param string $domain  Optional text-domain, defaults to framework domain.
	 * @return string
	 */
	protected function translate_with_context( string $text, string $context, string $domain = 'wpmoo' ): string {
		return function_exists( '_x' ) ? _x( $text, $context, $domain ) : $text;
	}

	/**
	 * Translate plural strings.
	 *
	 * @param string $singular Singular form.
	 * @param string $plural   Plural form.
	 * @param int    $count    Item count.
	 * @param string $domain   Optional text-domain, defaults to framework domain.
	 * @return string
	 */
	protected function translate_plural( string $singular, string $plural, int $count, string $domain = 'wpmoo' ): string {
		if ( function_exists( '_n' ) ) {
			return _n( $singular, $plural, $count, $domain );
		}

		return $count === 1 ? $singular : $plural;
	}

	/**
	 * Translate plural strings with context.
	 *
	 * @param string $singular Singular form.
	 * @param string $plural   Plural form.
	 * @param int    $count    Item count.
	 * @param string $context  Context describing the usage.
	 * @param string $domain   Optional text-domain, defaults to framework domain.
	 * @return string
	 */
	protected function translate_plural_with_context( string $singular, string $plural, int $count, string $context, string $domain = 'wpmoo' ): string {
		if ( function_exists( '_nx' ) ) {
			return _nx( $singular, $plural, $count, $context, $domain );
		}

		return $count === 1 ? $singular : $plural;
	}
}
