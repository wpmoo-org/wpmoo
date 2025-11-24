<?php

namespace WPMoo\Fields;

/**
 * Field stub class for PHPStan.
 *
 * Provides static methods for creating different field types.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Field {

	/**
	 * Create a text field.
	 *
	 * @param string $id Field ID.
	 * @return self Field instance.
	 */
	public static function text( string $id ) {}

	/**
	 * Create a textarea field.
	 *
	 * @param string $id Field ID.
	 * @return self Field instance.
	 */
	public static function textarea( string $id ) {}

	/**
	 * Create a color field.
	 *
	 * @param string $id Field ID.
	 * @return self Field instance.
	 */
	public static function color( string $id ) {}

	/**
	 * Create a checkbox field.
	 *
	 * @param string $id Field ID.
	 * @return self Field instance.
	 */
	public static function checkbox( string $id ) {}

	/**
	 * Create an accordion layout component.
	 *
	 * @param string $id Accordion ID.
	 * @return self Accordion instance.
	 */
	public static function accordion( string $id ) {}

	/**
	 * Create a fieldset layout component.
	 *
	 * @param string $id Fieldset ID.
	 * @return self Fieldset instance.
	 */
	public static function fieldset( string $id ) {}
}
