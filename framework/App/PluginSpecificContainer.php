<?php

namespace WPMoo\App;

/**
 * Plugin-specific container that extends the base container with plugin-specific configurations.
 *
 * @package WPMoo\App
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class PluginSpecificContainer extends Container {
    /**
     * The plugin ID for this container instance.
     *
     * @var string
     */
    private string $plugin_id;

    /**
     * The parent container to fall back to for services not defined in this container.
     *
     * @var Container
     */
    private Container $parent_container;

    /**
     * Constructor.
     *
     * @param string $plugin_id The plugin ID for this container.
     * @param Container $parent_container The parent container to fall back to.
     */
    public function __construct(string $plugin_id, Container $parent_container) {
        $this->plugin_id = $plugin_id;
        $this->parent_container = $parent_container;
        // The parent constructor will be called automatically if it exists
        // Since the base Container class doesn't define a constructor, we don't need to explicitly call parent::__construct()
    }

    /**
     * Resolve a service from the container.
     * First tries to resolve from this plugin-specific container,
     * then falls back to the parent container if not found.
     *
     * @param string $service_name Service identifier.
     * @return mixed Resolved service instance.
     * @throws \InvalidArgumentException When service cannot be resolved.
     */
    public function resolve(string $service_name) {
        // First check if the service is bound in this plugin-specific container
        if ($this->has_local_binding($service_name)) {
            return parent::resolve($service_name);
        }

        // If not found locally, try to resolve from the parent container
        if ($this->parent_container->has($service_name)) {
            return $this->parent_container->resolve($service_name);
        }

        // If still not found, try to resolve with the local container as fallback
        return parent::resolve($service_name);
    }

    /**
     * Check if a service is bound in this container specifically (not in parent).
     *
     * @param string $service_name Service identifier.
     * @return bool True if service exists in this container, false otherwise.
     */
    public function has_local_binding(string $service_name): bool {
        return isset($this->bindings[$service_name]) || isset($this->instances[$service_name]);
    }

    /**
     * Check if a service is available in this container (either local or parent).
     *
     * @param string $service_name Service identifier.
     * @return bool True if service exists in this container or parent, false otherwise.
     */
    public function has(string $service_name): bool {
        return $this->has_local_binding($service_name) || $this->parent_container->has($service_name);
    }

    /**
     * Get the plugin ID for this container.
     *
     * @return string
     */
    public function get_plugin_id(): string {
        return $this->plugin_id;
    }

    /**
     * Get the parent container.
     *
     * @return Container
     */
    public function get_parent_container(): Container {
        return $this->parent_container;
    }
}