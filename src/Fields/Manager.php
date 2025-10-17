<?php
/**
 * Handles field registration and instantiation for WPMoo.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 */

namespace WPMoo\Fields;

use InvalidArgumentException;

/**
 * Keeps track of field type mappings.
 */
class Manager {

	/**
	 * Registered field type map.
	 *
	 * @var array<string, class-string<Field>>
	 */
	protected $types = array();

	/**
	 * Register a new field type.
	 *
	 * @param string $type  Field type key.
	 * @param string $class Field class name.
	 * @return void
	 * @throws InvalidArgumentException If the registration arguments are invalid.
	 */
	public function register( $type, $class ) {
		$this->validate_type_class_pair( $type, $class );

		$this->types[ $type ] = $class;
	}

	/**
	 * Determine whether a field type is registered.
	 *
	 * @param string $type Field type key.
	 * @return bool
	 */
	public function has( $type ) {
		return isset( $this->types[ $type ] );
	}

	/**
	 * Create a field instance.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @return Field
	 * @throws InvalidArgumentException When a field type has not been registered.
	 */
	public function make( array $config ) {
		$type  = isset( $config['type'] ) ? $config['type'] : 'text';
		$class = isset( $config['class'] ) ? $config['class'] : null;

		if ( ! $this->has( $type ) ) {
			$this->maybe_autoregister( $type, $class );
		}

		if ( ! $this->has( $type ) ) {
			throw new InvalidArgumentException( sprintf( 'Field type "%s" is not registered.', $type ) );
		}

		$class = $this->types[ $type ];
		$config['type'] = $type;

		return new $class( $config );
	}

	/**
	 * Return a list of registered type keys.
	 *
	 * @return string[]
	 */
	public function types() {
		return array_keys( $this->types );
	}

	/**
	 * Validate the provided type/class pair.
	 *
	 * @param string $type  Field type key.
	 * @param string $class Field class name.
	 * @return void
	 */
	protected function validate_type_class_pair( $type, $class ) {
		if ( ! is_string( $type ) || '' === $type ) {
			throw new InvalidArgumentException( 'Field type must be a non-empty string.' );
		}

		if ( ! class_exists( $class ) ) {
			throw new InvalidArgumentException( sprintf( 'Field class "%s" does not exist.', $class ) );
		}

		if ( ! is_subclass_of( $class, Field::class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Field class "%1$s" must extend %2$s.',
					$class,
					Field::class
				)
			);
		}
	}

	/**
	 * Attempt to automatically register a field class for the given type.
	 *
	 * @param string      $type  Field type key or class name.
	 * @param string|null $class Optional explicit class name.
	 * @return void
	 */
	protected function maybe_autoregister( $type, $class = null ) {
		if ( $class ) {
			$this->register( $type, $class );
			return;
		}

		if ( class_exists( $type ) ) {
			$this->register( $type, $type );
			return;
		}

		$candidate = $this->resolve_class_from_type( $type );

		if ( $candidate && class_exists( $candidate ) ) {
			$this->register( $type, $candidate );
		}
	}

	/**
	 * Resolve a potential class name from a field type slug.
	 *
	 * @param string $type Field type key.
	 * @return string|null
	 */
	protected function resolve_class_from_type( $type ) {
		$studly = str_replace( ' ', '', ucwords( str_replace( array( '-', '_' ), ' ', $type ) ) );

		$candidates = array(
			"WPMoo\\Fields\\{$studly}\\{$studly}",
			"WPMoo\\Fields\\{$studly}",
		);

		foreach ( $candidates as $candidate ) {
			if ( class_exists( $candidate ) ) {
				return $candidate;
			}
		}

		return null;
	}
}
