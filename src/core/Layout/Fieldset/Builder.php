<?php
/**
 * Fluent builder for Fieldset layout component.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org
 * @link https://github.com/wpmoo/wpmoo
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html
 */

namespace WPMoo\Layout\Fieldset;

use WPMoo\Layout\Builders\LayoutBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fieldset builder.
 */
class Builder extends LayoutBuilder {

	/**
	 * Constructor.
	 *
	 * @param string $id Fieldset identifier.
	 */
	public function __construct( string $id ) {
		parent::__construct( $id, 'fieldset' );
	}

	/**
	 * Define fieldset items/sections.
	 *
	 * @param array<int, array<string, mixed>> $items Section definitions.
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

			$normalized[] = $item;
		}

		return $this->set( 'items', $normalized );
	}
}
