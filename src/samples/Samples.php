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
			->menu_slug( self::MENU_SLUG )
			->fluid()
			->sticky_header()
			->ajax_save();
	}

	/**
	 * Define the Sections.
	 *
	 * @return void
	 */
	public static function sections(): void {
		Moo::section( 'layout_examples', __( 'Preview', 'wpmoo' ) )
			->description( __( 'Sed ultricies dolor non ante vulputate hendrerit. Vivamus sit amet suscipit sapien. Nulla iaculis eros a elit pharetra egestas.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->grid(
				Field::input( 'sample_preview_first_name' )
					->label( __( 'First name', 'wpmoo' ) )
					->placeholder( __( 'First name', 'wpmoo' ) )
					->required(),
				Field::input( 'sample_preview_email' )
					->label( __( 'Email address', 'wpmoo' ) )
					->placeholder( __( 'Email address', 'wpmoo' ) )
					->attributes(
						array(
							'type'         => 'email',
							'autocomplete' => 'email',
							'required'     => true,
						)
					)
			)
			->fields(
				Field::toggle( 'sample_preview_terms' )
					->label( __( 'I agree to the Privacy Policy', 'wpmoo' ) )
					->description(
						sprintf(
							/* translators: %s is a link to the privacy policy. */
							__( 'Read the %s', 'wpmoo' ),
							'<a href="#" target="_blank" rel="noreferrer noopener">' . __( 'Privacy Policy', 'wpmoo' ) . '</a>'
						)
					)
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

		Moo::section( 'sample_accordion', __( 'Accordion', 'wpmoo' ), __( 'Display collapsible sections.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::accordion( 'demo_accordion' )
					->label( __( 'Frequently asked questions', 'wpmoo' ) )
					->items(
						array(
							array(
								'summary' => __( 'What is WPMoo?', 'wpmoo' ),
								'content' => '<p>' . esc_html__( 'A lightweight WordPress framework for building elegant admin screens.', 'wpmoo' ) . '</p>',
								'open'    => true,
							),
							array(
								'summary' => __( 'Can I disable notifications?', 'wpmoo' ),
								'content' => '<p>' . esc_html__( 'Yes, toggle the notification setting in the section above.', 'wpmoo' ) . '</p>',
							),
							array(
								'summary' => __( 'Where can I learn more?', 'wpmoo' ),
								'content' => '<p>' . esc_html__( 'Check the README and the wpmoo-docs site for full guides.', 'wpmoo' ) . '</p>',
							),
						)
					)
			);
	}
}
