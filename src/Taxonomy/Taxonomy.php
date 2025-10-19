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
	 * @param string $taxonomy Taxonomy slug.
	 * @return Builder
	 */
	public static function register( string $taxonomy ): Builder {
		return new Builder( $taxonomy );
	}
}
