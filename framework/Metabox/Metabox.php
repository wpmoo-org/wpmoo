<?php

namespace WPMoo\Metabox;

use WPMoo\Metabox\Builders\MetaboxBuilder;

/**
 * Metabox Factory.
 *
 * @package WPMoo\Metabox
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
final class Metabox {
	/**
	 * Create a new MetaboxBuilder instance.
	 *
	 * @param string $id    Metabox ID.
	 * @param string $title Metabox title.
	 * @return MetaboxBuilder
	 */
	public static function build( string $id, string $title ): MetaboxBuilder {
		return new MetaboxBuilder( $id, $title );
	}
}
