<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class Button {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_button', __( 'Button', 'wpmoo' ), __( 'Button field type.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::button( 'demo_button' )
					->label( __( 'Run', 'wpmoo' ) )
					->attributes( array( 'class' => 'contrast' ) )
			);
	}
}
