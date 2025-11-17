<?php

namespace WPMoo;

use WPMoo\Page\Builders\PageBuilder;
use WPMoo\Layout\Layout;  // Main layout facade
use WPMoo\Field\Field;  // Main field facade

/**
 * Main facade for fluent API.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Moo {
	/**
	 * Create a page builder.
	 *
	 * @param string $id Page ID.
	 * @param string $title Page title.
	 * @return PageBuilder
	 */
	public static function page( string $id, string $title ): PageBuilder {
		return new PageBuilder( $id, $title );
	}

	/**
	 * Create a tabs layout component.
	 *
	 * @param string $id Tabs ID.
	 * @return \WPMoo\Layout\Component\Tabs
	 */
	public static function tabs( string $id ) {
		return Layout::tabs( $id );
	}
}
