<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Field\Interfaces\FieldInterface;
use WPMoo\WordPress\Renderers\FieldRendererInterface;
use WPMoo\Field\Type\Input\WordPress\InputRenderer;
use WPMoo\Field\Type\Textarea\WordPress\TextareaRenderer;
use WPMoo\Field\Type\Toggle\WordPress\ToggleRenderer;
use WPMoo\Field\Type\Select\WordPress\SelectRenderer;

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
	 * Registered renderers.
	 *
	 * @var array<string, FieldRendererInterface>
	 */
	private array $renderers = array();

	/**
	 * Constructor.
	 *
	 * @param FrameworkManager $framework_manager The main framework manager.
	 */
	public function __construct( FrameworkManager $framework_manager ) {
		$this->framework_manager = $framework_manager;
		$this->register_default_renderers();
	}

	/**
	 * Register default renderers.
	 */
	private function register_default_renderers(): void {
		$this->register_renderer( 'input', new InputRenderer() );
		$this->register_renderer( 'textarea', new TextareaRenderer() );
		$this->register_renderer( 'toggle', new ToggleRenderer() );
		$this->register_renderer( 'select', new SelectRenderer() );
	}

	/**
	 * Register a renderer for a field type.
	 *
	 * @param string                 $field_type The field type.
	 * @param FieldRendererInterface $renderer The renderer instance.
	 * @return void
	 */
	public function register_renderer( string $field_type, FieldRendererInterface $renderer ): void {
		$this->renderers[ $field_type ] = $renderer;
	}

	/**
	 * Get a renderer for a field type.
	 *
	 * @param string $field_type The field type.
	 * @return FieldRendererInterface|null The renderer instance or null if not found.
	 */
	public function get_renderer( string $field_type ): ?FieldRendererInterface {
		return $this->renderers[ $field_type ] ?? null;
	}

	/**
	 * Get renderer for a field instance.
	 *
	 * @param FieldInterface $field The field instance.
	 * @return FieldRendererInterface|null The renderer instance or null if not found.
	 */
	public function get_renderer_for_field( FieldInterface $field ): ?FieldRendererInterface {
		$field_type = $this->get_field_type( $field );
		return $this->get_renderer( $field_type );
	}

	/**
	 * Determine field type from field instance.
	 *
	 * @param FieldInterface $field The field instance.
	 * @return string The field type.
	 */
	private function get_field_type( FieldInterface $field ): string {
		$field_class = get_class( $field );
		$field_type = strtolower( pathinfo( $field_class, PATHINFO_FILENAME ) );

		// Normalize field type names.
		if ( strpos( $field_class, 'Input' ) !== false ) {
			$field_type = 'input';
		} elseif ( strpos( $field_class, 'Textarea' ) !== false ) {
			$field_type = 'textarea';
		} elseif ( strpos( $field_class, 'Toggle' ) !== false ) {
			$field_type = 'toggle';
		} elseif ( strpos( $field_class, 'Select' ) !== false ) {
			$field_type = 'select';
		}

		return $field_type;
	}

	/**
	 * Render a specific field.
	 *
	 * @param FieldInterface $field       The field to render.
	 * @param string         $unique_slug The unique slug for the page settings.
	 * @param mixed          $value       The current value of the field.
	 * @return string The rendered HTML.
	 */
	public function render_field( FieldInterface $field, string $unique_slug, $value ): string {
		$renderer = $this->get_renderer_for_field( $field );

		if ( $renderer ) {
			return $renderer->render( $field, $unique_slug, $value );
		}

		// Fallback rendering if no specific renderer is found.
		// This ensures something is displayed even if configuration is missing.
		$field_id = $field->get_id();
		$field_name = $unique_slug . '[' . $field_id . ']';
		
		// Safe fallback for methods that might not exist on custom implementations without interface enforcement
		$label = method_exists( $field, 'get_label' ) ? $field->get_label() : $field_id;
		$placeholder = method_exists( $field, 'get_placeholder' ) ? $field->get_placeholder() : '';

		$html = '<div class="field-wrapper" data-field-id="' . esc_attr( $field_id ) . '">';
		if ( ! empty( $label ) ) {
			$html .= '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . '</label>';
		}
		$html .= '<div class="form-group"><input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="wpmoo-input input-group"></div>';
		$html .= '</div>';

		return $html;
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
