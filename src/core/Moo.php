<?php

namespace WPMoo;

use WPMoo\Page\Builders\PageBuilder;
use WPMoo\Layout\Layout;  // Main layout facade
use WPMoo\Field\Field;  // Main field facade
use WPMoo\Shared\Registry;

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
		$page = new PageBuilder( $id, $title );
		Registry::instance()->add_page( $page );
		return $page;
	}

	/**
	 * Create a tabs layout component.
	 *
	 * @param string $id Tabs ID.
	 * @return \WPMoo\Layout\Component\Tabs
	 */
	public static function tabs( string $id ) {
		$tabs = Layout::tabs( $id );
		Registry::instance()->add_layout( $tabs );
		return $tabs;
	}
}
