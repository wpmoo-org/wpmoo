<?php
/**
 * WPMoo Samples — aggregator/registrar for sample demos.
 *
 * @package WPMoo\Samples
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Samples;

use WPMoo\Moo;
use WPMoo\Fields\Field;
use WPMoo\Extensions\Tabs;

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
			// ->fluid()
			->sidebar_nav()
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
					->label( __( 'Accordion field demo', 'wpmoo' ) )
					->label_description( __( 'Group related controls inside collapsible panels.', 'wpmoo' ) )
					->items(
						array(
							array(
								'title'  => __( 'Accordion 1', 'wpmoo' ),
								'open'   => true,
								'fields' => array(
									Field::input( 'demo_accordion_text' )
										->label( __( 'Text', 'wpmoo' ) )
										->default( __( 'Sample value', 'wpmoo' ) ),
									Field::toggle( 'demo_accordion_switch' )
										->label( __( 'Switcher', 'wpmoo' ) ),
									Field::textarea( 'demo_accordion_textarea' )
										->label( __( 'Textarea', 'wpmoo' ) ),
								),
							),
							array(
								'title'  => __( 'Accordion 2', 'wpmoo' ),
								'fields' => array(
									Field::select( 'demo_accordion_select' )
										->label( __( 'Select an option', 'wpmoo' ) )
										->options(
											array(
												'a' => __( 'Option A', 'wpmoo' ),
												'b' => __( 'Option B', 'wpmoo' ),
												'c' => __( 'Option C', 'wpmoo' ),
											)
										),
									Field::button( 'demo_accordion_button' )
										->label( __( 'Action button', 'wpmoo' ) )
										->attributes(
											array(
												'class' => 'contrast',
												'type'  => 'button',
											)
										)
										->save_field( false ),
								),
							),
						)
					)
			);

		Moo::section( 'sample_fieldset', __( 'Fieldset', 'wpmoo' ), __( 'Group fields under sections.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::fieldset( 'demo_fieldset' )
					->label( __( 'Profile configuration', 'wpmoo' ) )
					->items(
						array(
							array(
								'title'       => __( 'Basic info', 'wpmoo' ),
								'description' => __( 'Contact details.', 'wpmoo' ),
								'fields'      => array(
									Field::input( 'fieldset_name' )
										->label( __( 'Full name', 'wpmoo' ) ),
									Field::input( 'fieldset_email' )
										->label( __( 'Email', 'wpmoo' ) )
										->attributes( array( 'type' => 'email' ) ),
								),
							),
							array(
								'title'       => __( 'Preferences', 'wpmoo' ),
								'description' => __( 'Contact details.', 'wpmoo' ),
								'fields'      => array(
									Field::toggle( 'fieldset_newsletter' )
										->label( __( 'Receive newsletter', 'wpmoo' ) ),
									Field::select( 'fieldset_language' )
										->label( __( 'Language', 'wpmoo' ) )
										->options(
											array(
												'en' => __( 'English', 'wpmoo' ),
												'tr' => __( 'Turkish', 'wpmoo' ),
											)
										),
								),
							),
						)
					)
			);

		Moo::section( 'sample_tabs', __( 'Tabs', 'wpmoo' ), __( 'Switch between grouped fields.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Tabs::make( 'demo_tabs' )
					->label( __( 'Tabbed settings', 'wpmoo' ) )
					->items(
						array(
							array(
								'title'       => __( 'Account', 'wpmoo' ),
								'id'          => 'tab-account',
								'type'        => 'tab',
								'icon_type'   => 'dashicons',
								'icon'        => 'dashicons-admin-users',
								'description' => __( 'General account options.', 'wpmoo' ),
								'fields'      => array(
									Field::input( 'tabs_username' )
										->label( __( 'Username', 'wpmoo' ) ),
									Field::toggle( 'tabs_two_factor' )
										->label( __( 'Enable 2FA', 'wpmoo' ) ),
								),
							),
							array(
								'title'       => __( 'Notifications', 'wpmoo' ),
								'id'          => 'tab-notifications',
								'icon_type'   => 'fontawesome',
								'icon'        => 'fas fa-bell',
								'fields'      => array(
									Field::checkbox( 'tabs_email_notifications' )
										->label( __( 'Email alerts', 'wpmoo' ) ),
									Field::checkbox( 'tabs_sms_notifications' )
										->label( __( 'SMS alerts', 'wpmoo' ) ),
								),
							),
							array(
								'title'       => __( 'Display', 'wpmoo' ),
								'id'          => 'tab-display',
								'icon_type'   => 'url',
								'icon'        => plugins_url( 'assets/img/sample-tab-icon.svg', WPMOO_FILE ),
								'fields'      => array(
									Field::select( 'tabs_theme' )
										->label( __( 'Theme', 'wpmoo' ) )
										->options(
											array(
												'light' => __( 'Light', 'wpmoo' ),
												'dark'  => __( 'Dark', 'wpmoo' ),
											)
										),
								),
							),
						)
					)
			);
	}
}
