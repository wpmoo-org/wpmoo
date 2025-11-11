<?php
/**
 * Fluent builder for the Accordion layout component.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout\Accordion;

use WPMoo\Layout\LayoutBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Accordion builder.
 */
class Builder extends LayoutBuilder {

	/**
	 * Constructor.
	 *
	 * @param string $id Accordion identifier.
	 */
	public function __construct( string $id ) {
		parent::__construct( $id, 'accordion' );
	}

	/**
	 * Define accordion items.
	 *
	 * @param array<int, array<string, mixed>> $items Item configuration.
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
