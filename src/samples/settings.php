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


// Since this file is loaded via the 'wpmoo' plugin's Local Facade (WPMoo\Moo),
// we can make static calls directly. The APP_ID 'wpmoo' is handled by the facade.

// Create a settings page.
Moo::page( 'settings', __( 'WPMoo Settings', 'wpmoo-samples' ) )
->capability( 'manage_options' )
->description( __( 'Configure WPMoo Framework settings', 'wpmoo-samples' ) )
->menu_slug( 'settings' )
->menu_position( 20 )
->menu_icon( 'dashicons-admin-generic' );

// Create tabs container for the settings page.
Moo::container( 'tabs', 'wpmoo_main_tabs' )
	->parent( 'settings' );  // Link to the settings page.

// Create individual tabs
Moo::tab( 'general', __( 'General Settings', 'wpmoo-samples' ) )
	->parent( 'wpmoo_main_tabs' )  // Link to the tabs container
	->fields( array(
		Moo::input( 'site_title' )
			->label( __( 'Site Title', 'wpmoo-samples' ) )
			->placeholder( __( 'Enter your site title', 'wpmoo-samples' ) ),
		Moo::textarea( 'site_description' )
			->label( __( 'Site Description', 'wpmoo-samples' ) )
			->placeholder( __( 'Enter site description', 'wpmoo-samples' ) ),
		Moo::toggle( 'enable_cache' )
		    ->label( __( 'Enable Caching', 'wpmoo-samples' ) ),
) );

// Example of using the custom select field
$custom_fields_tab = Moo::tab( 'custom_fields', __( 'Custom Fields', 'wpmoo-samples' ) )
    ->parent( 'wpmoo_main_tabs' );  // Link to the tabs container

$custom_fields_tab->fields( array(
    Moo::create_field('select', 'preferred_language')
        ->label( __( 'Preferred Language', 'wpmoo-samples' ) )
        ->options( array(
            'en' => __( 'English', 'wpmoo-samples' ),
            'de' => __( 'German', 'wpmoo-samples' ),
            'fr' => __( 'French', 'wpmoo-samples' ),
        ) ),
    Moo::create_field('select', 'user_role')
        ->label( __( 'Default User Role', 'wpmoo-samples' ) )
        ->options( array(
            'subscriber' => __( 'Subscriber', 'wpmoo-samples' ),
            'contributor' => __( 'Contributor', 'wpmoo-samples' ),
            'author' => __( 'Author', 'wpmoo-samples' ),
        ) ),
) );

// Example of using the custom grid layout
$grid_layout = Moo::create_layout('grid', 'feature_grid', __( 'Feature Grid', 'wpmoo-samples' ) );
if ($grid_layout) {
    $grid_layout->columns(3)
        ->parent('settings');  // Link to the settings page
}

Moo::tab( 'advanced', __( 'Advanced Settings', 'wpmoo-samples' ) )
	->parent( 'wpmoo_main_tabs' )  // Link to the tabs container
	->fields( array(
		Moo::input( 'cache_duration' )
			->label( __( 'Cache Duration (seconds)', 'wpmoo-samples' ) )
			->placeholder( __( 'Enter cache duration', 'wpmoo-samples' ) ),
		Moo::toggle( 'enable_debug' )
			->label( __( 'Enable Debug Mode', 'wpmoo-samples' ) ),
	) );

// Also demonstrate accordion container
Moo::container( 'accordion', 'wpmoo_accordion' )
	->parent( 'settings' );  // Link to the settings page.

Moo::accordion( 'acc_general', __( 'General Information', 'wpmoo-samples' ) )
	->parent( 'wpmoo_accordion' )  // Link to the accordion container
	->fields( array(
		Moo::input( 'info_field' )
			->label( __( 'Info Field', 'wpmoo-samples' ) ),
	) );

Moo::accordion( 'acc_help', __( 'Help & Support', 'wpmoo-samples' ) )
	->parent( 'wpmoo_accordion' )  // Link to the accordion container
	->fields( array(
		Moo::textarea( 'support_info' )
			->label( __( 'Support Information', 'wpmoo-samples' ) ),
	) );
