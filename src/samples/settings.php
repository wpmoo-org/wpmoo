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
// We can make static calls directly. The APP_ID 'wpmoo' is handled by the facade.

// Create a settings page.
Moo::page( 'settings', __( 'WPMoo Settings', 'wpmoo' ) )
->capability( 'manage_options' )
->description( __( 'Configure WPMoo Framework settings', 'wpmoo' ) )
->menu_slug( 'settings' )
->menu_position( 20 )
->menu_icon( 'dashicons-admin-generic' );

// Create tabs container for the settings page.
Moo::container( 'tabs', 'wpmoo_main_tabs' )
	->parent( 'settings' );  // Link to the settings page.

// Create individual tabs.
Moo::tab( 'general', __( 'General Settings', 'wpmoo' ) )
	->parent( 'wpmoo_main_tabs' )  // Link to the tabs container.
	->fields(
		array(
			Moo::input( 'site_title' )
				->label( __( 'Site Title', 'wpmoo' ) )
				->placeholder( __( 'Enter your site title', 'wpmoo' ) )
				->required(), // New: Required validation.
			Moo::textarea( 'site_description' )
				->label( __( 'Site Description', 'wpmoo' ) )
				->placeholder( __( 'Enter site description', 'wpmoo' ) )
				->rows( 8 ), // New: Custom rows.
			Moo::toggle( 'enable_cache' )
				->label( __( 'Enable Caching', 'wpmoo' ) )
				->on_label( __( 'Active', 'wpmoo' ) ) // New: Custom labels.
				->off_label( __( 'Inactive', 'wpmoo' ) ),
		)
	);

// Example of using the custom select field.
$custom_fields_tab = Moo::tab( 'custom_fields', __( 'Custom Fields', 'wpmoo' ) )
	->parent( 'wpmoo_main_tabs' );  // Link to the tabs container.

$custom_fields_tab->fields(
	array(
		Moo::create_field( 'select', 'preferred_language' )
		->label( __( 'Preferred Language', 'wpmoo' ) )
		->options(
			array(
				'en' => __( 'English', 'wpmoo' ),
				'de' => __( 'German', 'wpmoo' ),
				'fr' => __( 'French', 'wpmoo' ),
			)
		),
		Moo::create_field( 'select', 'allowed_roles' )
		->label( __( 'Allowed Roles (Multiple)', 'wpmoo' ) )
		->multiple() // New: Multiple select support.
		->options(
			array(
				'subscriber' => __( 'Subscriber', 'wpmoo' ),
				'contributor' => __( 'Contributor', 'wpmoo' ),
				'author' => __( 'Author', 'wpmoo' ),
				'editor' => __( 'Editor', 'wpmoo' ),
			)
		),
	)
);

// Example of using the custom grid layout.
$grid_layout = Moo::create_layout( 'grid', 'feature_grid', __( 'Feature Grid', 'wpmoo' ) );
if ( $grid_layout ) {
	$grid_layout->columns( 3 )
		->parent( 'settings' );  // Link to the settings page.
}

Moo::tab( 'advanced', __( 'Advanced Settings', 'wpmoo' ) )
	->parent( 'wpmoo_main_tabs' )  // Link to the tabs container.
	->fields(
		array(
			Moo::input( 'cache_duration' )
				->type( 'number' ) // New: Input type.
				->label( __( 'Cache Duration (seconds)', 'wpmoo' ) )
				->min( 60 ) // New: Min validation.
				->max( 3600 ) // New: Max validation.
				->step( 60 ) // New: Step.
				->default( 300 ), // New: Default value.
			Moo::toggle( 'enable_debug' )
				->label( __( 'Enable Debug Mode', 'wpmoo' ) ),
		)
	);

// Also demonstrate accordion container.
Moo::container( 'accordion', 'wpmoo_accordion' )
	->parent( 'settings' );  // Link to the settings page.

Moo::accordion( 'acc_general', __( 'General Information', 'wpmoo' ) )
	->parent( 'wpmoo_accordion' )  // Link to the accordion container.
	->fields(
		array(
			Moo::input( 'info_field' )
				->label( __( 'Info Field', 'wpmoo' ) ),
		)
	);

Moo::accordion( 'acc_help', __( 'Help & Support', 'wpmoo' ) )
	->parent( 'wpmoo_accordion' )  // Link to the accordion container.
	->fields(
		array(
			Moo::textarea( 'support_info' )
				->label( __( 'Support Information', 'wpmoo' ) )
				->rows( 3 ),
		)
	);
