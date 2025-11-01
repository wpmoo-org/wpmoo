<?php
/**
 * WPMoo Samples — Core Field: Text
 *
 * @package WPMoo\Samples\Fields
 */

namespace WPMoo\Samples\Fields;

use WPMoo\Fields\FieldBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Minimal options page that showcases the core Text field.
 */
final class Text {
	private const PAGE_ID    = 'wpmoo_samples';
	private const SECTION_ID = 'sample_text';
	private const FIELD_ID   = 'demo_text';

	/**
	 * Hook registration.
	 */
	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	/**
	 * Define the page/section/field using Moo facade + FieldBuilder.
	 */
	public static function define(): void {
		$page = \WPMoo\Moo::container(
			self::PAGE_ID,
			__( 'WPMoo Samples Test', 'wpmoo' ),
			__( 'Examples of core field types for development.', 'wpmoo' )
		);

		$page->menuSlug( 'wpmoo-samples' )
			->icon( 'dashicons-editor-textcolor' )
			->capability( 'manage_options' );

		\WPMoo\Moo::section(
			self::SECTION_ID,
			__( 'Text Field', 'wpmoo' ),
			__( 'A basic text input using the core Text field type.', 'wpmoo' )
		)
			->parent( self::PAGE_ID )
			->fields(
				( new FieldBuilder( self::FIELD_ID, 'text' ) )
					->label( __( 'Demo Text', 'wpmoo' ) )
					->placeholder( __( 'Type something…', 'wpmoo' ) )
					->help( __( 'Saved under the “wpmoo_samples” option.', 'wpmoo' ) )
			);
	}
}
