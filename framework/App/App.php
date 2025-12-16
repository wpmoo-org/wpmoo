<?php

namespace WPMoo\App;

use WPMoo\Core;
use WPMoo\WordPress\Managers\FrameworkManager;
use WPMoo\Page\Builders\PageBuilder; // Corrected use statement
use WPMoo\Layout\Layout;
use WPMoo\Field\Field;
use WPMoo\Shared\Helper\ValidationHelper;

/**
 * Plugin-specific Application instance.
 *
 * Provides a scoped API for a plugin to interact with the shared WPMoo core.
 *
 * @package WPMoo\App
 * @since 0.1.0
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
        // Validate page ID format
        ValidationHelper::validate_id_format($id, 'page');
        
        $page = new PageBuilder($id, $title); // Corrected instantiation
        $this->get_framework_manager()->add_page($page, $this->app_id);
        return $page;
    }
    
    /**
     * Register a custom field type.
     *
     * @param string $type The field type slug.
     * @param string $class The field class name.
     * @return void
     */
    public function register_field_type(string $type, string $class): void {
        Core::instance()->get_field_type_registry()->register_field_type($type, $class);
    }
    
    /**
     * Create a field using the field type registry.
     *
     * @param string $type The field type slug.
     * @param string $id The field ID.
     * @return \WPMoo\Field\Interfaces\FieldInterface|null The field instance or null if type is not registered.
     */
    public function create_field(string $type, string $id) {
        return Core::instance()->get_field_type_registry()->create_field($type, $id);
    }
    
    /**
     * Register a custom layout type.
     *
     * @param string $type The layout type slug.
     * @param string $class The layout class name.
     * @return void
     */
    public function register_layout_type(string $type, string $class): void {
        Core::instance()->get_layout_type_registry()->register_layout_type($type, $class);
    }
    
    /**
     * Create a layout component using the layout type registry.
     *
     * @param string $type The layout type slug.
     * @param string $id The layout ID.
     * @param string $title The layout title (where applicable).
     * @return \WPMoo\Layout\Interfaces\LayoutInterface|null The layout instance or null if type is not registered.
     */
    public function create_layout(string $type, string $id, string $title = '') {
        return Core::instance()->get_layout_type_registry()->create_layout($type, $id, $title);
    }

    /**
     * Create a tabs layout component.
     *
     * @param string $id Tabs ID.
     * @return \WPMoo\Layout\Component\Tabs
     */
    public function tabs(string $id) {
        // Validate layout ID format
        ValidationHelper::validate_id_format($id, 'layout');
        
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
        // Validate field ID format
        ValidationHelper::validate_id_format($id, 'field');
        
        $field = Field::{$type}($id);
        $this->get_framework_manager()->add_field($field, $this->app_id);
        return $field;
    }

    /**
     * Create a layout container.
     *
     * @param string $type Container type (e.g., 'tabs', 'accordion', 'grid', etc.).
     * @param string $id Container ID.
     * @return \WPMoo\Layout\Component\Container
     */
    public function container(string $type, string $id) {
        // Validate container ID format
        ValidationHelper::validate_id_format($id, 'container');
        
        $container = new \WPMoo\Layout\Component\Container($id, $type);
        $this->get_framework_manager()->add_layout($container, $this->app_id);
        return $container;
    }

    /**
     * Create a tab component.
     *
     * @param string $id Tab ID.
     * @param string $title Tab title.
     * @return \WPMoo\Layout\Component\Tab
     */
    public function tab(string $id, string $title) {
        // Validate tab ID format
        ValidationHelper::validate_id_format($id, 'tab');
        
        $tab = new \WPMoo\Layout\Component\Tab($id, $title);
        $this->get_framework_manager()->add_layout($tab, $this->app_id);
        return $tab;
    }

    /**
     * Create an accordion component (individual accordion item within an accordion container).
     *
     * @param string $id Accordion item ID.
     * @param string $title Accordion item title.
     * @return \WPMoo\Layout\Component\Accordion
     */
    public function accordion(string $id, string $title) {
        // Validate accordion ID format
        ValidationHelper::validate_id_format($id, 'accordion');
        
        $accordion = new \WPMoo\Layout\Component\Accordion($id, $title);
        $this->get_framework_manager()->add_layout($accordion, $this->app_id);
        return $accordion;
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