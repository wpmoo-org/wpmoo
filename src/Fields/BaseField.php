<?php
/**
 * Base field implementation used across WPMoo components.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields;

use WPMoo\Support\Concerns\EscapesOutput;
use WPMoo\Support\Concerns\HasColumns;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides shared functionality for field types.
 */
abstract class BaseField {
	use EscapesOutput;
	use HasColumns;

	/**
	 * Field identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Field type key.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Human readable label.
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Field description text.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Default value.
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Markup displayed before the field control.
	 *
	 * @var string
	 */
	protected $before = '';

	/**
	 * Markup displayed after the field control.
	 *
	 * @var string
	 */
	protected $after = '';

	/**
	 * Helper text displayed beneath the control.
	 *
	 * @var string
	 */
	protected $help = '';

	/**
	 * Description displayed under the field label.
	 *
	 * @var string
	 */
	protected $label_description = '';

	/**
	 * Additional HTML attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = array();

	/**
	 * Backwards compatible array of attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected $args = array();

	/**
	 * Declared width percentage (0-100).
	 *
	 * @var int
	 */
	protected $width = 0;

	/**
	 * Layout configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $layout = array(
		'size'    => 12,
		'columns' => array(
			'default' => 12,
		),
	);

	/**
	 * Common boolean flags.
	 *
	 * @var bool
	 */
	protected $required = false;

	/**
	 * Whether the control is disabled.
	 *
	 * @var bool
	 */
	protected $disabled = false;

	/**
	 * Whether the control is read-only.
	 *
	 * @var bool
	 */
	protected $readonly = false;

	/**
	 * Whether the control accepts multiple values.
	 *
	 * @var bool
	 */
	protected $multiple = false;

	/**
	 * Optional custom CSS class applied to wrapper.
	 *
	 * Note: Using css_class to avoid conflict with 'class' used for PHP class name.
	 *
	 * @var string
	 */
	protected $css_class = '';

	/**
	 * Optional custom sanitization callback. If set to string 'none', bypass sanitization.
	 *
	 * @var callable|string|null
	 */
	protected $sanitize_callback = null;

	/**
	 * Whether to persist the value on save operations.
	 *
	 * @var bool
	 */
	protected $save_field = true;

	/**
	 * Validation rule map (shape decided by consumers).
	 *
	 * @var array<string, mixed>
	 */
	protected $validation = array();

	/**
	 * Repeatable options (not yet wired to UI logic).
	 *
	 * @var bool
	 */
	protected $repeatable = false;

	/**
	 * Whether repeatable items can be sorted.
	 *
	 * @var bool
	 */
	protected $sort_repeatable = false;

	/**
	 * Whether to copy default value into new repeatable items.
	 *
	 * @var bool
	 */
	protected $repeatable_default = true;

	/**
	 * Store repeatable values in multiple rows.
	 *
	 * @var bool
	 */
	protected $repeatable_as_multiple = false;

	/**
	 * Maximum number of repeatable items.
	 *
	 * @var int
	 */
	protected $max_repeatable = 0;

	/**
	 * Minimum number of repeatable items.
	 *
	 * @var int
	 */
	protected $min_repeatable = 0;

