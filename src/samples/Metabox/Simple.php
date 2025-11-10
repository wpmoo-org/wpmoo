<?php
/**
 * WPMoo Samples — Simple Metabox demo.
 *
 * @package WPMoo\Samples
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Samples\Metabox;

use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Registers a minimal metabox using the grid-friendly renderer.
 */
final class Simple {
	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'define' ) );
		}
	}

	public static function define(): void {
		Moo::metabox( 'wpmoo_sample_meta', __( 'WPMoo Sample Meta', 'wpmoo' ) )
			->screens( array( 'post' ) )
			->context( 'normal' )
			->fields(
				Moo::Field( 'input', 'meta_demo' )
					->label( __( 'Demo Meta', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Type…', 'wpmoo' ) ) )
			);
	}
}
