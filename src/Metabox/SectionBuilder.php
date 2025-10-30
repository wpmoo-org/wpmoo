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
use WPMoo\Sections\SectionBuilder as BaseSectionBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Defines a metabox section and its fields.
 * Extends the shared Sections\SectionBuilder for common props/layout.
 */
class SectionBuilder extends BaseSectionBuilder {
	/**
	 * Fields included in the section.
	 *
	 * @var array<int, mixed>
	 */
	protected $fields = array();

	/**
	 * Add a field to the section.
	 *
	 * @param string $id   Field identifier.
	 * @param string $type Field type.
	 * @return FieldBuilder
	 */
	public function field( string $id, string $type ): FieldBuilder {
		$field          = new FieldBuilder( $id, $type );
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
			'id'          => $this->id(),
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
