<div class="wrap wpmoo-getting-started">
    <header class="wpmoo-header">
        <h1><?php esc_html_e( 'Welcome to WPMoo!', 'wpmoo' ); ?></h1>
        <p class="pico-muted-text"><?php esc_html_e( 'A lightweight and modern framework for WordPress.', 'wpmoo' ); ?></p>
    </header>

    <div class="grid">
        <article>
            <hgroup>
                <h2><?php esc_html_e( 'Getting Started', 'wpmoo' ); ?></h2>
                <p><?php esc_html_e( 'Here are some resources to help you get started with the WPMoo framework.', 'wpmoo' ); ?></p>
            </hgroup>
            <ul>
                <li>
                    <a href="https://wpmoo.org/docs" target="_blank" rel="noopener noreferrer">
                        <strong><?php esc_html_e( 'Documentation', 'wpmoo' ); ?></strong>
                        <br>
                        <small><?php esc_html_e( 'Explore the official documentation for detailed guides and API references.', 'wpmoo' ); ?></small>
                    </a>
                </li>
                <li>
                    <a href="https://github.com/wpmoo/wpmoo/issues" target="_blank" rel="noopener noreferrer">
                        <strong><?php esc_html_e( 'Support & Issues', 'wpmoo' ); ?></strong>
                        <br>
                        <small><?php esc_html_e( 'Found a bug or have a question? Open an issue on our GitHub repository.', 'wpmoo' ); ?></small>
                    </a>
                </li>
            </ul>
        </article>

        <article>
            <hgroup>
                <h2><?php esc_html_e( 'Demo Activation', 'wpmoo' ); ?></h2>
            </hgroup>
            <?php if ( get_option( 'wpmoo_demo_active' ) ) : ?>
                <p><?php esc_html_e( 'The demo is currently active. You can deactivate it or clear the sample data.', 'wpmoo' ); ?></p>
                <div class="grid">
                    <form method="post" action="">
                        <button type="submit" name="wpmoo_deactivate_demo" class="secondary"><?php esc_html_e( 'Deactivate Samples', 'wpmoo' ); ?></button>
                    </form>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'wpmoo_clear_demo_data_nonce', 'wpmoo_clear_demo_data_nonce_field' ); ?>
                        <button type="submit" name="wpmoo_clear_demo_data" class="contrast"><?php esc_html_e( 'Clear Samples Data', 'wpmoo' ); ?></button>
                    </form>
                </div>
            <?php else : ?>
                <p><?php esc_html_e( 'Activate the demo to see WPMoo in action with sample settings pages and fields.', 'wpmoo' ); ?></p>
                <form method="post" action="">
                    <button type="submit" name="wpmoo_activate_demo"><?php esc_html_e( 'Activate Demo', 'wpmoo' ); ?></button>
                </form>
            <?php endif; ?>
        </article>
    </div>
</div>
<style>
    .wpmoo-getting-started .grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
    }
    .wpmoo-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .wpmoo-getting-started article ul {
        list-style: none;
        padding: 0;
    }
    .wpmoo-getting-started article li {
        margin-bottom: 1rem;
    }
    .wpmoo-getting-started article li a {
        display: block;
        padding: 1rem;
        border: 1px solid var(--pico-muted-border-color);
        border-radius: var(--pico-border-radius);
        text-decoration: none;
    }
    .wpmoo-getting-started article li a:hover {
        background-color: var(--pico-muted-background-color);
    }
</style>
