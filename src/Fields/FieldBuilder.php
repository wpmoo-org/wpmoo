<?php
/**
 * Shared fluent field builder used across components (Options/Metabox).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 */

namespace WPMoo\Fields;

use WPMoo\Support\Concerns\HasColumns;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Component-agnostic field builder.
 *
 * Provides a consistent fluent API that Options and Metabox builders can extend.
 */
class FieldBuilder {
	use HasColumns;

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
	 * Description displayed under the label.
	 *
	 * @param string $text Label description.
	 * @return $this
	 */
	public function labelDescription( string $text ): self {
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
	public function readOnly( bool $readonly = true ): self { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
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
	public function cssClass( string $class ): self {
		$this->config['css_class'] = $class;
		return $this;
	}

	/**
	 * Provide a custom sanitization callback or 'none' to bypass.
	 *
	 * @param callable|string $callback Callable or the string 'none'.
	 * @return $this
	 */
	public function sanitizeCallback( $callback ): self {
		$this->config['sanitize_callback'] = $callback;
		return $this;
	}

	/**
	 * Control whether to persist value on save.
	 *
	 * @param bool $save Save flag.
	 * @return $this
	 */
	public function saveField( bool $save = true ): self {
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
	public function addButton( string $text ): self {
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

	public function sortRepeatable( bool $enabled = true ): self {
		$this->config['sort_repeatable'] = $enabled;
		return $this;
	}

	public function repeatableDefault( bool $enabled = true ): self {
		$this->config['repeatable_default'] = $enabled;
		return $this;
	}

	public function repeatableAsMultiple( bool $enabled = true ): self {
		$this->config['repeatable_as_multiple'] = $enabled;
		return $this;
	}

	public function maxRepeatable( int $max ): self {
		$this->config['max_repeatable'] = max( 0, $max );
		return $this;
	}

	public function minRepeatable( int $min ): self {
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

		if ( isset( $layout['size'] ) && ! isset( $layout['columns'] ) ) {
			$layout['columns'] = array(
				'default' => $this->clampColumnSpan( $layout['size'] ),
			);
		}

		$this->config['layout'] = array_merge( $this->config['layout'], $layout );

		if ( isset( $this->config['layout']['columns']['default'] ) ) {
			$span = $this->clampColumnSpan( $this->config['layout']['columns']['default'] );
			if ( null !== $span ) {
				$this->config['width'] = (int) round( ( $span / 12 ) * 100 );
			}
		} elseif ( isset( $this->config['layout']['size'] ) ) {
			$span = $this->clampColumnSpan( $this->config['layout']['size'] );
			if ( null !== $span ) {
				$this->config['width'] = (int) round( ( $span / 12 ) * 100 );
			}
		}

		return $this;
	}

	/**
	 * Set explicit width percentage (0-100).
	 *
	 * @param int $percentage Width percentage.
	 * @return $this
	 */
	public function width( int $percentage ): self {
		$percentage            = max( 0, min( 100, $percentage ) );
		$this->config['width'] = $percentage;
		return $this;
	}

	/**
	 * Set grid column span(s).
	 *
	 * @param mixed ...$columns Column definitions (int, string, array).
	 * @return $this
	 */
	public function size( ...$columns ): self {
		$parsed = $this->parseColumnSpans( $columns );
		$this->layout(
			array(
				'size'    => $parsed['default'],
				'columns' => $parsed,
			)
		);
		$width = (int) round( ( $parsed['default'] / 12 ) * 100 );
		return $this->width( $width );
	}

	/**
	 * Alias for size().
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function columns( ...$columns ): self {
		return $this->size( ...$columns );
	}

	/**
	 * Set preferred gutter size for grid-based controls.
	 *
	 * @param string $gutter Gutter keyword (sm, md, lg, xl, none).
	 * @return $this
	 */
	public function gutter( string $gutter ): self {
		return $this->layout( array( 'gutter' => $gutter ) );
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
