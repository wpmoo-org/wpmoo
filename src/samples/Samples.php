<?php
/**
 * WPMoo Samples — aggregator/registrar for sample demos.
 *
 * @package WPMoo\Samples
 */

namespace WPMoo\Samples;

use WPMoo\Moo;
use WPMoo\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Central registrar that wires up all sample demos in one place.
 */
final class Samples {
	/**
	 * Root page identifier for all Samples sections.
	 */
	public const PAGE_ID = 'wpmoo_samples';

	/**
	 * Root page menu slug.
	 */
	public const MENU_SLUG = 'wpmoo-samples';

	/**
	 * Register all sample modules.
	 *
	 * Intended to be called once from the framework loader in dev/admin.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Ensure the root Samples page exists before children define sections.
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'page_sample' ), 5 );
			add_action( 'wpmoo_init', array( self::class, 'sections' ), 5 );
		}
	}

	/**
	 * Create the root Samples page container once.
	 */
	public static function page_sample(): void {
		Moo::page( self::PAGE_ID )
			->title( __( 'WPMoo Samples', 'wpmoo' ) )
			->menuSlug( self::MENU_SLUG )
			->sticky_header()
			->ajax_save();
	}

	/**
	 * Define the Sections.
	 *
	 * @return void
	 */
	public static function sections(): void {
		Moo::section('layout_examples', 'Layout Examples')
			->parent( self::PAGE_ID )
			->fields(
				Field::input('first_name')->label('First Name')->width(50),
				Field::input('last_name')->label('Last Name')->width(50),
				Field::input('company')->label('Company')->width(50),
				Field::input('role')->label('Role')->width(50),
				Field::textarea('bio')->label('Biography')
			);

		Moo::section( 'sample_input', __( 'Input', 'wpmoo' ), __( 'Text input.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::input( 'demo_input' )
					->label( __( 'Demo Input', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type…', 'wpmoo' ) ) )
					->description( __( 'Saved under the samples option set.', 'wpmoo' ) )
			);

		Moo::section( 'sample_textarea', __( 'Textarea', 'wpmoo' ), __( 'Multiline input field.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::textarea( 'demo_textarea' )
					->label( __( 'Demo Textarea', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type multi-line…', 'wpmoo' ) ) )
					->description( __( 'Saved under the samples option set.', 'wpmoo' ) )
			);

		Moo::section( 'sample_button', __( 'Button', 'wpmoo' ), __( 'Button field type.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::button( 'demo_button' )
					->label( __( 'Run', 'wpmoo' ) )
					->attributes( array( 'class' => 'contrast' ) )
			);

		Moo::section( 'sample_checkbox', __( 'Checkbox', 'wpmoo' ), __( 'Boolean switch.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::checkbox( 'demo_checkbox' )
					->label( __( 'Enable feature', 'wpmoo' ) )
			);

		Moo::section( 'sample_radio', __( 'Radio', 'wpmoo' ), __( 'Single choice options.', 'wpmoo' ) )
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

		Moo::section( 'sample_toggle', __( 'Toggle', 'wpmoo' ), __( 'Boolean toggle (role="switch").', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::toggle( 'demo_toggle' )
					->label( __( 'Enable notifications', 'wpmoo' ) )
			);

		Moo::section( 'sample_range', __( 'Range', 'wpmoo' ), __( 'Range slider.', 'wpmoo' ) )
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
