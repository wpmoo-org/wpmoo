<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class Checkbox {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_checkbox', __( 'Checkbox', 'wpmoo' ), __( 'Boolean switch.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::checkbox( 'demo_checkbox' )
					->label( __( 'Enable feature', 'wpmoo' ) )
			);
	}
}