	/**
	 * Button label for adding repeatable items.
	 *
	 * @var string
	 */
	protected $add_button = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 */
	public function __construct( array $config ) {
		$defaults = array(
			'id'          => '',
			'type'        => 'text',
			'label'       => '',
			'description' => '',
			'label_description' => '',
			'default'     => null,
			'args'        => array(),
			'attributes'  => array(),
			'before'      => '',
			'after'       => '',
			'help'        => '',
			'placeholder' => null,
			'layout'      => array(),
			'required'    => false,
			'disabled'    => false,
			'readonly'    => false,
			'multiple'    => false,
			'css_class'   => '',
			'sanitize_callback' => null,
			'save_field'  => true,
			'validation'  => array(),
			// Repeatable options.
			'repeatable'             => false,
			'sort_repeatable'        => false,
			'repeatable_default'     => true,
			'repeatable_as_multiple' => false,
			'max_repeatable'         => 0,
			'min_repeatable'         => 0,
			'add_button'             => '',
		);

		$config = array_merge( $defaults, $config );

		$this->id          = $config['id'];
		$this->type        = $config['type'];
		$this->label       = $config['label'];
		$this->description = $config['description'];
		$this->label_description = is_string( $config['label_description'] ) ? $config['label_description'] : '';
		$this->default     = $config['default'];
		$this->before      = is_string( $config['before'] ) ? $config['before'] : '';
		$this->after       = is_string( $config['after'] ) ? $config['after'] : '';
		$this->help        = is_string( $config['help'] ) ? $config['help'] : '';
		$this->required    = (bool) $config['required'];
		$this->disabled    = (bool) $config['disabled'];
		$this->readonly    = (bool) $config['readonly'];
		$this->multiple    = (bool) $config['multiple'];
		$this->css_class   = is_string( $config['css_class'] ) ? $config['css_class'] : '';
		$this->sanitize_callback = $config['sanitize_callback'];
		$this->save_field  = (bool) $config['save_field'];
		$this->validation  = is_array( $config['validation'] ) ? $config['validation'] : array();
		// Apply repeatable settings directly.
		$this->repeatable             = (bool) $config['repeatable'];
		$this->sort_repeatable        = (bool) $config['sort_repeatable'];
		$this->repeatable_default     = (bool) $config['repeatable_default'];
		$this->repeatable_as_multiple = (bool) $config['repeatable_as_multiple'];
		$this->max_repeatable         = (int) $config['max_repeatable'];
		$this->min_repeatable         = (int) $config['min_repeatable'];
		$this->add_button             = is_string( $config['add_button'] ) ? $config['add_button'] : '';

		$attributes = array();

		if ( is_array( $config['attributes'] ) ) {
			$attributes = array_merge( $attributes, $config['attributes'] );
		}

		if ( is_array( $config['args'] ) ) {
			$attributes = array_merge( $attributes, $config['args'] );
		}

		if ( null !== $config['placeholder'] ) {
			$attributes['placeholder'] = $config['placeholder'];
		}

		// Map common flags to HTML attributes for inputs.
		if ( $this->required && ! isset( $attributes['required'] ) ) {
			$attributes['required'] = true;
		}
		if ( $this->disabled && ! isset( $attributes['disabled'] ) ) {
			$attributes['disabled'] = true;
		}
		if ( $this->readonly && ! isset( $attributes['readonly'] ) ) {
			$attributes['readonly'] = true;
		}
		if ( $this->multiple && ! isset( $attributes['multiple'] ) ) {
			$attributes['multiple'] = true;
		}

		if ( isset( $config['layout'] ) && is_array( $config['layout'] ) ) {
			$this->layout = array_merge(
				$this->layout,
				$config['layout']
			);
		}

		$this->normalise_layout();

		$this->width = isset( $config['width'] ) ? (int) $config['width'] : 0;

		if ( $this->width <= 0 ) {
			$size        = isset( $this->layout['size'] ) ? (int) $this->layout['size'] : 12;
			$this->width = (int) round( ( $size / 12 ) * 100 );
		}

		$this->width = max( 0, min( 100, $this->width ) );

		$this->attributes = $attributes;
		$this->args       = $attributes;
	}

	/**
	 * Returns the field identifier.
	 *
	 * @return string
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Returns the field type key.
	 *
	 * @return string
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * Returns the field label.
	 *
	 * @return string
	 */
	public function label() {
		return $this->label;
	}

	/**
	 * Returns the field description.
	 *
	 * @return string
	 */
	public function description() {
		return $this->description;
	}

	/**
	 * Returns the label description (displayed under the label).
	 *
	 * @return string
	 */
	public function label_description() {
		return $this->label_description;
	}

	/**
	 * Returns the default value.
	 *
	 * @return mixed
	 */
	public function default() {
		return $this->default;
	}

	/**
	 * Returns the preferred width percentage.
	 *
	 * @return int
	 */
	public function width() {
		return $this->width;
	}

	/**
	 * Returns additional HTML attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function args() {
		return $this->attributes;
	}

	/**
	 * Retrieve layout configuration.
	 *
	 * @param string|null $key Optional key to retrieve.
	 * @return mixed
	 */
	public function layout( $key = null ) {
		if ( null === $key ) {
			return $this->layout;
		}

		return isset( $this->layout[ $key ] ) ? $this->layout[ $key ] : null;
	}

	/**
	 * Retrieve attributes assigned to the control.
	 *
	 * @return array<string, mixed>
	 */
	public function attributes() {
		return $this->attributes;
	}

	/**
	 * Normalise layout configuration to ensure valid column spans.
	 *
	 * @return void
	 */
	protected function normalise_layout(): void {
		if ( isset( $this->layout['columns'] ) && is_array( $this->layout['columns'] ) ) {
			foreach ( $this->layout['columns'] as $breakpoint => $span ) {
				$normalised = $this->clampColumnSpan( $span );

				if ( null === $normalised ) {
					unset( $this->layout['columns'][ $breakpoint ] );
					continue;
				}

				$this->layout['columns'][ $breakpoint ] = $normalised;
			}

			if ( empty( $this->layout['columns'] ) ) {
				$this->layout['columns'] = array(
					'default' => 12,
				);
			}

			if ( ! isset( $this->layout['columns']['default'] ) ) {
				$first                              = reset( $this->layout['columns'] );
				$this->layout['columns']['default'] = false !== $first ? (int) $first : 12;
			}

			$this->layout['size'] = $this->layout['columns']['default'];
		} else {
			$size = isset( $this->layout['size'] ) ? $this->clampColumnSpan( $this->layout['size'] ) : 12;
			if ( null === $size ) {
				$size = 12;
			}

			$this->layout['size']    = $size;
			$this->layout['columns'] = array(
				'default' => $size,
			);
		}
	}

