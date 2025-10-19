<?php
/**
 * Fluent taxonomy registration API.
 *
 * @package WPMoo\Taxonomy
 * @since 0.2.0
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
	 * @param string       $taxonomy     Taxonomy slug.
	 * @param string|array $object_types Optional. Post type(s) to attach to.
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
