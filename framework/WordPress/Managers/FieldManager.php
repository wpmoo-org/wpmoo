<?php

namespace WPMoo\WordPress\Managers;

/**
 * Manages field types in WordPress context.
 *
 * @package WPMoo\WordPress\Managers
 * @since 0.1.0
 */
class FieldManager {
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
	 * Register all fields with WordPress.
	 *
	 * @return void
	 */
	public function register_all(): void {
		// Get all fields from the central framework manager.
		$all_fields_by_plugin = $this->framework_manager->get_fields();

		// Process fields by plugin to maintain isolation.
		foreach ( $all_fields_by_plugin as $plugin_slug => $fields ) {
			$this->register_fields_for_plugin( $plugin_slug, $fields );
		}
	}

	/**
	 * Register fields for a specific plugin.
	 *
	 * @param string                                                $plugin_slug The plugin slug.
	 * @param array<string, \WPMoo\Field\Interfaces\FieldInterface> $fields The fields to register.
	 * @return void
	 */
	private function register_fields_for_plugin( string $plugin_slug, array $fields ): void {
		// Registration logic for fields specific to this plugin.
	}

	/**
	 * Add a field to be registered.
	 *
	 * @deprecated This method is for backward compatibility and should not be used. Use App::field() instead.
	 *
	 * @param object      $field Field instance.
	 * @param string|null $plugin_slug Plugin slug to register the field under.
	 * @return void
	 */
	public function add_field( $field, ?string $plugin_slug = null ): void {
		// Fields are now added via the FrameworkManager.
		$this->framework_manager->add_field( $field, $plugin_slug );
	}
}
