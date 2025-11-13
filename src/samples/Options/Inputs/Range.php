<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Sample Range section registrar.
 *
 * This demo illustrates how to add a range input field to an options page.
 */
final class Range {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_range', __( 'Range', 'wpmoo' ), __( 'Range slider.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::range( 'demo_range' )
					->label( __( 'Volume', 'wpmoo' ) )
					->attributes(
						array(
							'min' => 0,
							'max' => 100,
							'step' => 5,
						)
					)
			);
	}
}
