<?php
/**
 * WPMoo Samples — Range demo.
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
 * Register a sample section for the Range field.
 */
final class Range {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_range';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		// Root Samples container is created once in the aggregator.

		Moo::section( self::SECTION_ID, __( 'Range', 'wpmoo' ), __( 'Range slider.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
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
