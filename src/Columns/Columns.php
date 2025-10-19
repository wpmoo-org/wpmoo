<?php
/**
 * Admin columns manager for post types and taxonomies.
 *
 * @package WPMoo\Columns
 * @since 0.2.0
 */

namespace WPMoo\Columns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages admin table columns for post types and taxonomies.
 */
class Columns {
	/**
	 * All defined columns (replaces defaults if set).
	 *
	 * @var array<string, string>
	 */
	protected $items = array();

	/**
	 * Columns to add.
	 *
	 * @var array<string, string>
	 */
	protected $add = array();

	/**
	 * Columns to hide.
	 *
	 * @var array<int, string>
	 */
	protected $hide = array();

	/**
	 * Column positions.
	 *
	 * @var array<string, int>
	 */
	protected $positions = array();

	/**
	 * Custom populate callbacks.
	 *
	 * @var array<string, callable>
	 */
	protected $populate = array();

	/**
	 * Sortable columns configuration.
	 *
	 * @var array<string, string|array<int, mixed>>
	 */
	protected $sortable = array();

	/**
	 * Set all columns (replaces defaults).
	 *
	 * @param array<string, string> $columns Column slug => label pairs.
	 * @return $this
	 */
	public function set( array $columns ): self {
		$this->items = $columns;

		return $this;
	}

	/**
	 * Add new column(s).
	 *
	 * @param string|array<string, string> $columns Column slug or slug => label array.
	 * @param string|null                  $label   Label if $columns is string.
	 * @return $this
	 */
	public function add( $columns, ?string $label = null ): self {
		if ( ! is_array( $columns ) ) {
			$columns = array( $columns => $label );
		}

		foreach ( $columns as $column => $column_label ) {
			if ( is_null( $column_label ) ) {
				$column_label = ucwords( str_replace( array( '_', '-' ), ' ', $column ) );
			}

			$this->add[ $column ] = $column_label;
		}

		return $this;
	}

	/**
	 * Hide column(s).
	 *
	 * @param string|array<int, string> $columns Column slug(s) to hide.
	 * @return $this
	 */
	public function hide( $columns ): self {
		$columns = is_string( $columns ) ? array( $columns ) : $columns;

		foreach ( $columns as $column ) {
			$this->hide[] = $column;
		}

		return $this;
	}

	/**
	 * Set custom populate callback for a column.
	 *
	 * @param string   $column   Column slug.
	 * @param callable $callback Populate function.
	 * @return $this
	 */
	public function populate( string $column, callable $callback ): self {
		$this->populate[ $column ] = $callback;

		return $this;
	}

	/**
	 * Set column positions.
	 *
	 * @param array<string, int> $columns Column slug => position pairs.
	 * @return $this
	 */
	public function order( array $columns ): self {
		foreach ( $columns as $column => $position ) {
			$this->positions[ $column ] = $position;
		}

		return $this;
	}

	/**
	 * Make columns sortable.
	 *
	 * @param string|array<string, string|array<int, mixed>> $column_or_array Column slug or array of sortable columns.
	 * @param string|array<int, mixed>|null                  $meta_key        Meta key or [meta_key, is_num] for single column.
	 * @return $this
	 */
	public function sortable( $column_or_array, $meta_key = null ): self {
		// If first parameter is array, use batch mode.
		if ( is_array( $column_or_array ) ) {
			foreach ( $column_or_array as $column => $options ) {
				$this->sortable[ $column ] = $options;
			}
		} else {
			// Single column mode.
			$this->sortable[ $column_or_array ] = $meta_key;
		}

		return $this;
	}

	/**
	 * Check if an orderby field is a custom sortable column.
	 *
	 * @param string $orderby Orderby value from query params.
	 * @return bool
	 */
	public function isSortable( string $orderby ): bool {
		if ( array_key_exists( $orderby, $this->sortable ) ) {
			return true;
		}

		foreach ( $this->sortable as $options ) {
			if ( is_string( $options ) && $options === $orderby ) {
				return true;
			}
			if ( is_array( $options ) && isset( $options[0] ) && $options[0] === $orderby ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get meta key for an orderby value.
	 *
	 * @param string $orderby Orderby value from query params.
	 * @return string|array<int, mixed>
	 */
	public function sortableMeta( string $orderby ) {
		if ( array_key_exists( $orderby, $this->sortable ) ) {
			return $this->sortable[ $orderby ];
		}

		foreach ( $this->sortable as $options ) {
			if ( is_string( $options ) && $options === $orderby ) {
				return $options;
			}
			if ( is_array( $options ) && isset( $options[0] ) && $options[0] === $orderby ) {
				return $options;
			}
		}

		return '';
	}

	/**
	 * Modify columns array (merge additions, remove hidden, reorder).
	 *
	 * @param array<string, string> $columns WordPress default columns.
	 * @return array<string, string>
	 */
	public function modifyColumns( array $columns ): array {
		// If user set specific columns, return those.
		if ( ! empty( $this->items ) ) {
			return $this->items;
		}

		// Add new columns.
		if ( ! empty( $this->add ) ) {
			foreach ( $this->add as $key => $label ) {
				$columns[ $key ] = $label;
			}
		}

		// Hide columns.
		if ( ! empty( $this->hide ) ) {
			foreach ( $this->hide as $key ) {
				unset( $columns[ $key ] );
			}
		}

		// Reorder columns.
		if ( ! empty( $this->positions ) ) {
			foreach ( $this->positions as $key => $position ) {
				$index = array_search( $key, array_keys( $columns ), true );
				if ( false === $index ) {
					continue;
				}

				$item = array_slice( $columns, $index, 1, true );
				unset( $columns[ $key ] );

				$start = array_slice( $columns, 0, $position, true );
				$end   = array_slice( $columns, $position, null, true );

				$columns = $start + $item + $end;
			}
		}

		return $columns;
	}

	/**
	 * Get populate callbacks.
	 *
	 * @return array<string, callable>
	 */
	public function getPopulateCallbacks(): array {
		return $this->populate;
	}

	/**
	 * Get sortable configuration.
	 *
	 * @return array<string, string|array<int, mixed>>
	 */
	public function getSortable(): array {
		return $this->sortable;
	}
}
