<?php
/**
 * WPMoo Getting Started Page Registration
 *
 * @package WPMoo
 * @since 0.1.0
 */

use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the WPMoo demo activation process.
 *
 * Checks for a form submission to activate the demo and performs necessary actions.
 *
 * @since 0.1.0
 */
function wpmoo_handle_demo_activation(): void {
    // Check if the form was submitted and the user has the 'manage_options' capability.
    if ( isset( $_POST['wpmoo_activate_demo'] ) && current_user_can( 'manage_options' ) ) {
        // Perform demo activation logic here.
        // For now, let's just set a transient to display a success message.
        set_transient( 'wpmoo_demo_activated', true, 5 ); // Store for 5 seconds.

        // Redirect to prevent form resubmission and clean up the URL.
        wp_safe_redirect( admin_url( 'tools.php?page=wpmoo-getting-started&activated=true' ) );
        exit;
    }
}
add_action( 'admin_init', 'wpmoo_handle_demo_activation' );

/**
 * Registers the 'Getting Started' page for WPMoo under the WordPress 'Tools' menu.
 *
 * This function utilizes the WPMoo Facade to add a new top-level administration
 * page. The content of this page is rendered via a separate view file located
 * in the 'views/admin' directory.
 *
 * @since 0.1.0
 */
function wpmoo_register_getting_started_page(): void {
    Moo::add_page(
        'tools', // Parent slug for the Tools menu.
        'wpmoo-getting-started', // Unique slug for the Getting Started page.
        __( 'WPMoo Getting Started', 'wpmoo' ), // Page title displayed in the browser.
        __( 'Getting Started', 'wpmoo' ), // Menu title displayed in the admin sidebar.
        'manage_options', // Capability required to access this page.
        function() {
            // Check if the demo was just activated.
            if ( get_transient( 'wpmoo_demo_activated' ) ) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'WPMoo demo successfully activated!', 'wpmoo' ); ?></p>
                </div>
                <?php
                delete_transient( 'wpmoo_demo_activated' ); // Delete the transient after displaying.
            }
            // Include the view file for the Getting Started page.
            // The __DIR__ is the current directory (src/admin).
            // dirname(__DIR__, 2) goes up two levels to the plugin root (wpmoo-org/wpmoo/).
            // Then it navigates into the views/admin directory.
            require_once dirname( __DIR__, 2 ) . '/views/admin/getting-started.php';
        }
    );
}

// Hook the page registration into WordPress's 'admin_menu' action.
// The `wpmoo_loaded` action would be too late for registering admin menu pages.
add_action( 'admin_menu', 'wpmoo_register_getting_started_page' );
