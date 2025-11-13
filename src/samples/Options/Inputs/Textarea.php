<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Sample Textarea section registrar.
 *
 * This demo illustrates how to add a textarea input field to an options page.
 */
final class Textarea {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_textarea', __( 'Textarea', 'wpmoo' ), __( 'Multiline input field.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::textarea( 'demo_textarea' )
					->label( __( 'Demo Textarea', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type multi-line…', 'wpmoo' ) ) )
					->description( __( 'Saved under the samples option set.', 'wpmoo' ) )
			);
	}
}
