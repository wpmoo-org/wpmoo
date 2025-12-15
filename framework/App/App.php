<?php

namespace WPMoo\App;

use WPMoo\WordPress\Managers\FrameworkManager;
use WPMoo\Page\Builders\PageBuilder; // Corrected use statement
use WPMoo\Layout\Layout;
use WPMoo\Field\Field;

/**
 * Plugin-specific Application instance.
 *
 * Provides a scoped API for a plugin to interact with the shared WPMoo core.
 *
 * @package WPMoo\App
 * @since 0.2.0
 */
class App {
    /**
     * The unique ID of this application instance.
     * @var string
     */
    private string $app_id;

    /**
     * The shared DI container.
     * @var Container
     */
    private Container $container;

    /**
     * Constructor.
     *
     * @param string    $app_id    The unique ID for this app instance.
     * @param Container $container The shared DI container from the core.
     */
    public function __construct(string $app_id, Container $container) {
        $this->app_id = $app_id;
        $this->container = $container;
    }

    /**
     * Create a page builder.
     *
     * @param string $id Page ID.
     * @param string $title Page title.
     * @return PageBuilder // Corrected return type
     */
    public function page(string $id, string $title): PageBuilder {
        $page = new PageBuilder($id, $title); // Corrected instantiation
        $this->get_framework_manager()->add_page($page, $this->app_id);
        return $page;
    }

    /**
     * Create a tabs layout component.
     *
     * @param string $id Tabs ID.
     * @return \WPMoo\Layout\Component\Tabs
     */
    public function tabs(string $id) {
        $tabs = Layout::tabs($id);
        $this->get_framework_manager()->add_layout($tabs, $this->app_id);
        return $tabs;
    }


    /**
     * Create a field.
     *
     * @param string $type Field type.
     * @param string $id Field ID.
     * @return \WPMoo\Field\Interfaces\FieldInterface
     */
    public function field(string $type, string $id) {
        $field = Field::{$type}($id);
        $this->get_framework_manager()->add_field($field, $this->app_id);
        return $field;
    }

    /**
     * Gets the ID for the current app instance.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->app_id;
    }
    
    /**
     * Resolves the FrameworkManager from the container.
     *
     * @return FrameworkManager
     */
    private function get_framework_manager(): FrameworkManager {
        return $this->container->resolve(FrameworkManager::class);
    }
}