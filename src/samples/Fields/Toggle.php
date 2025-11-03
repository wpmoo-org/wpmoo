<?php
/**
 * WPMoo Samples — Toggle demo.
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
 * Register a sample section for the Switch field.
 */
final class Toggle {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_toggle';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		// Root Samples container is created once in the aggregator.

		Moo::section( self::SECTION_ID, __( 'Toggle', 'wpmoo' ), __( 'Boolean toggle (role="switch").', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::toggle( 'demo_toggle' )
					->label( __( 'Enable notifications', 'wpmoo' ) )
			);
	}
}
