<?php
/**
 * Registers the Tabs extension with the WPMoo field manager.
 *
 * @package WPMoo\Extensions\Tabs
 * @since 0.1.0
 */

namespace WPMoo\Extensions\Tabs;

use WPMoo\Fields\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Service provider that wires the tabs field into the manager.
 */
class ServiceProvider {
	/**
	 * Hook the registration callback into the framework.
	 */
	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action(
				'wpmoo_register_field_types',
				static function ( $manager ) {
					if ( $manager instanceof Manager ) {
						$manager->register( 'tabs', Tabs::class );
					}
				}
			);
		}
	}
}
