<?php
/**
 * Fluent field builder for metaboxes.
 *
 * @package WPMoo\Metabox
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent builder for metabox fields.
 */
class FieldBuilder {
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
		$this->config['args'] = $args;

		return $this;
	}

	/**
	 * Set placeholder.
	 *
	 * @param string $placeholder Placeholder text.
	 * @return $this
	 */
	public function placeholder( string $placeholder ): self {
		if ( ! isset( $this->config['args'] ) ) {
			$this->config['args'] = array();
		}

		$this->config['args']['placeholder'] = $placeholder;

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
