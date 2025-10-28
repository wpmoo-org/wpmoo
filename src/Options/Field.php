<?php
/**
 * Lightweight field builder mirroring Carbon Fields syntax.
 *
 * @package WPMoo\Options
 */

namespace WPMoo\Options;

use BadMethodCallException;
use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fluent facade around the existing FieldBuilder.
 */
class Field {

	/**
	 * Underlying field builder instance.
	 *
	 * @var FieldBuilder
	 */
	protected $builder;

	/**
	 * Constructor.
	 *
	 * @param FieldBuilder $builder Field builder.
	 */
	protected function __construct( FieldBuilder $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Create a field definition.
	 *
	 * @param string $type  Field type.
	 * @param string $id    Field identifier.
	 * @param string $label Optional label.
	 * @return static
	 */
	public static function make( string $type, string $id, string $label = '' ): self {
		if ( '' === $id ) {
			throw new InvalidArgumentException( 'Field id cannot be empty.' );
		}

		$type    = static::normalize_type( $type );
		$builder = new FieldBuilder( $id, $type );

		if ( '' !== $label ) {
			$builder->label( $label );
		}

		return new self( $builder );
	}

	/**
	 * Shortcut factory to allow Field::text( 'id', 'Label' ) style calls.
	 *
	 * @param string $method Method name.
	 * @param array  $arguments Invocation arguments.
	 * @return static
	 */
	public static function __callStatic( string $method, array $arguments ) {
		if ( empty( $arguments ) ) {
			throw new InvalidArgumentException(
				sprintf(
					static::translate_string( 'Field type "%s" requires an id as the first argument.' ),
					(string) $method
				)
			);
		}

		$id    = (string) array_shift( $arguments );
		$label = isset( $arguments[0] ) ? (string) $arguments[0] : '';

		return static::make( $method, $id, $label );
	}

	/**
	 * Return the underlying builder instance.
	 *
	 * @return FieldBuilder
	 */
	public function builder(): FieldBuilder {
		return $this->builder;
	}

	/**
	 * Convert the field definition to an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return $this->builder->build();
	}

	/**
	 * Proxy unknown methods to the underlying builder with Carbon-style aliases.
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Invocation arguments.
	 * @return $this|mixed
	 */
	public function __call( $name, $arguments ) {
		$lower = strtolower( $name );

		if ( in_array( $lower, array( 'set_required', 'required' ), true ) ) {
			$value = isset( $arguments[0] ) ? (bool) $arguments[0] : true;
			$this->builder->set( 'required', $value );
			return $this;
		}

		$alias = $this->resolve_alias( $lower, $arguments );

		if ( $alias ) {
			$name      = $alias['method'];
			$arguments = $alias['arguments'];
		}

		$arguments = $this->normalize_arguments( $name, $arguments );

		if ( method_exists( $this->builder, $name ) ) {
			$result = $this->builder->{$name}( ...$arguments );

			if ( $result instanceof FieldBuilder ) {
				return $this;
			}

			return $result;
		}

		throw new BadMethodCallException(
			sprintf(
				static::translate_string( 'Call to undefined method %1$s::%2$s().' ),
				static::class,
				(string) $name
			)
		);
	}

	/**
	 * Normalize the field type from various naming conventions.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	protected static function normalize_type( string $type ): string {
		$type = preg_replace( '/([a-z])([A-Z])/', '$1_$2', $type );
		$type = strtolower( $type );
		$type = str_replace( array( ' ', '_' ), '-', $type );

		return $type;
	}

	/**
	 * Map Carbon-style aliases to FieldBuilder methods.
	 *
	 * @param string $method    Method name.
	 * @param array  $arguments Original arguments.
	 * @return array<string, mixed>|null
	 */
	protected function resolve_alias( string $method, array $arguments ) {
		switch ( $method ) {
			case 'set_label':
			case 'set_title':
				return array(
					'method'    => 'label',
					'arguments' => $arguments,
				);

			case 'set_description':
				return array(
					'method'    => 'description',
					'arguments' => $arguments,
				);

			case 'set_default_value':
			case 'default_value':
				return array(
					'method'    => 'default',
					'arguments' => $arguments,
				);

			case 'set_placeholder':
				return array(
					'method'    => 'placeholder',
					'arguments' => $arguments,
				);

			case 'set_help_text':
			case 'help_text':
			case 'set_help':
			case 'set_help_html':
				return array(
					'method'    => 'help',
					'arguments' => $arguments,
				);

			case 'set_options':
				return array(
					'method'    => 'options',
					'arguments' => $arguments,
				);

			case 'set_width':
			case 'width':
				return array(
					'method'    => 'width',
					'arguments' => $arguments,
				);

			case 'set_attributes':
				return array(
					'method'    => 'attributes',
					'arguments' => $arguments,
				);

			case 'add_attributes':
			case 'add_attribute':
				$normalized = $this->normalize_attribute_arguments( $arguments );
				return array(
					'arguments' => $normalized[1],
					'method'    => $normalized[0],
				);

			case 'set_before':
				return array(
					'method'    => 'before',
					'arguments' => $arguments,
				);

			case 'set_after':
				return array(
					'method'    => 'after',
					'arguments' => $arguments,
				);

			case 'set_arg':
				return array(
					'method'    => 'set',
					'arguments' => $arguments,
				);
		}

		return null;
	}

	/**
	 * Normalise attribute helper arguments to avoid array-to-string warnings.
	 *
	 * @param array<int, mixed> $arguments Raw arguments passed to the proxy.
	 * @return array<string, mixed>
	 */
	protected function normalize_attribute_arguments( array $arguments ): array {
		// Allow passing an associative array directly.
		if ( isset( $arguments[0] ) && is_array( $arguments[0] ) && ! isset( $arguments[1] ) ) {
			return array( 'attributes', array( $arguments[0] ) );
		}

		$key   = isset( $arguments[0] ) ? (string) $arguments[0] : '';
		$value = isset( $arguments[1] ) ? $arguments[1] : null;

		return array(
			'attributes',
			array(
				array(
					$key => $value,
				),
			),
		);
	}

	/**
	 * Normalise arguments before forwarding them to the builder.
	 *
	 * @param string            $method    Method being invoked.
	 * @param array<int, mixed> $arguments Original arguments.
	 * @return array<int, mixed>
	 */
	protected function normalize_arguments( string $method, array $arguments ): array {
		if ( 'set' === $method && isset( $arguments[1] ) ) {
			$arguments[1] = $this->normalize_nested_value( $arguments[1] );
			return $arguments;
		}

		if ( 'fields' === $method ) {
			if ( empty( $arguments ) ) {
				return array( array() );
			}

			if ( 1 === count( $arguments ) && is_array( $arguments[0] ) ) {
				$arguments[0] = $this->normalize_nested_value( $arguments[0] );
				return $arguments;
			}

			$normalized = array();

			foreach ( $arguments as $argument ) {
				$normalized[] = $this->normalize_nested_value( $argument );
			}

			return array( $normalized );
		}

		if ( in_array( $method, array( 'attributes', 'options', 'args' ), true ) && isset( $arguments[0] ) ) {
			$arguments[0] = $this->normalize_nested_value( $arguments[0] );
			return $arguments;
		}

		return $arguments;
	}

	/**
	 * Recursively convert Field/FieldBuilder instances within arrays.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function normalize_nested_value( $value ) {
		if ( $value instanceof self ) {
			return $value->toArray();
		}

		if ( $value instanceof FieldBuilder ) {
			return $value->build();
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->normalize_nested_value( $item );
			}
			return $value;
		}

		return $value;
	}

	/**
	 * Translate a message while tolerating non-WordPress runtimes.
	 *
	 * @param string $text Message to translate.
	 * @return string
	 */
	protected static function translate_string( string $text ): string {
		return function_exists( '__' ) ? \__( $text, 'wpmoo' ) : $text;
	}
}
