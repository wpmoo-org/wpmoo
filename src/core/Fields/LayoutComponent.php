<?php
/**
 * Base class for layout-only components (non-persistent fields).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides a BaseField-derived container that is never saved directly.
 */
abstract class LayoutComponent extends BaseField {
	/**
	 * Force layout components to opt out of persistence.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 */
	public function __construct( array $config ) {
		if ( ! isset( $config['save_field'] ) ) {
			$config['save_field'] = false;
		}

		parent::__construct( $config );
	}
}
