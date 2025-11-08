<?php
/**
 * Shared fluent field builder used across components (Options/Metabox).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 */

namespace WPMoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Component-agnostic field builder.
 *
 * Provides a consistent fluent API that Options and Metabox builders can extend.
 */
class FieldBuilder {

	/**
	 * Field configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $config = array();

	/**
	 * Constructor.
	 *
	 * @param string $id   Field ID.
	 * @param string $type Field type.
	 */
	public function __construct( string $id, string $type ) {
		$this->config = array(
			'id'   => $id,
			'type' => $type,
		);
	}

	/**
	 * Get the field identifier.
	 *
	 * @return string
	 */
	public function id(): string {
		return isset( $this->config['id'] ) ? (string) $this->config['id'] : '';
	}

	/**
	 * Set field label.
	 *
	 * @param string $label Label.
	 * @return $this
	 */
	public function label( string $label ): self {
		$this->config['label'] = $label;
		return $this;
	}

	/**
	 * Set field description.
	 *
	 * @param string $description Description.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->config['description'] = $description;
		return $this;
	}

	/**
	 * Set default value.
	 *
	 * @param mixed $default Default value.
	 * @return $this
	 */
	public function default( $default ): self {
		$this->config['default'] = $default;
		return $this;
	}

	/**
	 * Merge additional HTML attributes (Options) or args (Metabox).
	 *
	 * @param array<string, mixed> $attributes Attributes to merge.
	 * @return $this
	 */
	public function attributes( array $attributes ): self {
		if ( ! isset( $this->config['attributes'] ) ) {
			$this->config['attributes'] = array();
		}
		$this->config['attributes'] = array_merge( $this->config['attributes'], $attributes );

		// Maintain backwards-compatible alias used by Metabox builder.
		if ( ! isset( $this->config['args'] ) ) {
			$this->config['args'] = array();
		}
		$this->config['args'] = array_merge( $this->config['args'], $attributes );

		return $this;
	}

	/**
	 * Back-compat setter for Metabox builder signature.
	 *
	 * @param array<string, mixed> $args Arguments.
	 * @return $this
	 */
	public function args( array $args ): self { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
		return $this->attributes( $args );
	}

	/**
	 * Set placeholder.
	 *
	 * @param string $placeholder Placeholder text.
	 * @return $this
	 */
	public function placeholder( string $placeholder ): self {
		if ( ! isset( $this->config['attributes'] ) ) {
			$this->config['attributes'] = array();
		}
		$this->config['attributes']['placeholder'] = $placeholder;

		// Mirror for args if consumed by a metabox renderer.
		if ( ! isset( $this->config['args'] ) ) {
			$this->config['args'] = array();
		}
		$this->config['args']['placeholder'] = $placeholder;

		return $this;
	}

	/**
	 * Set options for select/radio fields.
	 *
	 * @param array<string, string> $options Options array.
	 * @return $this
	 */
	public function options( array $options ): self {
		$this->config['options'] = $options;
		return $this;
	}

	/**
	 * Define structured items (used by composite fields such as accordion).
	 *
	 * @param array<int, mixed> $items Items configuration.
	 * @return $this
	 */
	public function items( array $items ): self {
		return $this->set( 'items', $items );
	}

	/**
	 * Generic config setter.
	 *
	 * @param string $key   Config key.
	 * @param mixed  $value Config value.
	 * @return $this
	 */
	public function set( string $key, $value ): self {
		$this->config[ $key ] = $value;
		return $this;
	}

	/**
	 * Markup displayed before the field control.
	 *
	 * @param string $markup HTML markup.
	 * @return $this
	 */
	public function before( string $markup ): self {
		$this->config['before'] = $markup;
		return $this;
	}

	/**
	 * Markup displayed after the field control.
	 *
	 * @param string $markup HTML markup.
	 * @return $this
	 */
	public function after( string $markup ): self {
		$this->config['after'] = $markup;
		return $this;
	}

	/**
	 * Helper text rendered beneath the control.
	 *
	 * @param string $markup Helper text (HTML allowed).
	 * @return $this
	 */
	public function help( string $markup ): self {
		$this->config['help'] = $markup;
		return $this;
	}

	/**
	 * Add an input icon (e.g., dashicons classes) and position.
	 * Works with fields that support icons (e.g., Text).
	 *
	 * @param string $class    Icon CSS classes (e.g., 'dashicons dashicons-email').
	 * @param string $position Position: 'left' or 'right'.
	 * @return $this
	 */
	public function icon( string $class, string $position = 'left' ): self {
		if ( ! isset( $this->config['attributes'] ) ) {
			$this->config['attributes'] = array();
		}
		$this->config['attributes']['icon'] = $class;
		$pos                                = strtolower( $position );
		$this->config['attributes']['icon_position'] = in_array( $pos, array( 'left', 'right' ), true ) ? $pos : 'left';
		return $this;
	}

