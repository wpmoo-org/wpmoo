<?php

namespace WPMoo\Field;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Field type registry for dynamic field type management.
 *
 * This registry allows for registering and retrieving field types dynamically,
 * enabling extensibility through hooks and custom field types.
 *
 * @package WPMoo\Field
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class FieldTypeRegistry {

	/**
	 * Registered field types.
	 *
	 * @var array<string, class-string<FieldInterface>>
	 */
	private array $field_types = array();

	/**
	 * Default field types provided by the framework.
	 *
	 * @var array<string, class-string<FieldInterface>>
	 */
	private array $default_field_types = array(
		'input' => \WPMoo\Field\Type\Input::class,
		'textarea' => \WPMoo\Field\Type\Textarea::class,
		'toggle' => \WPMoo\Field\Type\Toggle::class,
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_field_types();
	}

	/**
	 * Register default field types provided by the framework.
	 */
	private function register_default_field_types(): void {
		foreach ( $this->default_field_types as $type => $class ) {
			$this->register_field_type( $type, $class );
		}
	}

	/**
	 * Register a new field type.
	 *
	 * @param string                       $type The field type slug.
	 * @param class-string<FieldInterface> $class The field class name.
	 * @return void
	 */
	public function register_field_type( string $type, string $class ): void {
		if ( ! class_exists( $class ) ) {
			throw new \InvalidArgumentException( "Field class does not exist: {$class}" );
		}

		// Verify that the class implements the FieldInterface
		if ( ! in_array( \WPMoo\Field\Interfaces\FieldInterface::class, class_implements( $class ) ) ) {
			throw new \InvalidArgumentException( "Field class must implement FieldInterface: {$class}" );
		}

		$this->field_types[ $type ] = $class;
	}

	/**
	 * Get a field class by type.
	 *
	 * @param string $type The field type slug.
	 * @return class-string<FieldInterface>|null The field class name or null if not found.
	 */
	public function get_field_class( string $type ): ?string {
		return $this->field_types[ $type ] ?? null;
	}

	/**
	 * Check if a field type is registered.
	 *
	 * @param string $type The field type slug.
	 * @return bool True if the type is registered, false otherwise.
	 */
	public function has_field_type( string $type ): bool {
		return isset( $this->field_types[ $type ] );
	}

	/**
	 * Get all registered field types.
	 *
	 * @return array<string, class-string<FieldInterface>> All registered field types.
	 */
	public function get_all_field_types(): array {
		return $this->field_types;
	}

	/**
	 * Create a field instance by type and ID.
	 *
	 * @param string $type The field type slug.
	 * @param string $id The field ID.
	 * @return FieldInterface|null The field instance or null if type is not registered.
	 */
	public function create_field( string $type, string $id ): ?FieldInterface {
		$class = $this->get_field_class( $type );

		if ( ! $class ) {
			return null;
		}

		return new $class( $id );
	}
}
