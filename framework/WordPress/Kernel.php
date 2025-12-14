<?php

namespace WPMoo\WordPress;

use WPMoo\Core;

/**
 * WPMoo WordPress Kernel.
 *
 * This class is the bridge between the decoupled Core and the WordPress environment.
 * It is responsible for registering all necessary WordPress hooks to make the
 * framework function.
 *
 * @package WPMoo\WordPress
 * @since 0.2.0
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

        // Register PageManager and MetaboxManager hooks on wpmoo_loaded
        // This ensures pages are registered after all plugins have declared them.
        add_action('wpmoo_loaded', [$this, 'register_admin_page_related_hooks'], 20);

        // Resolve the PageAssetEnqueuer from the container. Since it's a singleton,
        // this will create it on the first call and retrieve it on subsequent calls.
        // Its constructor registers the necessary 'admin_enqueue_scripts' hook.
        $container->resolve(\WPMoo\WordPress\AssetEnqueuers\PageAssetEnqueuer::class);
    }
    
    /**
     * Registers WordPress admin menu-related hooks.
     * This method is hooked to `wpmoo_loaded` to ensure pages are registered after all plugins have declared them.
     */
    public function register_admin_page_related_hooks(): void {
        $container = Core::instance()->get_container();
        
        // Resolve managers here to ensure they are the same singletons.
        $pageManager = $container->resolve(\WPMoo\WordPress\Managers\PageManager::class);
        
        $metaboxManager = $container->resolve(\WPMoo\WordPress\Managers\MetaboxManager::class);

        add_action('admin_menu', function() use ($pageManager) {
            $pageManager->register_all();
        });
        
        add_action('add_meta_boxes', function() use ($metaboxManager) {
            $metaboxManager->register_all();
        });
    }
}
