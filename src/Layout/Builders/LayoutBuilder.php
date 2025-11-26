<?php

namespace WPMoo\Layout\Builders;

use WPMoo\Layout\Component\Tabs;
use WPMoo\Layout\Component\Accordion;

/**
 * Layout builder.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class LayoutBuilder {
	/**
	 * Create a tabs component.
	 *
	 * @param string $id Tabs ID.
	 * @return Tabs
	 */
	public static function tabs( string $id ): Tabs {
		return new Tabs( $id );
	}

	/**
	 * Create an accordion component.
	 *
	 * @param string $id Accordion ID.
	 * @return Accordion
	 */
	public static function accordion( string $id ): Accordion {
		return new Accordion( $id );
	}
}
