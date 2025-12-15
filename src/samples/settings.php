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

// Wrap the code in an init action to ensure it runs at the right time.
// add_action(
// 	'init',
// 	function () {
		// Since this file is loaded via the 'wpmoo' plugin's Local Facade (WPMoo\Moo),
		// we can make static calls directly. The APP_ID 'wpmoo' is handled by the facade.

		// Create a settings page.
		Moo::page( 'wpmoo_settings', __( 'WPMoo Settings', 'wpmoo-samples' ) )
		->capability( 'manage_options' )
		->description( __( 'Configure WPMoo Framework settings', 'wpmoo-samples' ) )
		->menu_slug( 'wpmoo-settings' )
		->menu_position( 20 )
		->menu_icon( 'dashicons-admin-generic' );

		// Create tabs for the settings page.
		Moo::tabs( 'wpmoo_main_tabs' )
			->parent( 'wpmoo_settings' )  // Link to the settings page.
			->items(
				array(
					array(
						'id' => 'general',
						'title' => __( 'General Settings', 'wpmoo-samples' ),
						'content' => array(
							Moo::input( 'site_title' )
								->label( __( 'Site Title', 'wpmoo-samples' ) )
								->placeholder( __( 'Enter your site title', 'wpmoo-samples' ) ),
							Moo::textarea( 'site_description' )
								->label( __( 'Site Description', 'wpmoo-samples' ) )
								->placeholder( __( 'Enter site description', 'wpmoo-samples' ) ),
							Moo::toggle( 'enable_cache' )
								->label( __( 'Enable Caching', 'wpmoo-samples' ) ),
						),
					),
					array(
						'id' => 'advanced',
						'title' => __( 'Advanced Settings', 'wpmoo-samples' ),
						'content' => array(
							Moo::input( 'cache_duration' )
								->label( __( 'Cache Duration (seconds)', 'wpmoo-samples' ) )
								->placeholder( __( 'Enter cache duration', 'wpmoo-samples' ) ),
							Moo::toggle( 'enable_debug' )
								->label( __( 'Enable Debug Mode', 'wpmoo-samples' ) ),
						),
					),
				)
			);
// 	}
// );
