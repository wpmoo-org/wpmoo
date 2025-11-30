<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Page\Builders\PageBuilder;
use WPMoo\Layout\Component\Tabs;
use WPMoo\Layout\Component\Accordion;

/**
 * Framework manager for handling cross-plugin component registration.
 *
 * @package WPMoo\WordPress\Managers
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class FrameworkManager {
	/**
	 * Registered pages by plugin.
	 *
	 * @var array<string, array<string, PageBuilder>>
	 */
	private array $pages = [];

	/**
	 * Registered layouts by plugin.
	 *
	 * @var array<string, array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>>
	 */
	private array $layouts = [];

	/**
	 * Registered fields by plugin.
	 *
	 * @var array<string, array<string, \WPMoo\Field\Interfaces\FieldInterface>>
	 */
	private array $fields = [];

	/**
	 * Singleton instance.
	 *
	 * @var ?self
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the current plugin slug.
	 *
	 * @return string The current plugin slug, defaults to 'wpmoo' if not determined.
	 */
	private function get_current_plugin_slug(): string {
		// Get the current plugin slug from Bootstrap instances.
		$bootstrap = \WPMoo\WordPress\Bootstrap::instance();
		$instances = $bootstrap->get_instances();

		if ( ! empty( $instances ) ) {
			// Return the first instance's slug since we're in a WordPress hook context.
			$first_instance = reset( $instances );
			return $first_instance['slug'];
		}

		// Default to 'wpmoo' if not determined.
		return 'wpmoo';
	}

	/**
	 * Add a page to the registry.
	 *
	 * @param PageBuilder $page Page builder instance.
	 * @param string|null $plugin_slug Plugin slug to register the page under.
	 * @return void
	 */
	public function add_page( PageBuilder $page, ?string $plugin_slug = null ): void {
		$plugin_slug = $plugin_slug ?? $this->get_current_plugin_slug();

		if ( ! isset( $this->pages[ $plugin_slug ] ) ) {
			$this->pages[ $plugin_slug ] = [];
		}

		$this->pages[ $plugin_slug ][ $page->get_id() ] = $page;
	}

	/**
	 * Get a page by ID.
	 *
	 * @param string $id Page ID.
	 * @param string|null $plugin_slug Plugin slug to search within.
	 * @return PageBuilder|null
	 */
	public function get_page( string $id, ?string $plugin_slug = null ): ?PageBuilder {
		if ( $plugin_slug ) {
			return $this->pages[ $plugin_slug ][ $id ] ?? null;
		}

		// Search across all plugins if no specific plugin specified.
		foreach ( $this->pages as $plugin_pages ) {
			if ( isset( $plugin_pages[ $id ] ) ) {
				return $plugin_pages[ $id ];
			}
		}

		return null;
	}

	/**
	 * Get all pages.
	 *
	 * @param string|null $plugin_slug Plugin slug to get pages from, or null for all.
	 * @return array<string, PageBuilder>|array<string, array<string, PageBuilder>>
	 */
	public function get_pages( ?string $plugin_slug = null ) {
		if ( $plugin_slug ) {
			return $this->pages[ $plugin_slug ] ?? [];
		}

		// Return all pages grouped by plugin.
		return $this->pages;
	}

	/**
	 * Add a layout to the registry.
	 *
	 * @param Tabs|Accordion $layout Layout component instance.
	 * @param string|null $plugin_slug Plugin slug to register the layout under.
	 * @return void
	 */
	public function add_layout( $layout, ?string $plugin_slug = null ): void {
		$plugin_slug = $plugin_slug ?? $this->get_current_plugin_slug();

		if ( ! isset( $this->layouts[ $plugin_slug ] ) ) {
			$this->layouts[ $plugin_slug ] = [];
		}

		$this->layouts[ $plugin_slug ][ $layout->get_id() ] = $layout;
	}

	/**
	 * Get a layout by ID.
	 *
	 * @param string $id Layout ID.
	 * @param string|null $plugin_slug Plugin slug to search within.
	 * @return Tabs|Accordion|null
	 */
	public function get_layout( string $id, ?string $plugin_slug = null ) {
		if ( $plugin_slug ) {
			return $this->layouts[ $plugin_slug ][ $id ] ?? null;
		}

		// Search across all plugins if no specific plugin specified.
		foreach ( $this->layouts as $plugin_layouts ) {
			if ( isset( $plugin_layouts[ $id ] ) ) {
				return $plugin_layouts[ $id ];
			}
		}

		return null;
	}

	/**
	 * Get all layouts.
	 *
	 * @param string|null $plugin_slug Plugin slug to get layouts from, or null for all.
	 * @return array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>|array<string, array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>>
	 */
	public function get_layouts( ?string $plugin_slug = null ) {
		if ( $plugin_slug ) {
			return $this->layouts[ $plugin_slug ] ?? [];
		}

		// Return all layouts grouped by plugin.
		return $this->layouts;
	}

	/**
	 * Get layouts by parent ID.
	 *
	 * @param string $parentId Parent ID to filter by.
	 * @param string|null $plugin_slug Plugin slug to search within.
	 * @return array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	public function get_layouts_by_parent( string $parentId, ?string $plugin_slug = null ): array {
		$layouts = [];

		if ( $plugin_slug ) {
			// Search only in the specified plugin.
			if ( isset( $this->layouts[ $plugin_slug ] ) ) {
				foreach ( $this->layouts[ $plugin_slug ] as $layout ) {
					if ( $layout->get_parent() === $parentId ) {
						$layouts[ $layout->get_id() ] = $layout;
					}
				}
			}
		} else {
			// Search across all plugins.
			foreach ( $this->layouts as $plugin_layouts ) {
				foreach ( $plugin_layouts as $layout ) {
					if ( $layout->get_parent() === $parentId ) {
						$layouts[ $layout->get_id() ] = $layout;
					}
				}
			}
		}

		return $layouts;
	}

	/**
	 * Get all pages for a specific plugin.
	 *
	 * @param string $plugin_slug Plugin slug to get pages for.
	 * @return array<string, PageBuilder>
	 */
	public function get_pages_by_plugin( string $plugin_slug ): array {
		return $this->pages[ $plugin_slug ] ?? [];
	}

	/**
	 * Get all layouts for a specific plugin.
	 *
	 * @param string $plugin_slug Plugin slug to get layouts for.
	 * @return array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	public function get_layouts_by_plugin( string $plugin_slug ): array {
		return $this->layouts[ $plugin_slug ] ?? [];
	}

	/**
	 * Add a field to the registry.
	 *
	 * @param \WPMoo\Field\Interfaces\FieldInterface $field Field instance.
	 * @param string|null $plugin_slug Plugin slug to register the field under.
	 * @return void
	 */
	public function add_field( $field, ?string $plugin_slug = null ): void {
		$plugin_slug = $plugin_slug ?? $this->get_current_plugin_slug();

		if ( ! isset( $this->fields[ $plugin_slug ] ) ) {
			$this->fields[ $plugin_slug ] = [];
		}

		$this->fields[ $plugin_slug ][ $field->get_id() ] = $field;
	}

	/**
	 * Get a field by ID.
	 *
	 * @param string $id Field ID.
	 * @param string|null $plugin_slug Plugin slug to search within.
	 * @return \WPMoo\Field\Interfaces\FieldInterface|null
	 */
	public function get_field( string $id, ?string $plugin_slug = null ) {
		if ( $plugin_slug ) {
			return $this->fields[ $plugin_slug ][ $id ] ?? null;
		}

		// Search across all plugins if no specific plugin specified.
		foreach ( $this->fields as $plugin_fields ) {
			if ( isset( $plugin_fields[ $id ] ) ) {
				return $plugin_fields[ $id ];
			}
		}

		return null;
	}

	/**
	 * Get all fields.
	 *
	 * @param string|null $plugin_slug Plugin slug to get fields from, or null for all.
	 * @return array<string, \WPMoo\Field\Interfaces\FieldInterface>|array<string, array<string, \WPMoo\Field\Interfaces\FieldInterface>>
	 */
	public function get_fields( ?string $plugin_slug = null ) {
		if ( $plugin_slug ) {
			return $this->fields[ $plugin_slug ] ?? [];
		}

		// Return all fields grouped by plugin.
		return $this->fields;
	}

	/**
	 * Get all fields for a specific plugin.
	 *
	 * @param string $plugin_slug Plugin slug to get fields for.
	 * @return array<string, \WPMoo\Field\Interfaces\FieldInterface>
	 */
	public function get_fields_by_plugin( string $plugin_slug ): array {
		return $this->fields[ $plugin_slug ] ?? [];
	}

	/**
	 * Private constructor for singleton pattern.
	 */
	private function __construct() {}
}
