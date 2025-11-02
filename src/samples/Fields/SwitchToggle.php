<?php
/**
 * WPMoo Samples — Switch demo.
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
final class SwitchToggle {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_switch';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		Moo::container( self::PAGE_ID, __( 'WPMoo Samples', 'wpmoo' ), '' )->menuSlug( 'wpmoo-samples' );

		Moo::section( self::SECTION_ID, __( 'Switch', 'wpmoo' ), __( 'Boolean switch with role.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::switch( 'demo_switch' )
					->label( __( 'Enable notifications', 'wpmoo' ) )
			);
	}
}
