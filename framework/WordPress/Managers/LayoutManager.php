<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Layout\Component\Tabs;
use WPMoo\Layout\Component\Accordion;

/**
 * Layout manager.
 *
 * @package WPMoo\WordPress\Managers
 * @since 0.1.0
 */
class LayoutManager {
	/**
	 * Registered layouts.
	 *
	 * @var array<string, mixed> Array containing all layout components (Tabs, Accordion, Container, Tab, AccordionItem, etc.)
	 */
	private array $layouts = array();

	/**
	 * The framework manager instance.
	 *
	 * @var FrameworkManager
	 */
	private FrameworkManager $framework_manager;

	/**
	 * Constructor.
	 *
	 * @param FrameworkManager $framework_manager The main framework manager.
	 */
	public function __construct( FrameworkManager $framework_manager ) {
		$this->framework_manager = $framework_manager;
	}

	/**
	 * Add a layout to be registered.
	 *
	 * @param mixed $layout Layout component instance (could be Tabs, Accordion, Container, Tab, AccordionItem, etc.).
	 * @return void
	 */
	public function add_layout( $layout ): void {
		$this->layouts[ $layout->get_id() ] = $layout;
	}

	/**
	 * Register all layouts with WordPress.
	 *
	 * @return void
	 */
	public function register_all(): void {
		// Layouts are typically rendered inline with pages.
		// So this is where we store them for later retrieval.
		// When they're needed by page rendering.
		$registry_layouts = $this->framework_manager->get_layouts();

		// Update our internal layouts array with the registry layouts.
		// The registry now returns layouts grouped by plugin, so we need to flatten the array.
		if ( isset( $registry_layouts[ key( $registry_layouts ) ] ) ) {
			// If the first element is an array, it means we have grouped layouts by plugin.
			$flattened_layouts = array();
			foreach ( $registry_layouts as $plugin_layouts ) {
				if ( is_array( $plugin_layouts ) ) {
					$flattened_layouts = array_merge( $flattened_layouts, $plugin_layouts );
				}
			}
			$this->layouts = array_merge( $this->layouts, $flattened_layouts );
		} else {
			// If not grouped, merge directly.
			$this->layouts = array_merge( $this->layouts, $registry_layouts );
		}

		// For now, we're just retrieving them for potential use.
		// Later, we could tie them to specific pages or sections.
		// Process layouts based on their parent/page relationship.
		foreach ( $this->layouts as $layout ) {
			// Link layouts to their parent pages based on parent property.
			$parent_id = $layout->get_parent();
			if ( ! empty( $parent_id ) ) {
				// This is where we'll implement the connection between layouts and pages.
				// For now, just acknowledge the relationship.
				continue; // Placeholder to avoid empty if error.
			}
		}
	}
}
