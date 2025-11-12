<?php
namespace WPMoo\Samples\Options\Inputs;

use WPMoo\Fields\Field;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class Toggle {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_toggle', __( 'Toggle', 'wpmoo' ), __( 'Boolean toggle (role="switch").', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Field::toggle( 'demo_toggle' )
					->label( __( 'Enable notifications', 'wpmoo' ) )
			);
	}
}
