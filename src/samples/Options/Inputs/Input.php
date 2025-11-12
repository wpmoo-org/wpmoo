<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class Input {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_input', __( 'Input', 'wpmoo' ), __( 'Text input.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::input( 'demo_input' )
					->label( __( 'Demo Input', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type…', 'wpmoo' ) ) )
					->description( __( 'Saved under the samples option set.', 'wpmoo' ) )
			);
	}
}
