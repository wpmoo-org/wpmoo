<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Page\Builders\PageBuilder;
use WPMoo\Layout\Component\Tabs;
use WPMoo\Layout\Component\Accordion;
use WPMoo\Shared\Helper\ValidationHelper;
use WPMoo\WordPress\Compatibility\VersionCompatibilityChecker;

/**
 * Framework manager for handling cross-plugin component registration and version loading.
 *
 * @package WPMoo\WordPress\Managers
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class FrameworkManager {
	/**
	 * Registered plugins that use WPMoo.
	 *
	 * @var array<string, array{slug: string, version: string, path: string}>
	 */
	private array $plugins = array();

	/**
	 * Registered pages by plugin.
	 *
	 * @var array<string, array<string, PageBuilder>>
	 */
	private array $pages = array();

	/**
	 * Registered layouts by plugin.
	 *
	 * @var array<string, array<string, mixed>> Array containing all layout components (Tabs, Accordion, Container, Tab, AccordionItem, Fieldset, etc.)
	 */
	private array $layouts = array();

	/**
	 * Registered fields by plugin.
	 *
	 * @var array<string, array<string, \WPMoo\Field\Interfaces\FieldInterface>>
	 */
	private array $fields = array();

	/**
	 * Registered page hooks.
	 *
	 * @var array<string>
	 */
	private array $page_hooks = array();



	/**
	 * Register a plugin that is using the WPMoo framework.
	 *
	 * @param string $slug    The plugin's unique slug.
	 * @param string $version The version of the WPMoo framework the plugin requires.
	 * @param string $path    The full path to the plugin's main file or bootstrap file.
	 * @return void
	 */
	public function register_plugin( string $slug, string $version, string $path ): void {
		try {
			// Validate plugin slug format.
			ValidationHelper::validate_plugin_slug( $slug );

			// Validate version format.
			ValidationHelper::validate_version_format( $version );

			// Validate path exists.
			ValidationHelper::validate_file_path( $path );
		} catch ( \InvalidArgumentException $e ) {
			error_log( 'WPMoo: ' . $e->getMessage() );
			return;
		}

		// Check version compatibility.
		$compatibility_result = VersionCompatibilityChecker::is_compatible( $version, WPMOO_VERSION );

		if ( ! $compatibility_result['compatible'] ) {
			error_log( "WPMoo: Plugin {$slug} requires framework version {$version}, but current version is " . WPMOO_VERSION . '. ' . $compatibility_result['message'] );
		}

		$this->plugins[ $slug ] = array(
			'slug'    => $slug,
			'version' => $version,
			'path'    => $path,
			'compatibility' => $compatibility_result,
		);
	}

	/**
	 * Get all registered plugins
	 *
	 * @return array<string, array{slug: string, version: string, path: string, compatibility: array<string, mixed>}> All registered plugins.
	 */
	public function get_all_registered_plugins(): array {
		return $this->plugins;
	}

	/**
	 * Get the plugin with the highest version of the WPMoo framework.
	 *
	 * @return array{slug: string, version: string, path: string}|null The latest stable plugin.
	 */
	public function get_highest_version_plugin(): ?array {
		if ( empty( $this->plugins ) ) {
			return null;
		}

		$latest_stable_plugin = null;
		foreach ( $this->plugins as $plugin ) {
			if ( null === $latest_stable_plugin || version_compare( $plugin['version'], $latest_stable_plugin['version'], '>' ) ) {
				$latest_stable_plugin = $plugin;
			}
		}

		return $latest_stable_plugin;
	}

	/**
	 * Get all plugins with compatibility issues.
	 *
	 * @return array<string, array{slug: string, version: string, path: string, compatibility: array<string, mixed>}> Plugins with compatibility issues.
	 */
	public function get_incompatible_plugins(): array {
		   $incompatible = array();

		foreach ( $this->plugins as $slug => $plugin ) {
			if ( isset( $plugin['compatibility'] ) && ! $plugin['compatibility']['compatible'] ) {
				$incompatible[ $slug ] = $plugin;
			}
		}

		   return $incompatible;
	}

	/**
	 * Add a page hook to the registry.
	 *
	 * @param string $hook The page hook suffix.
	 * @return void
	 */
	public function add_page_hook( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			$this->page_hooks[] = $hook;
		}
	}

	/**
	 * Get all registered page hooks.
	 *
	 * @return array<string>
	 */
	public function get_all_page_hooks(): array {
		return $this->page_hooks;
	}

	/**
	 * Add a page to the registry.
	 *
	 * @param PageBuilder $page Page builder instance.
	 * @param string      $plugin_slug Plugin slug to register the page under.
	 * @return void
	 */
	public function add_page( PageBuilder $page, string $plugin_slug ): void {
		if ( ! isset( $this->pages[ $plugin_slug ] ) ) {
			$this->pages[ $plugin_slug ] = array();
		}

		$this->pages[ $plugin_slug ][ $page->get_id() ] = $page;
	}

	/**
	 * Get a page by ID.
	 *
	 * @param string      $id Page ID.
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
			return $this->pages[ $plugin_slug ] ?? array();
		}

		// Return all pages grouped by plugin.
		return $this->pages;
	}

	/**
	 * Add a layout to the registry.
	 *
	 * @param mixed  $layout Layout component instance (could be Tabs, Accordion, Container, Tab, AccordionItem, etc.).
	 * @param string $plugin_slug Plugin slug to register the layout under.
	 * @return void
	 */
	public function add_layout( $layout, string $plugin_slug ): void {
		if ( ! isset( $this->layouts[ $plugin_slug ] ) ) {
			$this->layouts[ $plugin_slug ] = array();
		}

		// Add plugin slug as prefix to layout ID to prevent conflicts across plugins.
		$prefixed_id = $plugin_slug . '_' . $layout->get_id();

		$this->layouts[ $plugin_slug ][ $prefixed_id ] = $layout;
	}

	/**
	 * Get a layout by ID.
	 *
	 * @param string      $id Layout ID.
	 * @param string|null $plugin_slug Plugin slug to search within.
	 * @return mixed|null The layout component or null if not found
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
	 * @return array<string, mixed>|array<string, array<string, mixed>> Array containing all layout components
	 */
	public function get_layouts( ?string $plugin_slug = null ) {
		if ( $plugin_slug ) {
			return $this->layouts[ $plugin_slug ] ?? array();
		}

		// Return all layouts grouped by plugin.
		return $this->layouts;
	}

	/**
	 * Get layouts by parent ID.
	 *
	 * @param string      $parent_id Parent ID to filter by.
	 * @param string|null $plugin_slug Plugin slug to search within.
	 * @return array<string, mixed> Array of layout components that have the specified parent
	 */
	public function get_layouts_by_parent( string $parent_id, ?string $plugin_slug = null ): array {
		$layouts = array();

		if ( $plugin_slug ) {
			// Search only in the specified plugin.
			if ( isset( $this->layouts[ $plugin_slug ] ) ) {
				foreach ( $this->layouts[ $plugin_slug ] as $layout ) {
					if ( $layout->get_parent() === $parent_id ) {
						$layouts[ $layout->get_id() ] = $layout;
					}
				}
			}
		} else {
			// Search across all plugins.
			foreach ( $this->layouts as $plugin_layouts ) {
				foreach ( $plugin_layouts as $layout ) {
					if ( $layout->get_parent() === $parent_id ) {
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
		return $this->pages[ $plugin_slug ] ?? array();
	}

	/**
	 * Get all layouts for a specific plugin.
	 *
	 * @param string $plugin_slug Plugin slug to get layouts for.
	 * @return array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	public function get_layouts_by_plugin( string $plugin_slug ): array {
		return $this->layouts[ $plugin_slug ] ?? array();
	}

	/**
	 * Add a field to the registry.
	 *
	 * @param \WPMoo\Field\Interfaces\FieldInterface $field Field instance.
	 * @param string                                 $plugin_slug Plugin slug to register the field under.
	 * @return void
	 */
	public function add_field( $field, string $plugin_slug ): void {
		if ( ! isset( $this->fields[ $plugin_slug ] ) ) {
			$this->fields[ $plugin_slug ] = array();
		}

		$this->fields[ $plugin_slug ][ $field->get_id() ] = $field;
	}

	/**
	 * Get a field by ID.
	 *
	 * @param string      $id Field ID.
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
			return $this->fields[ $plugin_slug ] ?? array();
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
		return $this->fields[ $plugin_slug ] ?? array();
	}

	/**
	 * Get fields by page ID (placeholder method - needs implementation based on your structure).
	 *
	 * @param string $page_id Page ID to get fields for.
	 * @return array<string, \WPMoo\Field\Interfaces\FieldInterface> Array of fields associated with the page.
	 */
	public function get_fields_by_page( string $page_id ): array {
		// This is a placeholder implementation. In a real scenario, you'd need to track
		// which fields belong to which page through the layout structure.
		// For now, we'll return an empty array.
		return array();
	}

	/**
	 * Public constructor.
	 * The lifecycle of this class is managed by the DI container.
	 */
	public function __construct() {}
}
