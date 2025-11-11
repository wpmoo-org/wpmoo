<?php
/**
 * Tabs layout facade.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org
 * @link https://github.com/wpmoo/wpmoo
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html
 */

namespace WPMoo\Layout;

use WPMoo\Layout\Tabs\Builder as TabsBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Static helper for creating Tabs builders.
 */
class Tabs {

	/**
	 * Create a tabs builder instance.
	 *
	 * @param string $id Identifier.
	 * @return TabsBuilder
	 */
	public static function make( string $id ): TabsBuilder {
		return new TabsBuilder( $id );
	}
}
