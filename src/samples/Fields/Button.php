<?php
/**
 * WPMoo Samples — Button demo.
 *
 * @package WPMoo\Samples\Fields
 */

namespace WPMoo\Samples\Fields;

use WPMoo\Moo;
use WPMoo\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Register a sample section for the Button control.
 */
final class Button {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_button';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		// Root Samples container is created once in the aggregator.

		Moo::section( self::SECTION_ID, __( 'Button', 'wpmoo' ), __( 'Button field type.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::button( 'demo_button' )
					->label( __( 'Run', 'wpmoo' ) )
					->attributes( array( 'class' => 'contrast' ) )
			);
	}
}
