<?php
/**
 * Backwards-compatible Options Builder alias.
 *
 * Provides `WPMoo\Options\Builder` as a thin extension of `WPMoo\Page\Builder`
 * so projects may import the builder from the Options namespace for clarity.
 *
 * @package WPMoo\Options
 */

namespace WPMoo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Alias for the Page builder in the Options namespace.
 */
class Builder extends \WPMoo\Page\Builder {}
