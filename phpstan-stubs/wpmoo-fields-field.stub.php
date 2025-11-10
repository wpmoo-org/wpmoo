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
	 * Static constructor for an input field.
	 *
	 * @return self
	 */
	public static function input( string $id ) {}

	/**
	 * Static constructor for a button pseudo-field.
	 *
	 * @return self
	 */
	public static function button( string $id ) {}

	/**
	 * Static constructor for a textarea field.
	 *
	 * @return self
	 */
	public static function textarea( string $id ) {}

	/**
	 * Static constructor for a select field.
	 *
	 * @return self
	 */
	public static function select( string $id ) {}

	/**
	 * Create a checkbox builder.
	 *
	 * @return self
	 */
	public static function checkbox( string $id ) {}

	/**
	 * Create a radio builder.
	 *
	 * @return self
	 */
	public static function radio( string $id ) {}

	/**
	 * Create a toggle builder.
	 *
	 * @return self
	 */
	public static function toggle( string $id ) {}

	/**
	 * Create a range builder.
	 *
	 * @return self
	 */
	public static function range( string $id ) {}

	/**
	 * Create an accordion builder.
	 *
	 * @return self
	 */
	public static function accordion( string $id ) {}

	/**
	 * Create a tabs builder.
	 *
	 * @return self
	 */
	public static function tabs( string $id ) {}
}
