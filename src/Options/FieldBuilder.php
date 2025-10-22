<?php
/**
 * Fluent field builder for options.
 *
 * @package WPMoo\Options
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Options;

use WPMoo\Support\Concerns\HasColumns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent builder for option fields.
 */
class FieldBuilder {
	use HasColumns;
	/**
	 * Field configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $config = array();

	/**
	 * Constructor.
	 *
	 * @param string $id   Field ID.
	 * @param string $type Field type.
	 */
	public function __construct( string $id, string $type ) {
		$this->config = array(
			'id'   => $id,
			'type' => $type,
		);
	}

	/**
	 * Set field label.
	 *
	 * @param string $label Label.
	 * @return $this
	 */
	public function label( string $label ): self {
		$this->config['label'] = $label;

		return $this;
	}

	/**
	 * Set field description.
	 *
	 * @param string $description Description.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->config['description'] = $description;

		return $this;
	}

	/**
	 * Set default value.
	 *
	 * @param mixed $default Default value.
	 * @return $this
	 */
	public function default( $default ): self {
		$this->config['default'] = $default;

		return $this;
	}

	/**
	 * Set field arguments.
	 *
	 * @param array<string, mixed> $args Arguments.
	 * @return $this
	 */
	public function args( array $args ): self {
		return $this->attributes( $args );
	}

	/**
	 * Set placeholder.
	 *
	 * @param string $placeholder Placeholder text.
	 * @return $this
	 */
	public function placeholder( string $placeholder ): self {
		if ( ! isset( $this->config['attributes'] ) ) {
			$this->config['attributes'] = array();
		}

		$this->config['attributes']['placeholder'] = $placeholder;

		return $this;
	}

	/**
	 * Set options for select/radio fields.
	 *
	 * @param array<string, string> $options Options array.
	 * @return $this
	 */
	public function options( array $options ): self {
		$this->config['options'] = $options;

		return $this;
	}

	/**
	 * Merge additional HTML attributes.
	 *
	 * @param array<string, mixed> $attributes Attributes to merge.
	 * @return $this
	 */
	public function attributes( array $attributes ): self {
		if ( ! isset( $this->config['attributes'] ) ) {
			$this->config['attributes'] = array();
		}

		$this->config['attributes'] = array_merge( $this->config['attributes'], $attributes );

		return $this;
	}

	/**
	 * Define layout configuration.
	 *
	 * @param array<string, mixed> $layout Layout settings.
	 * @return $this
	 */
	public function layout( array $layout ): self {
		if ( ! isset( $this->config['layout'] ) ) {
			$this->config['layout'] = array();
		}

		if ( isset( $layout['size'] ) && ! isset( $layout['columns'] ) ) {
			$layout['columns'] = array(
				'default' => $this->clampColumnSpan( $layout['size'] ),
			);
		}

		$this->config['layout'] = array_merge( $this->config['layout'], $layout );

		return $this;
	}

	/**
	 * Set grid column span(s).
	 *
	 * @param mixed ...$columns Column definitions (int, string, array).
	 * @return $this
	 */
	public function size( ...$columns ): self {
		$parsed = $this->parseColumnSpans( $columns );

		return $this->layout(
			array(
				'size'    => $parsed['default'],
				'columns' => $parsed,
			)
		);
	}

	/**
	 * Define nested fields (used by composite controls).
	 *
	 * @param array<int, mixed> $fields Field definitions.
	 * @return $this
	 */
	public function fields( array $fields ): self {
		return $this->set( 'fields', $fields );
	}

	/**
	 * Set preferred gutter size for grid-based controls.
	 *
	 * @param string $gutter Gutter keyword (sm, md, lg, xl, none).
	 * @return $this
	 */
	public function gutter( string $gutter ): self {
		return $this->layout(
			array(
				'gutter' => $gutter,
			)
		);
	}

	/**
	 * Alias for size().
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function columns( ...$columns ): self {
		return $this->size( ...$columns );
	}

	/**
	 * Markup displayed before the field control.
	 *
	 * @param string $markup HTML markup.
	 * @return $this
	 */
	public function before( string $markup ): self {
		$this->config['before'] = $markup;
		return $this;
	}

	/**
	 * Markup displayed after the field control.
	 *
	 * @param string $markup HTML markup.
	 * @return $this
	 */
	public function after( string $markup ): self {
		$this->config['after'] = $markup;
		return $this;
	}

	/**
	 * Helper text rendered beneath the control.
	 *
	 * @param string $markup Helper text.
	 * @return $this
	 */
	public function help( string $markup ): self {
		$this->config['help'] = $markup;
		return $this;
	}

	/**
	 * Generic config setter.
	 *
	 * @param string $key   Config key.
	 * @param mixed  $value Config value.
	 * @return $this
	 */
	public function set( string $key, $value ): self {
		$this->config[ $key ] = $value;

		return $this;
	}

	/**
	 * Build the field configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		return $this->config;
	}
}
