<?php
/**
 * Sample Settings Page using WPMoo Framework.
 *
 * This demonstrates the usage of the new WPMoo architecture for creating
 * a settings page with tabs and fields, following internationalization best practices.
 *
 * @package WPMoo
 * @since 0.1.0
 */

use WPMoo\Moo;
use WPMoo\Field\Field;

// Wrap the code in an init action to ensure it runs at the right time
add_action(
	'init',
	function () {
		// Create a settings page
		Moo::page( 'wpmoo_settings', __( 'WPMoo Settings', 'your-text-domain' ) )
		->capability( 'manage_options' )
		->description( __( 'Configure WPMoo Framework settings', 'your-text-domain' ) )
		->menu_slug( 'wpmoo-settings' )
		->menu_position( 20 )
		->menu_icon( 'dashicons-admin-generic' );

		// Create tabs for the settings page
		Moo::tabs( 'wpmoo_main_tabs' )
		->parent( 'wpmoo_settings' )  // Link to the settings page
		->items(
			[
				[
					'id' => 'general',
					'title' => __( 'General Settings', 'your-text-domain' ),
					'content' => [
						Field::input( 'site_title' )
							->label( __( 'Site Title', 'your-text-domain' ) )
							->placeholder( __( 'Enter your site title', 'your-text-domain' ) ),
						Field::textarea( 'site_description' )
							->label( __( 'Site Description', 'your-text-domain' ) )
							->placeholder( __( 'Enter site description', 'your-text-domain' ) ),
						Field::toggle( 'enable_cache' )
							->label( __( 'Enable Caching', 'your-text-domain' ) ),
					],
				],
				[
					'id' => 'advanced',
					'title' => __( 'Advanced Settings', 'your-text-domain' ),
					'content' => [
						Field::input( 'cache_duration' )
							->label( __( 'Cache Duration (seconds)', 'your-text-domain' ) )
							->placeholder( __( 'Enter cache duration', 'your-text-domain' ) ),
						Field::toggle( 'enable_debug' )
							->label( __( 'Enable Debug Mode', 'your-text-domain' ) ),
					],
				],
			]
		);
	}
);
