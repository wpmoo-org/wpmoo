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
 * Magic static constructors are provided via __callStatic so you can write:
 *  Field::text('id'), Field::textarea('id'), Field::color('id'), etc.
 * These are declared here for static analysers (PHPStan, Psalm).
 *
 * @method static Field text(string $id)
 * @method static Field textarea(string $id)
 * @method static Field color(string $id)
 * @method static Field checkbox(string $id)
 * @method static Field accordion(string $id)
 * @method static Field fieldset(string $id)
 *
 * @phpstan-consistent-constructor
 */
final class Field extends FieldBuilder {
	/**
	 * Create a builder via static typed constructor, e.g. Field::text('id').

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
