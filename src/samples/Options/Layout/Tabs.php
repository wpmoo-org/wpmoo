<?php
namespace WPMoo\Samples\Options\Layout;

use WPMoo\Fields\Field;
use WPMoo\Layout\Tabs\Tabs as Component;
use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

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
	}
}
