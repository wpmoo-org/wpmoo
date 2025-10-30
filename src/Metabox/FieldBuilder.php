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

use WPMoo\Fields\FieldBuilder as BaseFieldBuilder;
if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fluent builder for metabox fields.
 */
class FieldBuilder extends BaseFieldBuilder {
	/**
	 * Field configuration.
	 *
	 * @var array<string, mixed>
	 */
    // Inherit $config and fluent API from base builder.

	/**
	 * Constructor.
	 *
	 * @param string $id   Field ID.
	 * @param string $type Field type.
	 */
    public function __construct( string $id, string $type ) { parent::__construct( $id, $type ); }

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
    public function args( array $args ): self { return parent::args( $args ); }

	/**
	 * Set placeholder.
	 *
	 * @param string $placeholder Placeholder text.
	 * @return $this
	 */
    public function placeholder( string $placeholder ): self { return parent::placeholder( $placeholder ); }

	/**
	 * Set options for select/radio fields.
	 *
	 * @param array<string, string> $options Options array.
	 * @return $this
	 */
    public function options( array $options ): self { return parent::options( $options ); }

	/**
	 * Generic config setter.
	 *
	 * @param string $key   Config key.
	 * @param mixed  $value Config value.
	 * @return $this
	 */
    public function set( string $key, $value ): self { return parent::set( $key, $value ); }

	/**
	 * Build the field configuration.
	 *
	 * @return array<string, mixed>
	 */
    public function build(): array { return parent::build(); }
}
