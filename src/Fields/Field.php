<?php
/**
 * Base field implementation used across WPMoo components.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 */

namespace WPMoo\Fields;

/**
 * Provides shared functionality for field types.
 */
abstract class Field {

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
	 * Additional HTML attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected $args;

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
		);

		$config = array_merge( $defaults, $config );

		$this->id          = $config['id'];
		$this->type        = $config['type'];
		$this->label       = $config['label'];
		$this->description = $config['description'];
		$this->default     = $config['default'];
		$this->args        = is_array( $config['args'] ) ? $config['args'] : array();
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
		return $this->args;
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

	/**
	 * Escapes a value for usage in HTML attribute context.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_attr( $value ) {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Escapes a value for usage in HTML text context.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_html( $value ) {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}
}
