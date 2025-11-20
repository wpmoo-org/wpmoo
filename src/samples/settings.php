<?php
/**
 * Sample Settings Page using WPMoo Framework.
 *
 * This demonstrates the usage of the new WPMoo architecture for creating
 * a settings page with tabs and fields.
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
		Moo::page( 'wpmoo_settings', 'WPMoo Settings' )
		->capability( 'manage_options' )
		->description( 'Configure WPMoo Framework settings' )
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
					'title' => 'General Settings',
					'content' => [
						Field::input( 'site_title' )
							->label( 'Site Title' )
							->placeholder( 'Enter your site title' ),
						Field::textarea( 'site_description' )
							->label( 'Site Description' )
							->placeholder( 'Enter site description' ),
						Field::toggle( 'enable_cache' )
							->label( 'Enable Caching' ),
					],
				],
				[
					'id' => 'advanced',
					'title' => 'Advanced Settings',
					'content' => [
						Field::input( 'cache_duration' )
							->label( 'Cache Duration (seconds)' )
							->placeholder( 'Enter cache duration' ),
						Field::toggle( 'enable_debug' )
							->label( 'Enable Debug Mode' ),
					],
				],
			]
		);
	}
);
