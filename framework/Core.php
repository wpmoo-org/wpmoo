<?php

namespace WPMoo;

use WPMoo\App\App;
use WPMoo\App\Container;
use WPMoo\Field\FieldTypeRegistry;
use WPMoo\Layout\LayoutTypeRegistry;
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
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
final class Core {
    /**
     * The single, static instance of the Core registry.
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
     * Field type registry for dynamic field type management.
     * @var FieldTypeRegistry
     */
    private FieldTypeRegistry $field_type_registry;
    
    /**
     * Layout type registry for dynamic layout component management.
     * @var LayoutTypeRegistry
     */
    private LayoutTypeRegistry $layout_type_registry;
    
    /**
     * Private constructor to enforce the singleton pattern and setup the container.
     */
    private function __construct() {
        $this->container = new Container();
        $this->field_type_registry = new FieldTypeRegistry();
        $this->layout_type_registry = new LayoutTypeRegistry();
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
     * Initialize the asset enqueuing system.
     * This method should be called during the appropriate WordPress hook.
     *
     * @return void
     */
    public function init_asset_enqueuing(): void {
        $this->container->resolve(PageAssetEnqueuer::class);
    }
    
    /**
     * Get the framework asset enqueuer instance.
     *
     * @return PageAssetEnqueuer
     */
    public function get_asset_enqueuer(): PageAssetEnqueuer {
        return $this->container->resolve(PageAssetEnqueuer::class);
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
    
    /**
     * Get the field type registry.
     *
     * @return FieldTypeRegistry
     */
    public function get_field_type_registry(): FieldTypeRegistry {
        return $this->field_type_registry;
    }
    
    /**
     * Get the layout type registry.
     *
     * @return LayoutTypeRegistry
     */
    public function get_layout_type_registry(): LayoutTypeRegistry {
        return $this->layout_type_registry;
    }
}
