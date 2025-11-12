<?php
/**
 * Base section builder shared by Options and Metabox components.
 *
 * @package WPMoo\Sections
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */


namespace WPMoo\Sections;

use WPMoo\Fields\Builder as FieldBuilder;
use WPMoo\Layout\Builder as LayoutBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides common section properties and layout helpers.
 * Holds fields and can build a full section config.
 */
class Builder {

	/**
	 * Section ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Section title.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Section description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Dashicons icon class.
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Fields in this section (built arrays or FieldBuilder instances).
	 *
	 * @var array<int, mixed>
	 */
	protected $fields = array();

	/**
	 * Layout groups (e.g., grid wrappers) referencing field ids.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $layout_groups = array();

	/**
	 * Whether this section declares option-backed fields.
	 *
	 * @var bool
	 */
	protected $options_enabled = false;

	/**
	 * Constructor.
	 *
	 * @param string $id          Section ID.
	 * @param string $title       Title.
	 * @param string $description Description.
	 */
	public function __construct( string $id, string $title = '', string $description = '' ) {
		$this->id          = $id;
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Set section title.
	 *
	 * @param string $title Title.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->title = $title;
		return $this;
	}

	/**
	 * Set section description.
	 *
	 * @param string $description Description.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->description = $description;
		return $this;
	}

	/**
	 * Set dashicons icon.
	 *
	 * @param string $icon Icon class.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Add a field using the shared Fields\Builder.
	 *
	 * @param string $id   Field ID.
	 * @param string $type Field type.
	 * @return \WPMoo\Fields\Builder
	 */
	public function field( string $id, string $type ): \WPMoo\Fields\Builder {
		$field           = new \WPMoo\Fields\Builder( $id, $type );
		$this->fields[]  = $field;
		return $field;
	}

	/**
	 * Append a list of prepared field definitions.
	 * Accepts arrays or FieldBuilder instances.
	 *
	 * @param array<int, mixed> $fields Fields array.
	 * @return $this
	 */
	public function fields( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->fields[] = $field;
		}
		return $this;
	}

	/**
	 * Define option fields (alias for fields() that marks options as enabled).
	 *
	 * @param array<int, mixed> $fields Field definitions.
	 * @return $this
	 */
	public function options( array $fields ): self {
		$this->enable_options();
		return $this->fields( $fields );
	}

	/**
	 * Mark the section as options-enabled.
	 *
	 * @return $this
	 */
	public function enable_options(): self {
		$this->options_enabled = true;
		return $this;
	}

	/**
	 * Whether the section contains option-backed fields.
	 *
	 * @return bool
	 */
	public function has_options(): bool {
		return $this->options_enabled;
	}

	/**
	 * Get section id.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Get layout.
	 *
	 * @return array<string, mixed>
	 */
	public function get_layout(): array {
		return empty( $this->layout_groups )
			? array()
			: array(
				'groups' => $this->layout_groups,
			);
	}

	/**
	 * Register a layout group (grid wrapper, etc).
	 *
	 * @param string              $type      Group type identifier.
	 * @param array<int, string>  $field_ids Field identifiers belonging to the group.
	 * @return $this
	 */
	public function add_layout_group( string $type, array $field_ids ): self {
		$type = strtolower( trim( $type ) );

		if ( '' === $type ) {
			return $this;
		}

		$ids = array();

		foreach ( $field_ids as $field_id ) {
			$normalized = trim( (string) $field_id );

			if ( '' === $normalized ) {
				continue;
			}

			$ids[] = $normalized;
		}

		if ( empty( $ids ) ) {
			return $this;
		}

		$this->layout_groups[] = array(
			'type'   => $type,
			'fields' => array_values( array_unique( $ids ) ),
		);

		return $this;
	}

	/**
	 * Build the section configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		$fields = array();

		foreach ( $this->fields as $field ) {
			if ( $field instanceof FieldBuilder ) {
				$fields[] = $field->build();
			} elseif ( $field instanceof LayoutBuilder ) {
				$fields[] = $field->build();
			} else {
				$fields[] = $field;
			}
		}

		return array(
			'id'          => $this->id,
			'title'       => $this->title,
			'description' => $this->description,
			'icon'        => $this->icon,
			'fields'      => $fields,
			'layout'      => $this->get_layout(),
			'options_enabled' => $this->options_enabled,
		);
	}
}
