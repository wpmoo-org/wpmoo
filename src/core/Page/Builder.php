<?php
/**
 * Backwards-compatible alias for the Options builder.
 *
 * @package WPMoo\Page
 * @since 0.1.0
 */

namespace WPMoo\Page;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * @deprecated 0.1.x Use WPMoo\Options\Builder instead.
 */
class Builder extends \WPMoo\Options\Builder {}
