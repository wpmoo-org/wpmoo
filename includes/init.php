<?php
/**
 * WPMoo Framework Loader Guard.
 *
 * This file ensures that the WPMoo framework is not loaded more than once.
 * Path and URL constants should be defined by the plugin that includes this file.
 *
 * @package WPMoo
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

// If the main Moo class exists, it means the framework has already been loaded.
// We check without triggering autoloading since autoloading might trigger a circular require.
if ( class_exists( 'WPMoo\\Moo', false ) ) {
	return;
}
