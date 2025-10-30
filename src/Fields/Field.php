<?php
/**
 * Fluent field builder alias: Field now maps to the builder.
 *
 * @package WPMoo\Fields
 */

namespace WPMoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Field builder (alias for FieldBuilder).
 */
class Field extends FieldBuilder {}
