<?php
/**
 * WPMoo Samples — Input field demo.
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
 * Register a sample section for the Input field.
 */
final class Input {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_input';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		// Reuse existing Samples page if present.
		Moo::container( self::PAGE_ID, __( 'WPMoo Samples', 'wpmoo' ), '' )->menuSlug( 'wpmoo-samples' );

		Moo::section( self::SECTION_ID, __( 'Input', 'wpmoo' ), __( 'Text input.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::input( 'demo_input' )
					->label( __( 'Demo Input', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type…', 'wpmoo' ) ) )
					->description( __( 'Saved under the samples option set.', 'wpmoo' ) )
			);
	}
}
