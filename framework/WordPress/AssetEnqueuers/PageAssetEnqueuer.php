<?php

namespace WPMoo\WordPress\AssetEnqueuers;

/**
 * Asset enqueuer specifically for page-related assets in the WPMoo framework.
 *
 * @package WPMoo\WordPress\AssetEnqueuers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class PageAssetEnqueuer extends AssetEnqueuer {
    
    /**
     * Constructor to hook into WordPress actions for asset enqueuing.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue admin assets for WPMoo pages.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets(string $hook_suffix): void {
        // Only load assets on WPMoo pages
        if (!$this->is_wpmoo_page($hook_suffix)) {
            return;
        }
        
        // Enqueue framework styles
        $this->enqueue_framework_styles();
        
        // Enqueue framework scripts
        $this->enqueue_framework_scripts();
        
        // Localize scripts with translations
        $this->localize_scripts();
    }
    
    /**
     * Check if the current page is a WPMoo page.
     *
     * @param string $hook_suffix The page hook suffix.
     * @return bool True if it's a WPMoo page, false otherwise.
     */
    private function is_wpmoo_page(string $hook_suffix): bool {
        // Check if the hook suffix contains 'wpmoo' to identify WPMoo pages
        return strpos($hook_suffix, 'wpmoo') !== false;
    }
    
    /**
     * Enqueue framework styles.
     *
     * @return void
     */
    private function enqueue_framework_styles(): void {
        // Enqueue main framework styles
        $this->enqueue_style(
            'wpmoo-framework',
            $this->get_asset_url('css/wpmoo.amber.css'),
            array(),
            filemtime($this->get_asset_path('css/wpmoo.amber.css')),
            'all'
        );
        
        // Enqueue PicoCSS if not already loaded
        if (!wp_style_is('pico-css', 'enqueued')) {
            $this->enqueue_style(
                'pico-css',
                $this->get_asset_url('css/pico.min.css'),
                array(),
                '2.0.0',
                'all'
            );
        }
    }
    
    /**
     * Enqueue framework scripts.
     *
     * @return void
     */
    private function enqueue_framework_scripts(): void {
        // Enqueue main framework script
        $this->enqueue_script(
            'wpmoo-framework',
            $this->get_asset_url('js/wpmoo.min.js'),
            array('jquery'),
            filemtime($this->get_asset_path('js/wpmoo.min.js')),
            true
        );
    }
    
    /**
     * Localize scripts with translations.
     *
     * @return void
     */
    private function localize_scripts(): void {
        wp_localize_script(
            'wpmoo-framework',
            'wpmoo_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpmoo_nonce'),
                'translations' => array(
                    'save_successful' => __('Settings saved successfully.', 'wpmoo'),
                    'save_error' => __('There was an error saving settings.', 'wpmoo'),
                    'confirm_reset' => __('Are you sure you want to reset all settings?', 'wpmoo'),
                )
            )
        );
    }
    
    /**
     * Check if the current page has WPMoo components.
     *
     * @return bool True if the page has WPMoo components, false otherwise.
     */
    private function has_wpmoo_components_on_page(): bool {
        // For now, we'll return true if we're on a page that might have WPMoo components
        // In a real implementation, you'd have more sophisticated detection
        return true;
    }
}
