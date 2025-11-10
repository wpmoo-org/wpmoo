<?php
/**
 * Toggle control (checkbox with role="switch").
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Toggle;

use WPMoo\Fields\Checkbox\Checkbox as BaseCheckbox;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Toggle is a semantic variant of checkbox rendered as a switch.
 */
class Toggle extends BaseCheckbox {
	/**
	 * Render toggle (checkbox + role="switch").
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$attributes         = $this->attributes();
		$attributes['role'] = 'switch';
		$this->override_attributes( $attributes );

		return parent::render( $name, $value );
	}

	/**
	 * Internal helper to override attributes array.
	 *
	 * @param array<string,mixed> $attributes Attrs.
	 * @return void
	 */
	protected function override_attributes( array $attributes ): void {
		$this->attributes = array_merge( $this->attributes, $attributes );
	}
}
