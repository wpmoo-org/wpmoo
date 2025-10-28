<?php
/**
 * Fluent section builder for metabox panels.
 *
 * @package WPMoo\Metabox
 * @since 0.3.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Metabox;

use WPMoo\Options\Field as OptionsFieldDefinition;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Defines a metabox section and its fields.
 */
class SectionBuilder {

	/**
	 * Section identifier.
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
	 * Section icon (dashicons).
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Fields included in the section.
	 *
	 * @var array<int, mixed>
	 */
	protected $fields = array();

	/**
	 * Constructor.
	 *
	 * @param string $id          Section identifier.
	 * @param string $title       Section title.
	 * @param string $description Section description.
	 */
	public function __construct( string $id, string $title = '', string $description = '' ) {
		$this->id          = $id;
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Set the section title.
	 *
	 * @param string $title Section title.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->title = $title;

		return $this;
	}

	/**
	 * Set the section description.
	 *
	 * @param string $description Section description.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->description = $description;

		return $this;
	}

	/**
	 * Set the section icon (dashicons class).
	 *
	 * @param string $icon Icon class name.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Add a field to the section.
	 *
	 * @param string $id   Field identifier.
	 * @param string $type Field type.
	 * @return FieldBuilder
	 */
	public function field( string $id, string $type ): FieldBuilder {
		$field = new FieldBuilder( $id, $type );

		$this->fields[] = $field;

		return $field;
	}

	/**
	 * Append multiple fields at once.
	 *
	 * Accepts arrays, metabox field builders, or option field definitions.
	 *
	 * @param mixed ...$fields Field definitions.
	 * @return $this
	 */
	public function fields( ...$fields ): self {
		if ( 1 === count( $fields ) && is_array( $fields[0] ) && $this->is_list_array( $fields[0] ) ) {
			$fields = $fields[0];
		}

		foreach ( $fields as $field ) {
			$this->fields[] = $field;
		}

		return $this;
	}

	/**
	 * Build the section configuration array.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		$fields = array();

		foreach ( $this->fields as $field ) {
			if ( $field instanceof FieldBuilder ) {
				$fields[] = $field->build();
				continue;
			}

			if ( $field instanceof OptionsFieldDefinition ) {
				$fields[] = $field->toArray();
				continue;
			}

			$fields[] = $field;
		}

		return array(
			'id'          => $this->id,
			'title'       => $this->title,
			'description' => $this->description,
			'icon'        => $this->icon,
			'fields'      => $fields,
		);
	}

	/**
	 * Determine whether an array is a sequential list.
	 *
	 * @param array<int|string, mixed> $items Candidate array.
	 * @return bool
	 */
	protected function is_list_array( array $items ): bool {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $items );
		}

		$expected = 0;

		foreach ( $items as $key => $_value ) {
			if ( $key !== $expected ) {
				return false;
			}

			++$expected;
		}

		return true;
	}
}
