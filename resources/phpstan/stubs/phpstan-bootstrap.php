<?php

/**
 * PHPStan Bootstrap File.
 *
 * This file is loaded by PHPStan before analysis begins.
 * It's used to define constants and global functions that PHPStan
 * wouldn't otherwise know about, preventing false-positive errors.
 *
 * @package WPMoo
 */

// Define constants that are defined in other parts of the application at runtime.
// The actual values do not matter for static analysis, only their existence.
define( 'WPMOO_PATH', '' );
define( 'WPMOO_FILE', '' );
define( 'WPMOO_PLUGIN_LOADED', false );
define( 'WPMOO_URL', '' );
define( 'WPMOO_VERSION', '' );
