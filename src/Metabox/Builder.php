<?php
/**
 * Fluent metabox builder.
 *
 * @package WPMoo\Metabox
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Metabox;

use InvalidArgumentException;
use WPMoo\Fields\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent builder for metaboxes.
 */
class Builder {
	/**
	 * Metabox ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Metabox configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $config = array();

	/**
	 * Fields configuration.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $fields = array();

	/**
	 * Field manager instance.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Constructor.
	 *
	 * @param string  $id            Metabox ID.
	 * @param Manager $field_manager Field manager.
	 */
	public function __construct( string $id, Manager $field_manager ) {
		if ( empty( $id ) ) {
			throw new InvalidArgumentException( 'Metabox ID cannot be empty.' );
		}

		$this->id            = $id;
		$this->field_manager = $field_manager;
		$this->config        = array(
			'id'      => $id,
			'screens' => array( 'post' ),
		);
	}

	/**
	 * Set metabox title.
	 *
	 * @param string $title Title.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->config['title'] = $title;

		return $this;
	}

	/**
	 * Set post types (screens).
	 *
	 * @param string|array<int, string> $screens Post type(s).
	 * @return $this
	 */
	public function postType( $screens ): self {
		$this->config['screens'] = is_string( $screens ) ? array( $screens ) : $screens;

		return $this;
	}

	/**
	 * Set context.
	 *
	 * @param string $context Context ('normal', 'side', 'advanced').
	 * @return $this
	 */
	public function context( string $context ): self {
		$this->config['context'] = $context;

		return $this;
	}

	/**
	 * Set priority.
	 *
	 * @param string $priority Priority ('high', 'low', 'default').
	 * @return $this
	 */
	public function priority( string $priority ): self {
		$this->config['priority'] = $priority;

		return $this;
	}

	/**
	 * Set capability required.
	 *
	 * @param string $capability Capability.
	 * @return $this
	 */
	public function capability( string $capability ): self {
		$this->config['capability'] = $capability;

		return $this;
	}

	/**
	 * Add a field.
	 *
	 * @param string $id   Field ID.
	 * @param string $type Field type.
	 * @return FieldBuilder
	 */
	public function field( string $id, string $type ): FieldBuilder {
		$field = new FieldBuilder( $id, $type );
		
		$this->fields[] = $field;

		return $field;
	}

	/**
	 * Add fields from array (backward compatibility).
	 *
	 * @param array<int, array<string, mixed>> $fields Fields array.
	 * @return $this
	 */
	public function fields( array $fields ): self {
		foreach ( $fields as $field_config ) {
			$this->fields[] = $field_config;
		}

		return $this;
	}

	/**
	 * Set context to normal.
	 *
	 * @return $this
	 */
	public function normal(): self {
		return $this->context( 'normal' );
	}

	/**
	 * Set context to side.
	 *
	 * @return $this
	 */
	public function side(): self {
		return $this->context( 'side' );
	}

	/**
	 * Set context to advanced.
	 *
	 * @return $this
	 */
	public function advanced(): self {
		return $this->context( 'advanced' );
	}

	/**
	 * Set priority to high.
	 *
	 * @return $this
	 */
	public function high(): self {
		return $this->priority( 'high' );
	}

	/**
	 * Set priority to low.
	 *
	 * @return $this
	 */
	public function low(): self {
		return $this->priority( 'low' );
	}

	/**
	 * Generic config setter.
	 *
	 * @param string $key   Config key.
	 * @param mixed  $value Config value.
	 * @return $this
	 */
	public function config( string $key, $value ): self {
		$this->config[ $key ] = $value;

		return $this;
	}

	/**
	 * Register the metabox.
	 *
	 * @return Metabox
	 */
	public function register(): Metabox {
		// Build fields from FieldBuilder instances.
		$built_fields = array();
		
		foreach ( $this->fields as $field ) {
			if ( $field instanceof FieldBuilder ) {
				$built_fields[] = $field->build();
			} else {
				$built_fields[] = $field;
			}
		}

		$this->config['fields'] = $built_fields;

		// Create metabox instance.
		Metabox::ensure_booted();
		$metabox = new Metabox( $this->config, $this->field_manager );
		$metabox->boot();

		// Register in static cache.
		Metabox::registerMetabox( $metabox );

		return $metabox;
	}
}
