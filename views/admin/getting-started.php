<div class="wrap">
    <h1><?php esc_html_e( 'WPMoo Getting Started', 'wpmoo' ); ?></h1>
    <p><?php esc_html_e( 'Welcome to WPMoo! This page will help you get started with the framework.', 'wpmoo' ); ?></p>

    <h2><?php esc_html_e( 'Documentation', 'wpmoo' ); ?></h2>
    <p><?php printf(
        /* translators: %s: Documentation URL */
        esc_html__( 'Please refer to our %s for detailed information.', 'wpmoo' ),
        '<a href="https://wpmoo.org" target="_blank" rel="noopener noreferrer">' . esc_html__( 'online documentation', 'wpmoo' ) . '</a>'
    ); ?></p>

    <h2><?php esc_html_e( 'Demo Activation', 'wpmoo' ); ?></h2>
    
    <?php if ( get_option( 'wpmoo_demo_active' ) ) : ?>
        <p><?php esc_html_e( 'The demo is currently active. You can deactivate it below.', 'wpmoo' ); ?></p>
        <form method="post" action="" style="display: inline-block; margin-right: 10px;">
            <input type="submit" name="wpmoo_deactivate_demo" class="button" value="<?php esc_attr_e( 'Deactivate Samples', 'wpmoo' ); ?>">
        </form>
        <form method="post" action="" style="display: inline-block;">
            <?php wp_nonce_field( 'wpmoo_clear_demo_data_nonce', 'wpmoo_clear_demo_data_nonce_field' ); ?>
            <input type="submit" name="wpmoo_clear_demo_data" class="button button-secondary" value="<?php esc_attr_e( 'Clear Samples Data', 'wpmoo' ); ?>">
        </form>
    <?php else : ?>
        <p><?php esc_html_e( 'You can activate a demo to see WPMoo in action. This will add some sample data and settings to your WordPress installation.', 'wpmoo' ); ?></p>
        <form method="post" action="">
            <input type="submit" name="wpmoo_activate_demo" class="button button-primary" value="<?php esc_attr_e( 'Activate Demo', 'wpmoo' ); ?>">
        </form>
    <?php endif; ?>
</div>
