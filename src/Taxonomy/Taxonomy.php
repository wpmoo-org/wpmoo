<?php
/**
 * Fluent taxonomy registration API.
 *
 * @package WPMoo\Taxonomy
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Entry point for building taxonomies.
 */
class Taxonomy {
	/**
	 * Start building a new taxonomy.
	 *
	 * @param string                $taxonomy     Taxonomy slug.
	 * @param string|array<int, string> $object_types Optional. Post type(s) to attach to.
	 * @return Builder
	 */
	public static function register( string $taxonomy, $object_types = null ): Builder {
		$builder = new Builder( $taxonomy );

		if ( ! is_null( $object_types ) ) {
			$object_types = is_string( $object_types ) ? array( $object_types ) : $object_types;
			$builder->attachTo( $object_types );
		}

		return $builder;
	}
}
