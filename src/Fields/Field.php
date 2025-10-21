<?php
/**
 * Base field implementation used across WPMoo components.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Fields;

use WPMoo\Support\Concerns\EscapesOutput;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides shared functionality for field types.
 */
abstract class Field {
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
			'default'     => null,
			'args'        => array(),
			'attributes'  => array(),
			'before'      => '',
			'after'       => '',
			'help'        => '',
			'placeholder' => null,
		);

		$config = array_merge( $defaults, $config );

		$this->id          = $config['id'];
		$this->type        = $config['type'];
		$this->label       = $config['label'];
		$this->description = $config['description'];
		$this->default     = $config['default'];
		$this->before      = is_string( $config['before'] ) ? $config['before'] : '';
		$this->after       = is_string( $config['after'] ) ? $config['after'] : '';
		$this->help        = is_string( $config['help'] ) ? $config['help'] : '';

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
	 * Returns the default value.
	 *
	 * @return mixed
	 */
	public function default() {
		return $this->default;
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
	 * Retrieve attributes assigned to the control.
	 *
	 * @return array<string, mixed>
	 */
	public function attributes() {
		return $this->attributes;
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
		if ( is_string( $value ) ) {
			return $this->sanitize_string( $value );
		}

		return $value;
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
