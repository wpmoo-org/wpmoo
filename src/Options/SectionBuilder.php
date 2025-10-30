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

use WPMoo\Sections\SectionBuilder as BaseSectionBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fluent builder for option sections.
 * Extends the shared Sections\SectionBuilder for common props/layout.
 */
class SectionBuilder extends BaseSectionBuilder {
	/**
	 * Fields in this section.
	 *
	 * @var array<int, array<string, mixed>|FieldBuilder>
	 */
	protected $fields = array();

	/**
	 * Add a field to the section.
	 *
	 * @param string $id   Field ID.
	 * @param string $type Field type.
	 * @return FieldBuilder
	 */
	public function field( string $id, string $type ): FieldBuilder {
		$field          = new FieldBuilder( $id, $type );
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
			'id'          => $this->id(),
			'title'       => $this->title,
			'description' => $this->description,
			'icon'        => $this->icon,
			'fields'      => $fields,
			'layout'      => $this->get_layout(),
		);
	}
}
