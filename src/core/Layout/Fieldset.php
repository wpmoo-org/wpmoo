<?php
/**
 * Fieldset layout facade.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org
 * @link https://github.com/wpmoo/wpmoo
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html
 */

namespace WPMoo\Layout;

use WPMoo\Layout\Fieldset\Builder as FieldsetBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Static helper for Fieldset builder.
 */
class Fieldset {

	/**
	 * Create a new fieldset builder.
	 *
	 * @param string $id Fieldset identifier.
	 * @return FieldsetBuilder
	 */
	public static function make( string $id ): FieldsetBuilder {
		return new FieldsetBuilder( $id );
	}
}
