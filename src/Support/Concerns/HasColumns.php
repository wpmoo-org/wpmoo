<?php
/**
 * Shared helpers for parsing responsive grid column definitions.
 *
 * @package WPMoo\Support\Concerns
 */

namespace WPMoo\Support\Concerns;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides helpers for normalising responsive column spans.
 */
trait HasColumns {

	/**
	 * Normalise column span arguments into a keyed array.
	 *
	 * @param array<int|string, mixed> $arguments Raw column span arguments.
	 * @return array<string, int>
	 */
	protected function parseColumnSpans( array $arguments ): array {
		$arguments = $this->normalizeColumnsArgumentList( $arguments );
		$columns   = array();

		foreach ( $arguments as $key => $value ) {
			if ( is_array( $value ) ) {
				$columns = array_merge( $columns, $this->parseColumnSpans( $value ) );
				continue;
			}

			if ( is_string( $key ) && ! is_numeric( $key ) ) {
				$breakpoint = $this->normalizeBreakpointKey( $key );
				$span       = $this->clampColumnSpan( $value );

				if ( null !== $span ) {
					$columns[ $breakpoint ] = $span;
				}

				continue;
			}

			if ( is_numeric( $value ) || ( is_string( $value ) && is_numeric( trim( $value ) ) ) ) {
				$span = $this->clampColumnSpan( $value );

				if ( null !== $span ) {
					$columns['default'] = $span;
				}

				continue;
			}

			if ( is_string( $value ) ) {
				$value = trim( $value );

				if ( '' === $value ) {
					continue;
				}

				if ( false !== strpos( $value, '-' ) ) {
					list( $breakpoint, $span_value ) = explode( '-', $value, 2 );
					$breakpoint                      = $this->normalizeBreakpointKey( $breakpoint );
					$span                            = $this->clampColumnSpan( $span_value );

					if ( null !== $span ) {
						$columns[ $breakpoint ] = $span;
					}

					continue;
				}

				$span = $this->clampColumnSpan( $value );

				if ( null !== $span ) {
					$columns['default'] = $span;
				}
			}
		}

		if ( empty( $columns['default'] ) ) {
			$columns['default'] = 12;
		}

		return $columns;
	}

	/**
	 * Flatten mixed column arguments into a predictable list.
	 *
	 * @param array<int|string, mixed> $arguments Argument list.
	 * @return array<int|string, mixed>
	 */
	protected function normalizeColumnsArgumentList( array $arguments ): array {
		if ( 1 === count( $arguments ) ) {
			$single = $arguments[0];

			if ( is_array( $single ) ) {
				return $single;
			}

			if ( is_string( $single ) ) {
				$single = trim( $single );

				if ( '' === $single ) {
					return array();
				}

				$parts = preg_split( '/\s+/', $single );

				return false !== $parts ? $parts : array( $single );
			}
		}

		return $arguments;
	}

	/**
	 * Clamp a column span to the grid bounds.
	 *
	 * @param mixed $value Raw value.
	 * @return int|null
	 */
	protected function clampColumnSpan( $value ): ?int {
		if ( is_string( $value ) ) {
			$value = preg_replace( '/[^0-9]/', '', $value );

			if ( '' === $value ) {
				return null;
			}
		}

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$span = (int) $value;

		if ( $span < 1 ) {
			$span = 1;
		} elseif ( $span > 12 ) {
			$span = 12;
		}

		return $span;
	}

	/**
	 * Normalise a breakpoint key.
	 *
	 * @param string $breakpoint Raw breakpoint value.
	 * @return string
	 */
	protected function normalizeBreakpointKey( string $breakpoint ): string {
		$breakpoint = strtolower( trim( $breakpoint ) );

		if ( '' === $breakpoint || 'default' === $breakpoint || 'base' === $breakpoint || 'auto' === $breakpoint ) {
			return 'default';
		}

		$breakpoint = preg_replace( '/[^a-z0-9]/', '', $breakpoint );

		return '' !== $breakpoint ? $breakpoint : 'default';
	}
}
