<?php
/**
 * Fluent builder for the Tabs extension.
 *
 * @package WPMoo\Extensions\Tabs
 * @since 0.1.0
 * @link https://wpmoo.org
 * @link https://github.com/wpmoo/wpmoo
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html
 */

namespace WPMoo\Extensions\Tabs;

use WPMoo\Fields\FieldBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Tabs builder wrapper.
 */
class Builder extends FieldBuilder {
	public function __construct( string $id ) {
		parent::__construct( $id, 'tabs' );
		$this->save_field( false );
	}
}
