<?php
/**
 * Backwards-compatible Options Builder alias.
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Alias for the Page builder in the Options namespace.
 * Provides `WPMoo\Options\Builder` as a thin extension of `WPMoo\Page\Builder`
 * so projects may import the builder from the Options namespace for clarity.
 */
class Builder extends \WPMoo\Page\Builder {}
