<?php
/**
 * Shared output escaping helpers with WordPress fallbacks.
 *
 * @package WPMoo\Support\Concerns
 * @since 0.4.4
 */

namespace WPMoo\Support\Concerns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides HTML/attribute escaping helpers that gracefully degrade outside WP.
 */
trait EscapesOutput {
	/**
	 * Escape a value for HTML context.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_html( $value ) {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Escape a value for attribute context.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_attr( $value ) {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Escape a translated string for attribute context.
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	protected function esc_attr__( $text, $domain ) {
		if ( function_exists( 'esc_attr__' ) ) {
			return esc_attr__( $text, $domain );
		}

		return $this->esc_attr( $text );
	}
}
