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
	exit;
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
				sprintf( 'Field type "%s" requires an id as the first argument.', $method )
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

		if ( method_exists( $this->builder, $name ) ) {
			$result = $this->builder->{$name}( ...$arguments );

			if ( $result instanceof FieldBuilder ) {
				return $this;
			}

			return $result;
		}

		throw new BadMethodCallException(
			sprintf( 'Call to undefined method %s::%s()', static::class, $name )
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
		$map = array(
			'set_label'         => array( 'label', $arguments ),
			'set_title'         => array( 'label', $arguments ),
			'set_description'   => array( 'description', $arguments ),
			'set_default_value' => array( 'default', $arguments ),
			'default_value'     => array( 'default', $arguments ),
			'set_placeholder'   => array( 'placeholder', $arguments ),
			'set_help_text'     => array( 'help', $arguments ),
			'help_text'         => array( 'help', $arguments ),
			'set_help'          => array( 'help', $arguments ),
			'set_options'       => array( 'options', $arguments ),
			'set_attributes'    => array( 'attributes', $arguments ),
			'add_attributes'    => array( 'attributes', $arguments ),
			'add_attribute'     => array(
				'attributes',
				array( array(
					isset( $arguments[0] ) ? (string) $arguments[0] : '' =>
						isset( $arguments[1] ) ? $arguments[1] : null,
				) ),
			),
			'set_before'        => array( 'before', $arguments ),
			'set_after'         => array( 'after', $arguments ),
			'set_help_html'     => array( 'help', $arguments ),
			'set_arg'           => array( 'set', $arguments ),
		);

		if ( isset( $map[ $method ] ) ) {
			return array(
				'method'    => $map[ $method ][0],
				'arguments' => $map[ $method ][1],
			);
		}

		return null;
	}
}

