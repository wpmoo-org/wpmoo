<?php
namespace WPMoo\Samples\Options\Layout;

use WPMoo\Fields\Field;
use WPMoo\Layout\Fieldset\Fieldset as Component;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class Fieldset {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_fieldset', __( 'Fieldset', 'wpmoo' ), __( 'Group fields under sections.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Component::make( 'demo_fieldset' )
					->label( __( 'Fieldset demo', 'wpmoo' ) )
					->items(
						array(
							array(
								'label'  => __( 'Profile', 'wpmoo' ),
								'fields' => array(
									Field::input( 'fieldset_name' )
										->label( __( 'Name', 'wpmoo' ) ),
									Field::input( 'fieldset_email' )
										->label( __( 'Email', 'wpmoo' ) )
										->attributes( array( 'type' => 'email' ) ),
								),
							),
							array(
								'label'  => __( 'Preferences', 'wpmoo' ),
								'fields' => array(
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
	}
}
