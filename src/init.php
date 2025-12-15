<?php
/**
 * Initializes the WPMoo standalone plugin.
 *
 * This file handles loader registration and hooks into the core
 * to load its own features. It acts as a "conductor" in the global scope.
 *
 * @package WPMoo
 */

// 1. Load the shared, immutable loader.
if ( ! function_exists( 'wpmoo_loader' ) ) {
	require_once dirname( __DIR__ ) . '/framework/wpmoo-loader.php';
}

// Load the WPMoo autoloader early so Core and other WPMoo classes are available.
wpmoo_loader( 'load_autoloader', dirname( __DIR__ ) . '/framework' );

// 2. Register this version of the framework with the loader.
wpmoo_loader( 'register', dirname( __DIR__ ) . '/framework/WordPress/boot.php', '0.2.0' );

// 3. Load the Local Facade for this plugin.
require_once __DIR__ . '/Moo.php';

// 4. Hook into the core loaded action to initialize samples.
add_action('init', function() {
    // 4.1. Register this plugin with the FrameworkManager for component tracking.
    // This ensures that its components (pages, fields) can be associated with it.
    \WPMoo\Core::instance()->get_container()->resolve(\WPMoo\WordPress\Managers\FrameworkManager::class)->register_plugin(
        __FILE__, // Plugin's main file path
        \WPMoo\Moo::detect_app_id(),  // Dynamically detected plugin slug
        '0.2.0'   // Plugin version
    );

    // Load sample pages and fields using the WPMoo Local Facade.
    require_once __DIR__ . '/samples/settings.php';
});
