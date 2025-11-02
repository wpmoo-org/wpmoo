<?php
/**
 * WPMoo Samples — Radio demo.
 *
 * @package WPMoo\\Samples\\Fields
 */

namespace WPMoo\Samples\Fields;

use WPMoo\Moo;
use WPMoo\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Register a sample section for the Radio field.
 */
final class Radio {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_radio';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		Moo::container( self::PAGE_ID, __( 'WPMoo Samples', 'wpmoo' ), '' )->menuSlug( 'wpmoo-samples' );

		Moo::section( self::SECTION_ID, __( 'Radio', 'wpmoo' ), __( 'Single choice options.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
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
