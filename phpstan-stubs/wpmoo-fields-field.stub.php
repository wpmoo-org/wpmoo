<?php
/**
 * PHPStan stub for WPMoo\Fields\Field magic static constructors.
 *
 * Distributed with the framework so consumers can include it from vendor/.
 * These stubs allow static analysis to understand runtime-provided
 * magic static constructors on the Field builder.
 */

namespace WPMoo\Fields;

/**
 * Field static factory stubs for static analysis.
 */
class Field {

	/**
	 * Static constructor for a text field.
	 *
	 * @return self
	 */
	public static function text( string $id ) {}

	/**
	 * Static constructor for a textarea field.
	 *
	 * @return self
	 */
	public static function textarea( string $id ) {}

	/**
	 * Static constructor for a color field.
	 *
	 * @return self
	 */
	public static function color( string $id ) {}

	/**
	 * Static constructor for a checkbox field.
	 *
	 * @return self
	 */
	public static function checkbox( string $id ) {}

	/**
	 * Static constructor for an accordion field.
	 *
	 * @return self
	 */
	public static function accordion( string $id ) {}

	/**
	 * Static constructor for a fieldset field.
	 *
	 * @return self
	 */
	public static function fieldset( string $id ) {}
}