	/**
	 * Description displayed under the label.
	 *
	 * @param string $text Label description.
	 * @return $this
	 */
	public function label_description( string $text ): self {
		$this->config['label_description'] = $text;
		return $this;
	}

	/**
	 * Mark field as required (adds required attribute).
	 *
	 * @param bool $required Required flag.
	 * @return $this
	 */
	public function required( bool $required = true ): self {
		$this->config['required'] = $required;
		return $this;
	}

	/**
	 * Disable the control (adds disabled attribute).
	 *
	 * @param bool $disabled Disabled flag.
	 * @return $this
	 */
	public function disabled( bool $disabled = true ): self {
		$this->config['disabled'] = $disabled;
		return $this;
	}

	/**
	 * Set readonly attribute for the control.
	 *
	 * @param bool $readonly Read-only flag.
	 * @return $this
	 */
	public function read_only( bool $readonly = true ): self { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->config['readonly'] = $readonly;
		return $this;
	}

	/**
	 * Allow multiple values (e.g., multi-select).
	 *
	 * @param bool $multiple Multiple flag.
	 * @return $this
	 */
	public function multiple( bool $multiple = true ): self {
		$this->config['multiple'] = $multiple;
		return $this;
	}

	/**
	 * Apply a custom CSS class to the field wrapper.
	 *
	 * @param string $class CSS class name(s).
	 * @return $this
	 */
	public function css_class( string $class ): self {
		$this->config['css_class'] = $class;
		return $this;
	}

	/**
	 * Provide a custom sanitization callback or 'none' to bypass.
	 *
	 * @param callable|string $callback Callable or the string 'none'.
	 * @return $this
	 */
	public function sanitize_callback( $callback ): self {
		$this->config['sanitize_callback'] = $callback;
		return $this;
	}

	/**
	 * Control whether to persist value on save.
	 *
	 * @param bool $save Save flag.
	 * @return $this
	 */
	public function save_field( bool $save = true ): self {
		$this->config['save_field'] = $save;
		return $this;
	}

	/**
	 * Set validation rules (shape is consumer-defined; e.g., ['pattern' => '/^...$/']).
	 *
	 * @param array<string, mixed> $rules Rule map.
	 * @return $this
	 */
	public function validation( array $rules ): self {
		$this->config['validation'] = $rules;
		return $this;
	}

	/**
	 * Button label for adding repeatable items.
	 *
	 * @param string $text Button text.
	 * @return $this
	 */
	public function add_button( string $text ): self {
		$this->config['add_button'] = $text;
		return $this;
	}

	/**
	 * Prefered naming: repeatable settings API.
	 */
	public function repeatable( bool $repeatable = true ): self {
		$this->config['repeatable'] = $repeatable;
		return $this;
	}

	public function sort_repeatable( bool $enabled = true ): self {
		$this->config['sort_repeatable'] = $enabled;
		return $this;
	}

	public function repeatable_default( bool $enabled = true ): self {
		$this->config['repeatable_default'] = $enabled;
		return $this;
	}

	public function repeatable_as_multiple( bool $enabled = true ): self {
		$this->config['repeatable_as_multiple'] = $enabled;
		return $this;
	}

	public function max_repeatable( int $max ): self {
		$this->config['max_repeatable'] = max( 0, $max );
		return $this;
	}

	public function min_repeatable( int $min ): self {
		$this->config['min_repeatable'] = max( 0, $min );
		return $this;
	}

	/**
	 * Define layout configuration (Options grid helpers).
	 *
	 * @param array<string, mixed> $layout Layout settings.
	 * @return $this
	 */
	public function layout( array $layout ): self {
		if ( ! isset( $this->config['layout'] ) ) {
			$this->config['layout'] = array();
		}
		// Grid helpers removed. Keep as no-op merger for BC.
		$this->config['layout'] = array_merge( $this->config['layout'], $layout );
		return $this;
	}

	/**
	 * Set explicit width percentage (0-100).
	 *
	 * @param int $percentage Width percentage.
	 * @return $this
	 */
	public function width( int $percentage ): self {
		$this->config['width'] = max( 0, min( 100, $percentage ) );
		return $this;
	}

	/**
	 * Define nested fields (used by composite controls).
	 *
	 * @param array<int, mixed> $fields Field definitions.
	 * @return $this
	 */
	public function fields( array $fields ): self {
		return $this->set( 'fields', $fields );
	}

	/**
	 * Build the field configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		return $this->config;
	}
}
