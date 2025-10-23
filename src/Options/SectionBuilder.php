<?php
/**
 * Fluent section builder for options pages.
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
 * Fluent builder for option sections.
 */
class SectionBuilder {
	use HasColumns;

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
	protected $title;

	/**
	 * Section description.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Section icon (dashicons class).
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Fields in this section.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $fields = array();

	/**
	 * Layout configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $layout = array(
		'size'    => 12,
		'columns' => array(
			'default' => 12,
		),
	);

	/**
	 * Constructor.
	 *
	 * @param string $id          Section ID.
	 * @param string $title       Section title.
	 * @param string $description Section description.
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
	 * Set section icon (dashicons class name).
	 *
	 * @param string $icon Dashicons class (e.g. dashicons-admin-generic).
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Define column span(s) for the section card.
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function size( ...$columns ): self {
		$parsed       = $this->parseColumnSpans( $columns );
		$this->layout = array(
			'size'    => $parsed['default'],
			'columns' => $parsed,
		);

		return $this;
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
	 * Add a field to the section.
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
	 * Build the section configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		$fields = array();

		foreach ( $this->fields as $field ) {
			if ( $field instanceof FieldBuilder ) {
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
			'layout'      => $this->layout,
		);
	}
}
