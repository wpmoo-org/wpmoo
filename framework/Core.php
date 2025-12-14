<?php

namespace WPMoo;

use WPMoo\App\App;
use WPMoo\App\Container;

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
        // We can register framework-internal, non-WP services here if needed.
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