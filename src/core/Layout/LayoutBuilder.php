<?php
/**
 * Shared fluent builder for layout-only components (tabs, accordion, etc.).
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout;

use WPMoo\Fields\FieldBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Base configuration builder for layout components.
 */
abstract class LayoutBuilder {

	/**
	 * Layout identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Component slug (tabs, accordion, ...).
	 *
	 * @var string
	 */
	protected $component;

	/**
	 * Builder configuration bag.
	 *
	 * @var array<string, mixed>
	 */
	protected $config = array();

	/**
	 * Constructor.
	 *
	 * @param string $id        Layout identifier.
	 * @param string $component Component slug.
	 */
	public function __construct( string $id, string $component ) {
		$this->id        = $id;
		$this->component = strtolower( $component );

		$this->config = array(
			'id' => $id,
		);
	}

	/**
	 * Retrieve the identifier.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Set the visible label/title.
	 *
	 * @param string $label Label text.
	 * @return $this
	 */
	public function label( string $label ): self {
		$this->config['label'] = $label;
		$this->config['title'] = $label;
		return $this;
	}

	/**
	 * Alias for label() for backwards compatibility.
	 *
	 * @param string $title Title text.
	 * @return $this
	 */
	public function title( string $title ): self {
		return $this->label( $title );
	}

	/**
	 * Set the body description.
	 *
	 * @param string $description Description text.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->config['description'] = $description;
		return $this;
	}

	/**
	 * Set the helper text displayed beneath the label.
	 *
	 * @param string $text Label helper text.
	 * @return $this
	 */
	public function label_description( string $text ): self {
		$this->config['label_description'] = $text;
		return $this;
	}

	/**
	 * Provide helper/footnote markup.
	 *
	 * @param string $help Help markup.
	 * @return $this
	 */
	public function help( string $help ): self {
		$this->config['help'] = $help;
		return $this;
	}

	/**
	 * Inject raw markup before the component.
	 *
	 * @param string $markup HTML markup.
	 * @return $this
	 */
	public function before( string $markup ): self {
		$this->config['before'] = $markup;
		return $this;
	}

	/**
	 * Inject raw markup after the component.
	 *
	 * @param string $markup HTML markup.
	 * @return $this
	 */
	public function after( string $markup ): self {
		$this->config['after'] = $markup;
		return $this;
	}

	/**
	 * Assign a custom CSS class.
	 *
	 * @param string $class Class attribute.
	 * @return $this
	 */
	public function css_class( string $class ): self {
		$this->config['css_class'] = $class;
		return $this;
	}

	/**
	 * Define a default structured value.
	 *
	 * @param mixed $default Default value.
	 * @return $this
	 */
	public function default( $default ): self {
		$this->config['default'] = $default;
		return $this;
	}

	/**
	 * Generic setter.
	 *
	 * @param string $key   Config key.
	 * @param mixed  $value Config value.
	 * @return $this
	 */
	protected function set( string $key, $value ): self {
		$this->config[ $key ] = $value;
		return $this;
	}

	/**
	 * Build the normalized configuration array.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		return array(
			'__layout_component' => true,
			'component'          => $this->component,
			'id'                 => $this->id,
			'config'             => $this->config,
		);
	}

	/**
	 * Normalize nested field definitions.
	 *
	 * @param array<int, mixed> $fields Field definitions.
	 * @return array<int, array<string, mixed>>
	 */
	protected function normalize_fields( array $fields ): array {
		$normalized = array();

		foreach ( $fields as $field ) {
			if ( $field instanceof FieldBuilder ) {
				$normalized[] = $field->build();
			} elseif ( is_array( $field ) ) {
				$normalized[] = $field;
			}
		}

		return $normalized;
	}
}
