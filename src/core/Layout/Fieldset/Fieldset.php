<?php
/**
 * Fieldset layout component (stacked grouped sections).
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout\Fieldset;

use WPMoo\Fields\BaseField;
use WPMoo\Fields\FieldBuilder;
use WPMoo\Fields\Manager;
use WPMoo\Layout\LayoutComponent;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fieldset layout component.
 */
class Fieldset extends LayoutComponent {

	/**
	 * Prepared sections.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $items = array();

	/**
	 * Field manager for nested fields.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Restricted nested types.
	 *
	 * @var string[]
	 */
	protected $disallowed_types = array( 'fieldset' );

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Layout configuration.
	 */
	public function __construct( array $config ) {
		parent::__construct( $config );

		$this->field_manager = isset( $config['field_manager'] ) && $config['field_manager'] instanceof Manager
			? $config['field_manager']
			: Manager::instance();

		$this->items = $this->normalize_items( isset( $config['items'] ) ? $config['items'] : array() );
	}

	/**
	 * Render the fieldset sections.
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$value = is_array( $value ) ? $value : array();

		$output = $this->before_html();

		if ( empty( $this->items ) ) {
			$empty = function_exists( '__' ) ? __( 'No fieldset items configured.', 'wpmoo' ) : 'No fieldset items configured.';
			$output .= '<div class="wpmoo-fieldset wpmoo-fieldset--empty"><p>' . esc_html( $empty ) . '</p></div>';
			return $output . $this->after_html();
		}

		$output .= '<div class="wpmoo-fieldset" role="group">';

		foreach ( $this->items as $item ) {
			$item_value = isset( $value[ $item['id'] ] ) && is_array( $value[ $item['id'] ] ) ? $value[ $item['id'] ] : array();

			$output .= '<section class="wpmoo-fieldset__section" id="' . esc_attr( $item['id'] ) . '">';
			$output .= '<header>';
			$output .= '<h3>' . esc_html( $item['title'] ) . '</h3>';
			if ( '' !== $item['description'] ) {
				$output .= '<p>' . esc_html( $item['description'] ) . '</p>';
			}
			$output .= '</header>';

			$output .= '<div class="wpmoo-fieldset__fields">';

			foreach ( $item['fields'] as $field_id => $field ) {
				$field_name  = $this->build_nested_input_name( $name, $item['id'], $field_id );
				$field_value = array_key_exists( $field_id, $item_value ) ? $item_value[ $field_id ] : $field->default();

				$output .= '<div class="wpmoo-field wpmoo-fieldset__field">';
				$output .= $field->render( $field_name, $field_value );
				if ( $field->description() ) {
					$output .= '<small class="description">' . esc_html( $field->description() ) . '</small>';
				}
				$output .= '</div>';
			}

			$output .= '</div>';
			$output .= '</section>';
		}

		$output .= '</div>';

		return $output . $this->after_html();
	}

	/**
	 * Sanitize nested field values.
	 *
	 * @param mixed $value Raw value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $value ) {
		$value = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( $this->items as $item ) {
			$item_id = $item['id'];
			$clean[ $item_id ] = array();

			foreach ( $item['fields'] as $field_id => $field ) {
				$submitted               = isset( $value[ $item_id ][ $field_id ] ) ? $value[ $item_id ][ $field_id ] : null;
				$clean[ $item_id ][ $field_id ] = $field->sanitize( $submitted );
			}
		}

		return $clean;
	}

	/**
	 * Normalize configuration.
	 *
	 * @param mixed $items Raw config.
	 * @return array<int, array<string, mixed>>
	 */
	protected function normalize_items( $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $items as $index => $item ) {
			if ( $item instanceof FieldBuilder ) {
				$item = $item->build();
			}

			if ( ! is_array( $item ) ) {
				continue;
			}

			$title = isset( $item['title'] ) ? (string) $item['title'] : '';
			if ( '' === $title ) {
				$title = function_exists( '__' )
					? sprintf( __( 'Fieldset %d', 'wpmoo' ), $index + 1 )
					: 'Fieldset ' . ( $index + 1 );
			}

			$section_id = isset( $item['id'] ) && '' !== (string) $item['id']
				? sanitize_title( (string) $item['id'] )
				: sanitize_title( $title . '-' . $index );

			$fields = array();
			if ( isset( $item['fields'] ) && is_array( $item['fields'] ) ) {
				foreach ( $item['fields'] as $field ) {
					$prepared = $this->prepare_nested_field( $field );
					if ( null === $prepared ) {
						continue;
					}
					$fields[ $prepared->id() ] = $prepared;
				}
			}

			if ( empty( $fields ) ) {
				continue;
			}

			$normalized[] = array(
				'id'          => $section_id,
				'title'       => $title,
				'description' => isset( $item['description'] ) ? (string) $item['description'] : '',
				'fields'      => $fields,
			);
		}

		return $normalized;
	}

	/**
	 * Instantiate nested field.
	 *
	 * @param mixed $field Field definition.
	 * @return BaseField|null
	 */
	protected function prepare_nested_field( $field ) {
		if ( $field instanceof FieldBuilder ) {
			$field = $field->build();
		}

		if ( ! is_array( $field ) ) {
			return null;
		}

		if ( empty( $field['id'] ) || empty( $field['type'] ) ) {
			return null;
		}

		if ( in_array( $field['type'], $this->disallowed_types, true ) ) {
			return null;
		}

		$field['field_manager'] = $this->field_manager;

		try {
			return $this->field_manager->make( $field );
		} catch ( \Throwable $exception ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
			return null;
		}
	}

	/**
	 * Build nested input name.
	 *
	 * @param string $base       Base input name.
	 * @param string $section_id Section identifier.
	 * @param string $field_id   Field identifier.
	 * @return string
	 */
	protected function build_nested_input_name( string $base, string $section_id, string $field_id ): string {
		if ( '' === $field_id ) {
			return $base;
		}

		if ( '' === $section_id ) {
			return $base . '[' . $field_id . ']';
		}

		return $base . '[' . $section_id . '][' . $field_id . ']';
	}
}
