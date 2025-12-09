<?php
/**
 * Plugin Name: WPMoo Framework
 * Plugin URI: https://wpmoo.org
 * Description: A Simple and Lightweight WordPress Option Framework for Themes and Plugins.
 * Author: WPMoo
 * Author URI: https://wpmoo.org
 * Version: 0.1.0
 * Text Domain: wpmoo
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 *
 * @package WPMoo
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Load the WPMoo framework bootstrap.
 *
 * This file is responsible for setting up constants, autoloading,
 * and initializing the framework.
 */
require_once __DIR__ . '/framework/WordPress/boot.php';
