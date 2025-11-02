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
}
