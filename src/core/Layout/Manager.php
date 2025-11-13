<?php
/**
 * Registry/factory for layout components.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout;

use InvalidArgumentException;
use WPMoo\Fields\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Keeps track of layout components and instantiates them on demand.
 */
class Manager {

	/**
	 * Shared singleton instance.
	 *
	 * @var Manager|null
	 */
	protected static $instance = null;

	/**
	 * Registered components map.
	 *
	 * @var array<string, class-string<Component>>
	 */
	protected $components = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_defaults();
	}

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Manager
	 */
	public static function instance(): Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a component class.
	 *
	 * @param string $component Component slug.
	 * @param string $class     Class name.
	 * @return void
	 * @throws InvalidArgumentException When inputs are invalid.
	 */
	public function register( string $component, string $class ): void {
		if ( '' === $component ) {
			throw new InvalidArgumentException( 'Layout component key cannot be empty.' );
		}

		if ( ! class_exists( $class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s is the class name. */
					'Layout component class "%s" does not exist.',
					\esc_html( $class )
				)
			);
		}

		if ( ! is_subclass_of( $class, Component::class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %1$s is the class name, %2$s is the parent class name. */
					'Layout component class "%1$s" must extend %2$s.',
					\esc_html( $class ),
					Component::class
				)
			);
		}

		$this->components[ strtolower( $component ) ] = $class;
	}

	/**
	 * Instantiate a layout component from its normalized definition.
	 *
	 * @param array<string, mixed> $definition   Normalized definition.
	 * @param FieldManager         $field_manager Field manager for nested fields.
	 * @return Component
	 * @throws InvalidArgumentException When the definition is invalid.
	 */
	public function make( array $definition, FieldManager $field_manager ): Component {
		$component = isset( $definition['component'] ) ? strtolower( (string) $definition['component'] ) : '';

		if ( '' === $component || ! isset( $this->components[ $component ] ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Unknown layout component "%s".',
					\esc_html( $component )
				)
			);
		}

		$class  = $this->components[ $component ];
		$config = isset( $definition['config'] ) && is_array( $definition['config'] ) ? $definition['config'] : array();

		$config['component']     = $component;
		$config['field_manager'] = $field_manager;

		return new $class( $config );
	}

	/**
	 * Register the default components bundled with the framework.
	 *
	 * @return void
	 */
	protected function register_defaults(): void {
		foreach ( $this->default_components() as $component => $class ) {
			$this->components[ $component ] = $class;
		}
	}

	/**
	 * Default component map.
	 *
	 * @return array<string, class-string<Component>>
	 */
	protected function default_components(): array {
		return array(
			'accordion' => \WPMoo\Layout\Accordion\Accordion::class,
			'fieldset'  => \WPMoo\Layout\Fieldset\Fieldset::class,
			'tabs'      => \WPMoo\Layout\Tabs\Tabs::class,
		);
	}
}
