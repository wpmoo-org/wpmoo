<?php
namespace WPMoo\Samples\Options\Layout;

use WPMoo\Fields\Field;
use WPMoo\Layout\Accordion\Accordion as Component;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Sample accordion layout registrar.
 *
 * This demo illustrates how to display collapsible sections within an options page.
 */
final class Accordion {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_accordion', __( 'Accordion', 'wpmoo' ), __( 'Display collapsible sections.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Component::make( 'demo_accordion' )

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
	}
}
