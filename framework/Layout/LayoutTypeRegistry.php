<?php

namespace WPMoo\Layout;

use WPMoo\Layout\Interfaces\LayoutInterface;

/**
 * Layout type registry for dynamic layout component management.
 *
 * This registry allows for registering and retrieving layout types dynamically,
 * enabling extensibility through hooks and custom layout types.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class LayoutTypeRegistry {

	/**
	 * Registered layout types.
	 *
	 * @var array<string, class-string<LayoutInterface>>
	 */
	private array $layout_types = array();

	/**
	 * Default layout types provided by the framework.
	 *
	 * @var array<string, class-string<LayoutInterface>>
	 */
	private array $default_layout_types = array(
		'tabs' => \WPMoo\Layout\Component\Tabs::class,
		'accordion' => \WPMoo\Layout\Component\Accordion::class,
		'container' => \WPMoo\Layout\Component\Container::class,
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_layout_types();
	}

	/**
	 * Register default layout types provided by the framework.
	 */
	private function register_default_layout_types(): void {
		foreach ( $this->default_layout_types as $type => $class ) {
			$this->register_layout_type( $type, $class );
		}
	}

	/**
	 * Register a new layout type.
	 *
	 * @param string $type The layout type slug.
	 * @param string $class The layout class name.
	 *
	 * @phpstan-param class-string<\WPMoo\Layout\Interfaces\LayoutInterface> $class
	 *
	 * @return void
	 * @throws \InvalidArgumentException If the layout class doesn't exist or doesn't implement LayoutInterface.
	 */
	public function register_layout_type( string $type, string $class ): void {
		// Validate the layout type slug format.
		if ( ! class_exists( $class ) ) {
			/* translators: %s: Layout class name */
			throw new \InvalidArgumentException( sprintf( esc_html__( 'Layout class does not exist: %s', 'wpmoo' ), esc_html( $class ) ) );
		}

		// Verify that the class implements the LayoutInterface.
		if ( ! in_array( \WPMoo\Layout\Interfaces\LayoutInterface::class, class_implements( $class ) ) ) {
			/* translators: %s: Layout class name */
			throw new \InvalidArgumentException( sprintf( esc_html__( 'Layout class must implement LayoutInterface: %s', 'wpmoo' ), esc_html( $class ) ) );
		}

		$this->layout_types[ $type ] = $class;
	}

	/**
	 * Get a layout class by type.
	 *
	 * @param string $type The layout type slug.
	 * @return class-string<LayoutInterface>|null The layout class name or null if not found.
	 */
	public function get_layout_class( string $type ): ?string {
		return $this->layout_types[ $type ] ?? null;
	}

	/**
	 * Check if a layout type is registered.
	 *
	 * @param string $type The layout type slug.
	 * @return bool True if the type is registered, false otherwise.
	 */
	public function has_layout_type( string $type ): bool {
		return isset( $this->layout_types[ $type ] );
	}

	/**
	 * Get all registered layout types.
	 *
	 * @return array<string, class-string<LayoutInterface>> All registered layout types.
	 */
	public function get_all_layout_types(): array {
		return $this->layout_types;
	}

	/**
	 * Create a layout instance by type, ID, and title.
	 *
	 * @param string $type The layout type slug.
	 * @param string $id The layout ID.
	 * @param string $title The layout title (where applicable).
	 * @return LayoutInterface|null The layout instance or null if type is not registered.
	 */
	public function create_layout( string $type, string $id, string $title = '' ): ?LayoutInterface {
		$class = $this->get_layout_class( $type );

		if ( ! $class ) {
			return null;
		}

		// Different layout types might have different constructors.
		// For now, assuming all accept at least an ID.
		return new $class( $id, $title );
	}
}
