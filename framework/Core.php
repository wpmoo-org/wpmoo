<?php

namespace WPMoo;

use WPMoo\App\App;
use WPMoo\App\Container;
use WPMoo\WordPress\AssetEnqueuers\PageAssetEnqueuer;
use WPMoo\WordPress\Managers\FieldManager;
use WPMoo\WordPress\Managers\FrameworkManager;
use WPMoo\WordPress\Managers\LayoutManager;
use WPMoo\WordPress\Managers\MetaboxManager;
use WPMoo\WordPress\Managers\PageManager;

/**
 * WPMoo Core Registry & Factory.
 *
 * This class acts as the central registry and factory for creating plugin-specific
 * App instances. It also holds the master DI container. It is the only Singleton
 * in the framework and remains completely decoupled from WordPress.
 *
 * @package WPMoo
 * @since 0.2.0
 */
final class Core {
    /**
     * The single, static instance of the Core class.
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Holds all registered App instances, keyed by their unique App ID.
     * @var App[]
     */
    private array $apps = [];

    /**
     * The master dependency injection container.
     * @var Container
     */
    private Container $container;

    /**
     * Private constructor to enforce the singleton pattern and setup the container.
     */
    private function __construct() {
        $this->container = new Container();
        $this->register_services(); // Call register_services here
    }

    /**
     * Registers all core services in the container.
     * This method is called once when the Core singleton is instantiated.
     */
    private function register_services(): void {
        // Register all managers as singletons.
        $this->container->singleton(FrameworkManager::class);
        $this->container->singleton(FieldManager::class);
        $this->container->singleton(PageManager::class);
        $this->container->singleton(LayoutManager::class);
        $this->container->singleton(MetaboxManager::class);
        
        // Register asset enqueuers as singletons.
        $this->container->singleton(PageAssetEnqueuer::class);
    }

    /**
     * Get the singleton instance of the Core registry.
     *
     * @return self
     */
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gets or creates a plugin-specific App instance.
     *
     * @param string $app_id A unique identifier for the App instance (e.g., 'my-shop').
     * @return App The App instance for the given ID.
     */
    public static function get(string $app_id): App {
        $core = self::instance();

        if (empty($app_id)) {
            throw new \InvalidArgumentException('App ID cannot be empty.');
        }

        if (!isset($core->apps[$app_id])) {
            // Pass the container to the App instance so it can resolve managers.
            $core->apps[$app_id] = new App($app_id, $core->get_container());
        }

        return $core->apps[$app_id];
    }

    /**
     * Provides access to the master DI container.
     *
     * @return Container
     */
    public function get_container(): Container {
        return $this->container;
    }
}