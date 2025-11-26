<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Layout\Component\Tabs;
use WPMoo\Layout\Component\Accordion;

/**
 * Layout manager.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class LayoutManager {
	/**
	 * Registered layouts.
	 *
	 * @var array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	private array $layouts = [];

	/**
	 * Add a layout to be registered.
	 *
	 * @param Tabs|Accordion $layout Layout component instance.
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
		// Layouts are typically rendered inline with pages,
		// so this may be where we store them for later retrieval
		// when they're needed by page rendering
		$registry = \WPMoo\Shared\Registry::instance();
		$registry_layouts = $registry->get_layouts();

		// Update our internal layouts array with the registry layouts
		$this->layouts = array_merge( $this->layouts, $registry_layouts );

		// For now, we're just retrieving them for potential use
		// Later, we could tie them to specific pages or sections
		// Process layouts based on their parent/page relationship
		foreach ( $this->layouts as $layout ) {
			// Link layouts to their parent pages based on parent property
			$parentId = $layout->get_parent();
			if ( ! empty( $parentId ) ) {
				// This is where we'll implement the connection between layouts and pages
				// For now, just acknowledge the relationship
				continue; // Placeholder to avoid empty if error
			}
		}
	}
}
