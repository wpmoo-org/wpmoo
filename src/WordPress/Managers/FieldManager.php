<?php

namespace WPMoo\WordPress\Managers;

/**
 * Manages field types in WordPress context.
 *
 * @package WPMoo\Field
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class FieldManager {
	/**
	 * Register all fields with WordPress.
	 *
	 * @return void
	 */
	public function register_all(): void {
		// Get the registry instance to retrieve all fields.
		$registry = FrameworkManager::instance();
		$all_fields_by_plugin = $registry->get_fields();

		// Process fields by plugin to maintain isolation.
		foreach ( $all_fields_by_plugin as $plugin_slug => $fields ) {
			$this->register_fields_for_plugin( $plugin_slug, $fields );
		}
	}

	/**
	 * Register fields for a specific plugin.
	 *
	 * @param string $plugin_slug The plugin slug.
	 * @param array<string, \WPMoo\Field\Interfaces\FieldInterface> $fields The fields to register.
	 * @return void
	 */
	private function register_fields_for_plugin( string $plugin_slug, array $fields ): void {
		// Registration logic for fields specific to this plugin.
		// This could involve connecting fields to specific pages, sections, or options.
		// Currently a placeholder - actual implementation will be added later.
	}

	/**
	 * Add a field to be registered.
	 *
	 * @param object $field Field instance.
	 * @param string|null $plugin_slug Plugin slug to register the field under. If null, use default.
	 * @return void
	 */
	public function add_field( $field, ?string $plugin_slug = null ): void {
		// Fields are now added via the FrameworkManager.
		// This maintains compatibility with existing code if needed.
		$registry = FrameworkManager::instance();
		$registry->add_field( $field, $plugin_slug );
	}
}
