<?php
/**
 * Abstract field implementation used across WPMoo components.
 *
 * @package WPMoo\Fields
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Abstracts;

use WPMoo\Fields\Contracts\FieldInterface;
use WPMoo\Support\Concerns\EscapesOutput;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides shared functionality for field types.
 */
abstract class AbstractField implements FieldInterface {
	use EscapesOutput;

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
	 * Discrete options for choice-like fields (e.g., select).
	 *
	 * @var array<int|string, mixed>
	 */
	protected $options = array();

	// Layout/width helpers removed; rendering uses simple container/grid wrappers.

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
			// 'layout' ignored under Pico-first renderers
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

		if ( isset( $config['options'] ) && is_array( $config['options'] ) ) {
			$this->options = $config['options'];
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

		$this->attributes = $attributes;
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

	// public function width() removed — not used by Pico-first renderers.

	// public function layout() removed — not used by Pico-first renderers.

	/**
	 * Retrieve attributes assigned to the control.
	 *
	 * @return array<string, mixed>
	 */
	public function attributes() {
		return $this->attributes;
	}

	/**
	 * Retrieve configured options for choice-like fields.
	 *
	 * @return array<int|string, mixed>
	 */
	public function options(): array {
		return $this->options;
	}

	// normalise_layout() removed — layout helpers no longer used.

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
			// Basic tag removal fallback without using strip_tags (plugin‑check compliance).
			$help = (string) preg_replace( '/<[^>]*>/', '', (string) $help );
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
	 * Build the field configuration.
	 *
	 * @return array Configuration array.
	 */
	public function build() {
		return array(
			'id'          => $this->id,
			'type'        => $this->type,
			'label'       => $this->label,
			'description' => $this->description,
			'default'     => $this->default,
			'required'    => $this->required,
			'disabled'    => $this->disabled,
			'readonly'    => $this->readonly,
			'multiple'    => $this->multiple,
			'css_class'   => $this->css_class,
			'sanitize_callback' => $this->sanitize_callback,
			'save_field'  => $this->save_field,
			'validation'  => $this->validation,
			'repeatable'  => $this->repeatable,
			'max_repeatable' => $this->max_repeatable,
			'min_repeatable' => $this->min_repeatable,
			'add_button'  => $this->add_button,
			'attributes'  => $this->attributes,
			'options'     => $this->options,
			'before'      => $this->before,
			'after'       => $this->after,
			'help'        => $this->help,
			'label_description' => $this->label_description,
		);
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
		$label = (string) $this->add_button;
		if ( '' !== $label ) {
			return $label;
		}

		// Default label when none provided: be label-aware (e.g., "Add Tags").
		$base = function_exists( '__' ) ? __( 'Add', 'wpmoo' ) : 'Add';
		if ( '' !== (string) $this->label ) {
			return $base . ' ' . (string) $this->label;
		}

		return $base;
	}

	/**
	 * Maximum repeatable items.
	 *
	 * @return int
	 */
	public function max_repeatable(): int { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return (int) $this->max_repeatable;
	}

	/**
	 * Minimum repeatable items.
	 *
	 * @return int
	 */
	public function min_repeatable(): int { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return (int) $this->min_repeatable;
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

		// Fallback sanitization without using strip_tags (plugin‑check compliance).
		return trim( (string) preg_replace( '/<[^>]*>/', '', (string) $value ) );
	}
}
