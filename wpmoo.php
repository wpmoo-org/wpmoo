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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Begins the framework loading process.
 *
 * This file is the primary entry point for the standalone WPMoo plugin.
 * It loads the plugin's core initializer which handles everything else.
 */
require_once __DIR__ . '/src/init.php';
