<?php

namespace WPMoo\WordPress;

use WPMoo\Core;
use WPMoo\WordPress\Managers\FrameworkManager;
use WPMoo\WordPress\Managers\PageManager;

/**
 * WPMoo WordPress Kernel.
 *
 * This class is the bridge between the decoupled Core and the WordPress environment.
 * It is responsible for registering all necessary WordPress hooks to make the
 * framework function.
 *
 * @package WPMoo\WordPress
 * @since 0.1.0
 */
final class Kernel {
    /**
     * Singleton instance.
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Boot status flag.
     * @var bool
     */
    private bool $booted = false;

    /**
     * Get singleton instance.
     */
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        // Services are now registered in WPMoo\Core.
    }
    
    /**
     * Main boot method. Registers all WordPress hooks.
     */
    public function boot(): void {
        if ($this->booted) {
            return;
        }

        $this->register_hooks();
        $this->booted = true;
    }
    
    /**
     * Register WordPress hooks.
     */
    private function register_hooks(): void {
        $container = Core::instance()->get_container();

        add_action('init', function() use ($container) {
            $container->resolve(\WPMoo\WordPress\Managers\FieldManager::class)->register_all();
            $container->resolve(\WPMoo\WordPress\Managers\LayoutManager::class)->register_all();
        }, 15);

        // Register settings groups on admin_init.
        add_action('admin_init', [$this, 'register_settings_groups']);

        // Register PageManager and MetaboxManager hooks on wpmoo_loaded
        // This ensures pages are registered after all plugins have declared them.
        add_action('wpmoo_loaded', [$this, 'register_admin_page_related_hooks'], 20);

        // Resolve the PageAssetEnqueuer from the container. Since it's a singleton,
        // this will create it on the first call and retrieve it on subsequent calls.
        // Its constructor registers the necessary 'admin_enqueue_scripts' hook.
        $container->resolve(\WPMoo\WordPress\AssetEnqueuers\PageAssetEnqueuer::class);
    }

    /**
     * Registers all unique settings groups with WordPress.
     * This is necessary to whitelist the options so they can be saved.
     */
    public function register_settings_groups(): void {
        $container = Core::instance()->get_container();
        $frameworkManager = $container->resolve(FrameworkManager::class);
        $all_pages_by_plugin = $frameworkManager->get_pages();

        foreach ( $all_pages_by_plugin as $plugin_slug => $pages ) {
            foreach ( $pages as $page ) {
                $unique_slug = PageManager::get_unique_slug($plugin_slug, $page->get_menu_slug());
                register_setting( $unique_slug, $unique_slug );
            }
        }
    }
    
    /**
     * Registers WordPress admin menu-related hooks.
     * This method is hooked to `wpmoo_loaded` to ensure pages are registered after all plugins have declared them.
     */
    public function register_admin_page_related_hooks(): void {
        $container = Core::instance()->get_container();
        
        // Resolve managers here to ensure they are the same singletons.
        $pageManager = $container->resolve(PageManager::class);
        
        $metaboxManager = $container->resolve(\WPMoo\WordPress\Managers\MetaboxManager::class);

        add_action('admin_menu', function() use ($pageManager) {
            $pageManager->register_all();
        });
        
        add_action('add_meta_boxes', function() use ($metaboxManager) {
            $metaboxManager->register_all();
        });
    }
}