	/**
	 * Retrieve a specific attribute value.
	 *
	 * @param string     $key     Attribute key.
	 * @param mixed|null $default Default value if missing.
	 * @return mixed
	 */
	public function attribute( string $key, $default = null ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : $default;
	}

	/**
	 * Retrieve markup rendered before the field control.
	 *
	 * @return string
	 */
	public function before() {
		return $this->before;
	}

	/**
	 * Retrieve markup rendered after the field control.
	 *
	 * @return string
	 */
	public function after() {
		return $this->after;
	}

	/**
	 * Whether the field is marked as required.
	 *
	 * @return bool
	 */
	public function required(): bool {
		return $this->required;
	}

	/**
	 * Custom CSS class applied to the wrapper.
	 *
	 * @return string
	 */
	public function css_class(): string {
		return $this->css_class;
	}

	/**
	 * Whether to save this field on submit.
	 *
	 * @return bool
	 */
	public function should_save(): bool {
		return $this->save_field;
	}

	/**
	 * Retrieve helper text.
	 *
	 * @return string
	 */
	public function help() {
		return $this->help;
	}

	/**
	 * Render attributes into a string suitable for HTML output.
	 *
	 * @param array<string, mixed> $attributes Attribute map.
	 * @return string
	 */
	protected function compile_attributes( array $attributes ) {
		if ( empty( $attributes ) ) {
			return '';
		}

		$output = '';

		foreach ( $attributes as $attribute => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$output .= ' ' . $this->esc_attr( $attribute );
				}
				continue;
			}

			$output .= sprintf(
				' %s="%s"',
				$this->esc_attr( $attribute ),
				$this->esc_attr( $value )
			);
		}

		return $output;
	}

	/**
	 * Sanitize optional markup strings.
	 *
	 * @param string $value Raw markup.
	 * @return string
	 */
	protected function sanitize_markup( $value ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		if ( function_exists( 'wp_kses_post' ) ) {
			return wp_kses_post( $value );
		}

		return $this->esc_html( $value );
	}

	/**
	 * Helper for sanitized before markup.
	 *
	 * @return string
	 */
	public function before_html() {
		return $this->sanitize_markup( $this->before );
	}

	/**
	 * Helper for sanitized after markup.
	 *
	 * @return string
	 */
	public function after_html() {
		return $this->sanitize_markup( $this->after );
	}

	/**
	 * Helper for sanitized help markup.
	 *
	 * @return string
	 */
	public function help_html() {
		return $this->sanitize_markup( $this->help );
	}

	/**
	 * Helper for plain-text help content.
	 *
	 * @return string
	 */
	public function help_text() {
		$help = $this->help_html();

		if ( '' === $help ) {
			return '';
		}

		if ( function_exists( 'wp_strip_all_tags' ) ) {
			$help = wp_strip_all_tags( $help );
		} else {
			$help = strip_tags( $help );
		}

		$help = preg_replace( '/\s+/u', ' ', $help );

		return trim( (string) $help );
	}

	/**
	 * Render the field HTML.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	abstract public function render( $name, $value );

	/**
	 * Sanitize the provided value.
	 *
	 * @param mixed $value Input value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		// Custom sanitizer handling.
		if ( is_string( $this->sanitize_callback ) && 'none' === $this->sanitize_callback ) {
			return $value;
		}
		if ( is_callable( $this->sanitize_callback ) ) {
			return call_user_func( $this->sanitize_callback, $value, $this );
		}

		// Repeatable / multiple values: sanitize each item.
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $item ) {
				if ( is_string( $item ) ) {
					$san = $this->sanitize_string( $item );
					// Drop empty strings but keep numeric "0".
					if ( '' === trim( (string) $san ) ) {
						continue;
					}
					$clean[] = $san;
				} else {
					$clean[] = $item;
				}
			}
			return $clean;
		}

		if ( is_string( $value ) ) {
			return $this->sanitize_string( $value );
		}

		return $value;
	}

	/**
	 * Whether this field is configured as repeatable.
	 *
	 * @return bool
	 */
	public function is_repeatable(): bool {
		return (bool) $this->repeatable;
	}

	/**
	 * Whether repeatable values should be stored as multiple rows.
	 *
	 * @return bool
	 */
	public function repeatable_as_multiple(): bool {
		return (bool) $this->repeatable_as_multiple;
	}

	/**
	 * Button label for adding repeatable items.
	 *
	 * @return string
	 */
	public function add_button_text(): string {
		return (string) $this->add_button;
	}

	/**
	 * Sanitizes a string value using core helpers when available.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	protected function sanitize_string( $value ) {
		if ( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}

		return trim( strip_tags( $value ) );
	}
}
