<?php
/**
 * Facade helper for the Tabs extension.
 *
 * @package WPMoo\Extensions
 * @since 0.1.0
 */

namespace WPMoo\Extensions;

use WPMoo\Extensions\Tabs\Builder as TabsBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provide convenient static constructors for Tabs builders.
 */
class Tabs {
	/**
	 * Create a tabs builder instance.
	 *
	 * @param string $id Field identifier.
	 * @return TabsBuilder
	 */
	public static function make( string $id ): TabsBuilder {
		return new TabsBuilder( $id );
	}

	/**
	 * Alias of make(); mirrors other fluent APIs.
	 *
	 * @param string $id Field identifier.
	 * @return TabsBuilder
	 */
	public static function builder( string $id ): TabsBuilder {
		return self::make( $id );
	}
}
