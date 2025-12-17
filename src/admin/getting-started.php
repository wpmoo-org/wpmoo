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
 * Handles the WPMoo demo activation and deactivation process.
 *
 * Checks for form submissions and sets the demo status accordingly.
 *
 * @since 0.1.0
 */
function wpmoo_handle_demo_actions(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle activation.
    if ( isset( $_POST['wpmoo_activate_demo'] ) ) {
        update_option( 'wpmoo_demo_active', true );
        wp_safe_redirect( admin_url( 'tools.php?page=wpmoo-getting-started&activated=true' ) );
        exit;
    }

    // Handle deactivation.
    if ( isset( $_POST['wpmoo_deactivate_demo'] ) ) {
        update_option( 'wpmoo_demo_active', false );
        wp_safe_redirect( admin_url( 'tools.php?page=wpmoo-getting-started&deactivated=true' ) );
        exit;
    }

    // Handle clearing demo data.
    if ( isset( $_POST['wpmoo_clear_demo_data'] ) ) {
        if ( ! isset( $_POST['wpmoo_clear_demo_data_nonce_field'] ) || ! wp_verify_nonce( $_POST['wpmoo_clear_demo_data_nonce_field'], 'wpmoo_clear_demo_data_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        // Delete the sample settings option.
        delete_option( 'wpmoo_settings' );
        
        // Deactivate the demo.
        update_option( 'wpmoo_demo_active', false );
        
        wp_safe_redirect( admin_url( 'tools.php?page=wpmoo-getting-started&cleared=true' ) );
        exit;
    }
}
add_action( 'admin_init', 'wpmoo_handle_demo_actions' );


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
    // Show activation success message.
    if ( isset( $_GET['activated'] ) && $_GET['activated'] === 'true' ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'WPMoo demo successfully activated!', 'wpmoo' ); ?></p>
        </div>
        <?php
    }

    // Show deactivation success message.
    if ( isset( $_GET['deactivated'] ) && $_GET['deactivated'] === 'true' ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'WPMoo demo successfully deactivated!', 'wpmoo' ); ?></p>
        </div>
        <?php
    }

    // Show data cleared success message.
    if ( isset( $_GET['cleared'] ) && $_GET['cleared'] === 'true' ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'WPMoo demo data successfully cleared!', 'wpmoo' ); ?></p>
        </div>
        <?php
    }

    require_once dirname( __DIR__, 2 ) . '/views/admin/getting-started.php';
}
