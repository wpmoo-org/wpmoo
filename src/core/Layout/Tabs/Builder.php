<?php
/**
 * Fluent builder for the Tabs layout component.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org
 * @link https://github.com/wpmoo/wpmoo
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html
 */

namespace WPMoo\Layout\Tabs;

use WPMoo\Layout\Builder as LayoutBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Tabs builder.
 */
class Builder extends LayoutBuilder {

	/**
	 * Switch layout orientation.
	 *
	 * @param string $orientation Orientation string (horizontal|vertical).
	 * @return $this
	 */
	public function orientation( string $orientation ): self {
		$orientation = strtolower( $orientation );
		if ( ! in_array( $orientation, array( 'horizontal', 'vertical' ), true ) ) {
			$orientation = 'horizontal';
		}

		return $this->set( 'orientation', $orientation );
	}

	/**
	 * Convenience shortcut for vertical layout.
	 *
	 * @return $this
	 */
	public function vertical(): self {
		return $this->orientation( 'vertical' );
	}

	/**
	 * Constructor.
	 *
	 * @param string $id Tabs identifier.
	 */
	public function __construct( string $id ) {
		parent::__construct( $id, 'tabs' );
	}

	/**
	 * Define tab panels/items.
	 *
	 * @param array<int, array<string, mixed>> $items Items array.
	 * @return $this
	 */
	public function items( array $items ): self {
		$normalized = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( isset( $item['fields'] ) && is_array( $item['fields'] ) ) {
				$item['fields'] = $this->normalize_fields( $item['fields'] );
			}

			if ( isset( $item['label'] ) && ! isset( $item['title'] ) ) {
				$item['title'] = $item['label'];
			}

			if ( ! isset( $item['type'] ) ) {
				$item['type'] = 'tab';
			}

			$normalized[] = $item;
		}

		return $this->set( 'items', $normalized );
	}
}
