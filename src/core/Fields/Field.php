<?php
/**
 * Fluent field builder alias: Field now maps to the builder.
 *
 * @package WPMoo\Fields
 */

namespace WPMoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Field builder (alias for FieldBuilder).
 *
 * Magic static constructors are provided via __callStatic.
 * These are declared here for static analysers (PHPStan, Psalm).
 *
 * @method static Field input(string $id)
 * @method static Field button(string $id)
 * @method static Field textarea(string $id)
 * @method static Field select(string $id)
 * @method static Field checkbox(string $id)
 * @method static Field radio(string $id)
 * @method static Field toggle(string $id)
 * @method static Field range(string $id)
 *
 * @phpstan-consistent-constructor
 */
final class Field extends FieldBuilder {
	/**
	 * Create an 'input' field builder (text-like).
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function input( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'input' );
	}

	/**
	 * Create a 'button' pseudo-field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function button( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'button' );
	}

	/**
	 * Create a 'textarea' field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function textarea( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'textarea' );
	}

	/**
	 * Create a 'select' field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function select( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'select' );
	}

	/**
	 * Create a 'checkbox' field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function checkbox( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'checkbox' );
	}

	/**
	 * Create a 'radio' field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function radio( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'radio' );
	}

	/**
	 * Create a 'toggle' field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function toggle( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'toggle' );
	}

	/**
	 * Create a 'range' field builder.
	 *
	 * @param string $id Field id.
	 * @return static
	 */
	public static function range( string $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return new self( $id, 'range' );
	}

	/**
	 * Create a builder via static typed constructor, e.g. Field::text('id').
	 *
	 * @param string $name      Called static method name (used as type).
	 * @param array  $arguments Arguments passed to the method (expects [ id ]).
	 * @return static
	 * @throws \InvalidArgumentException If ID is missing.
	 */
	public static function __callStatic( string $name, array $arguments ) {
		$id = '';
		if ( isset( $arguments[0] ) && is_string( $arguments[0] ) ) {
			$id = $arguments[0];
		}

		if ( '' === $id ) {
			throw new \InvalidArgumentException( 'Field id is required for static constructor.' );
		}

		$type = strtolower( $name );
		// Allow snake_case in static calls (maps to kebab-case types if used).
		$type = str_replace( '_', '-', $type );

		return new self( $id, $type );
	}

	/**
	 * Explicit factory helper.
	 *
	 * @param string $id   Field id.
	 * @param string $type Field type.
	 * @return static
	 */
	public static function make( string $id, string $type ) {
		return new self( $id, $type );
	}
}
