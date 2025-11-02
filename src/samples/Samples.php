<?php
/**
 * WPMoo Samples — aggregator/registrar for sample demos.
 *
 * @package WPMoo\Samples
 */

namespace WPMoo\Samples;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Central registrar that wires up all sample demos in one place.
 */
final class Samples {
	/**
	 * Register all sample modules.
	 *
	 * Intended to be called once from the framework loader in dev/admin.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Defer to each sample class; they hook into `wpmoo_init` for definition.
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Input' ) ) {
			\WPMoo\Samples\Fields\Input::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Button' ) ) {
			\WPMoo\Samples\Fields\Button::register();
		}
	}
}
