<?php
/**
 * WPMoo Getting Started Page Registration
 *
 * @package WPMoo
 * @since 0.1.0
 */

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
    if ( isset( $_POST['wpmoo_activate_demo'] ) && current_user_can( 'manage_options' ) ) {
        set_transient( 'wpmoo_demo_activated', true, 5 );
        wp_safe_redirect( admin_url( 'tools.php?page=wpmoo-getting-started&activated=true' ) );
        exit;
    }
}
add_action( 'admin_init', 'wpmoo_handle_demo_activation' );

/**
 * Registers the 'Getting Started' page for WPMoo under the WordPress 'Tools' menu.
 *
 * @since 0.1.0
 */
function wpmoo_register_getting_started_page(): void {
    add_submenu_page(
        'tools.php',
        __( 'WPMoo Getting Started', 'wpmoo' ),
        __( 'WPMoo', 'wpmoo' ),
        'manage_options',
        'wpmoo-getting-started',
        'wpmoo_render_getting_started_page'
    );
}
add_action( 'admin_menu', 'wpmoo_register_getting_started_page' );

/**
 * Renders the 'Getting Started' page.
 *
 * @since 0.1.0
 */
function wpmoo_render_getting_started_page(): void {
    if ( get_transient( 'wpmoo_demo_activated' ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'WPMoo demo successfully activated!', 'wpmoo' ); ?></p>
        </div>
        <?php
        delete_transient( 'wpmoo_demo_activated' );
    }
    require_once dirname( __DIR__, 2 ) . '/views/admin/getting-started.php';
}
