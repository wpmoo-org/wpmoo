<?php
/**
 * Shared helpers for generating responsive grid classes.
 *
 * @package WPMoo\Support\Concerns
 */

namespace WPMoo\Support\Concerns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides build_grid_classes() and normalise_grid_span() helpers.
 */
trait GeneratesGridClasses {

	/**
	 * Build responsive grid classes for a column configuration.
	 *
	 * @param array<string, int|string> $columns Column configuration.
	 * @return array<int, string>
	 */
	protected function build_grid_classes( array $columns ): array {
		$classes = array();

		foreach ( $columns as $breakpoint => $span ) {
			$span = $this->normalise_grid_span( $span );

			if ( 'default' === $breakpoint || '' === $breakpoint ) {
				$classes[] = 'wpmoo-col-' . $span;
				continue;
			}

			$breakpoint = strtolower( (string) $breakpoint );
			$breakpoint = preg_replace( '/[^a-z0-9]/', '', $breakpoint );

			if ( '' === $breakpoint ) {
				$classes[] = 'wpmoo-col-' . $span;
				continue;
			}

			$classes[] = 'wpmoo-col-' . $breakpoint . '-' . $span;
		}

		return $classes;
	}

	/**
	 * Clamp a grid span to valid bounds.
	 *
	 * @param mixed $span Raw span value.
	 * @return int
	 */
	protected function normalise_grid_span( $span ): int {
		if ( is_string( $span ) && is_numeric( $span ) ) {
			$span = (int) $span;
		} elseif ( ! is_int( $span ) ) {
			$span = (int) $span;
		}

		if ( $span < 1 ) {
			return 1;
		}

		if ( $span > 12 ) {
			return 12;
		}

		return $span;
	}
}

