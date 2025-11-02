<?php
/**
 * WPMoo Samples — Textarea demo.
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
 * Register a sample section for the Textarea field.
 */
final class Textarea {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_textarea';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		Moo::container( self::PAGE_ID, __( 'WPMoo Samples', 'wpmoo' ), '' )->menuSlug( 'wpmoo-samples' );

		Moo::section( self::SECTION_ID, __( 'Textarea', 'wpmoo' ), __( 'Multiline input field.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::textarea( 'demo_textarea' )
					->label( __( 'Demo Textarea', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type multi-line…', 'wpmoo' ) ) )
					->description( __( 'Saved under the samples option set.', 'wpmoo' ) )
			);
	}
}
