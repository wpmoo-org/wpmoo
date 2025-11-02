<?php
/**
 * WPMoo Samples — Select demo.
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
 * Register a sample section for the Select field.
 */
final class Select {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_select';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		Moo::container( self::PAGE_ID, __( 'WPMoo Samples', 'wpmoo' ), '' )->menuSlug( 'wpmoo-samples' );

		Moo::section( self::SECTION_ID, __( 'Select', 'wpmoo' ), __( 'Single select dropdown.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::select( 'demo_select' )
					->label( __( 'Demo Select', 'wpmoo' ) )
					->options(
						array(
							'one'   => __( 'One', 'wpmoo' ),
							'two'   => __( 'Two', 'wpmoo' ),
							'three' => __( 'Three', 'wpmoo' ),
						)
					)
					->description( __( 'Choose a value.', 'wpmoo' ) )
			);
	}
}
