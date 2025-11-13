<?php
namespace WPMoo\Samples\Options\Layout;

use WPMoo\Fields\Field;
use WPMoo\Layout\Tabs\Tabs as Component;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Sample tabs layout registrar.
 *
 * This demo illustrates how to switch between grouped fields using tabs within an options page.
 */
final class Tabs {
	public static function register( string $page_id ): void {
		Moo::section( 'sample_tabs', __( 'Tabs', 'wpmoo' ), __( 'Switch between grouped fields.', 'wpmoo' ) )
			->parent( $page_id )
			->options(
				Component::make( 'demo_tabs' )
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

		Moo::section(
			'sample_tabs_vertical',
			__( 'Vertical Tabs', 'wpmoo' ),
			__( 'Navigation is pinned to the left for dense configuration screens.', 'wpmoo' )
		)
			->parent( $page_id )
			->options(
				Component::make( 'demo_tabs_vertical' )
					->vertical()
					->label( __( 'Team preferences', 'wpmoo' ) )
					->items(
						array(
							array(
								'title'       => __( 'Overview', 'wpmoo' ),
								'id'          => 'tab-overview-vertical',
								'description' => __( 'High-level configuration for the module.', 'wpmoo' ),
								'fields'      => array(
									Field::textarea( 'tabs_overview_notes' )
										->label( __( 'Internal notes', 'wpmoo' ) )
										->placeholder( __( 'Describe how this group should behave.', 'wpmoo' ) ),
									Field::toggle( 'tabs_overview_enabled' )
										->label( __( 'Enable module', 'wpmoo' ) ),
								),
							),
							array(
								'title'       => __( 'Members', 'wpmoo' ),
								'id'          => 'tab-members-vertical',
								'description' => __( 'Fine tune who gets access to the settings.', 'wpmoo' ),
								'fields'      => array(
									Field::checkbox( 'tabs_members_admins' )
										->label( __( 'Allow administrators', 'wpmoo' ) ),
									Field::checkbox( 'tabs_members_editors' )
										->label( __( 'Allow editors', 'wpmoo' ) ),
									Field::input( 'tabs_members_custom_role' )
										->label( __( 'Custom role', 'wpmoo' ) )
										->placeholder( __( 'e.g. manage_team_settings', 'wpmoo' ) ),
								),
							),
							array(
								'title'       => __( 'Audit', 'wpmoo' ),
								'id'          => 'tab-audit-vertical',
								'description' => __( 'Log retention and export preferences.', 'wpmoo' ),
								'fields'      => array(
									Field::range( 'tabs_audit_retention' )
										->label( __( 'Retention (days)', 'wpmoo' ) )
										->attributes(
											array(
												'min'  => 7,
												'max'  => 90,
												'step' => 1,
											)
										),
									Field::toggle( 'tabs_audit_export' )
										->label( __( 'Allow CSV export', 'wpmoo' ) )
										->description( __( 'Enable if the audit trail needs to be reviewed externally.', 'wpmoo' ) ),
								),
							),
						)
					)
			);
	}
}
