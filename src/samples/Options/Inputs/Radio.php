<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Sample Radio section registrar.
 *
 * This demo illustrates how to add a radio input field to an options page.
 */
final class Radio {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_radio', __( 'Radio', 'wpmoo' ), __( 'Single choice options.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::radio( 'demo_radio' )
					->label( __( 'Pick one', 'wpmoo' ) )
					->options(
						array(
							'a' => __( 'Option A', 'wpmoo' ),
							'b' => __( 'Option B', 'wpmoo' ),
							'c' => __( 'Option C', 'wpmoo' ),
						)
					)
			);
	}
}
