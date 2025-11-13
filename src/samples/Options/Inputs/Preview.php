<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Sample preview grid section registrar.
 *
 * This demo illustrates how to create a section with a grid layout
 * containing input fields and options.
 */
final class Preview {
	public static function register( string $page_id ): void {
		Moo::section( 'layout_examples', __( 'Preview', 'wpmoo' ) )
			->description( __( 'Sed ultricies dolor non ante vulputate hendrerit. Vivamus sit amet suscipit sapien. Nulla iaculis eros a elit pharetra egestas.', 'wpmoo' ) )
			->parent( $page_id )
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
			->options(
				Field::toggle( 'sample_preview_terms' )
					->label( __( 'I agree to the Privacy Policy', 'wpmoo' ) )
					->description(
						sprintf(
							/* translators: %s: Privacy policy link. */
							__( 'Read the %s', 'wpmoo' ),
							'<a href="#" target="_blank" rel="noreferrer noopener">' . __( 'Privacy Policy', 'wpmoo' ) . '</a>'
						)
					)
			);
	}
}
