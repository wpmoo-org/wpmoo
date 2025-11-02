<?php
/**
 * Fluent post type registration API.
 *
 * @package WPMoo\PostType
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Entry point for building and registering custom post types.
 */
class PostType {
	/**
	 * Start building a new post type.
	 *
	 * @param string $type Post type slug.
	 * @return Builder
	 */
	public static function create( string $type ): Builder {
		return new Builder( $type );
	}

	/**
	 * Backward compatible alias of create().
	 *
	 * @param string $type Post type slug.
	 * @return Builder
	 */
	public static function register( string $type ): Builder {
		return self::create( $type );
	}
}
