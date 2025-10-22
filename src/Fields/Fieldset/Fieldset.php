<?php
/**
 * Fieldset field for grouping related controls.
 *
 * @package WPMoo\Fields
 * @since 0.5.0
 * @link https://wpmoo.org
 * @license GPL-3.0-or-later
 */

namespace WPMoo\Fields\Fieldset;

use InvalidArgumentException;
use WPMoo\Fields\Field;
use WPMoo\Fields\Manager;
use WPMoo\Options\Field as FieldDefinition;
use WPMoo\Options\FieldBuilder as FieldDefinitionBuilder;
use WPMoo\Support\Concerns\GeneratesGridClasses;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a fieldset containing nested inputs.
 */
class Fieldset extends Field {
    use GeneratesGridClasses;

	/**
	 * Nested field instances.
	 *
	 * @var array<int, Field>
	 */
	protected $fields = array();

	/**
	 * Field manager for nested inputs.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 */
	public function __construct( array $config ) {
		$manager = isset( $config['field_manager'] ) && $config['field_manager'] instanceof Manager
			? $config['field_manager']
			: new Manager();

		$this->field_manager = $manager;

		$nested_fields = array();

		if ( isset( $config['fields'] ) && is_array( $config['fields'] ) ) {
			$nested_fields = $config['fields'];
		}

		unset( $config['fields'], $config['field_manager'] );

		parent::__construct( $config );

		$this->fields = $this->prepare_fields( $nested_fields );
	}

	/**
	 * Render the fieldset markup.
	 *
	 * @param string $name  Input base name.
	 * @param mixed  $value Saved value (array expected).
	 * @return string
	 */
	public function render( $name, $value ) {
		$values = is_array( $value ) ? $value : array();

		if ( empty( $this->fields ) ) {
			return '';
		}

		ob_start();

		$gutter = $this->layout( 'gutter' );
		$gutter = is_string( $gutter ) && '' !== trim( $gutter ) ? trim( strtolower( $gutter ) ) : 'lg';
		$gutter = preg_replace( '/[^a-z0-9]/', '', $gutter );
		if ( '' === $gutter ) {
			$gutter = 'lg';
		}

		$grid_classes = array(
			'wpmoo-grid',
			'wpmoo-grid--fields',
			'wpmoo-grid--guttered',
			'wpmoo-fieldset__grid',
			'gutter-' . $gutter,
		);

		echo '<div class="wpmoo-fieldset-group" data-wpmoo-fieldset="' . $this->esc_attr( $this->id() ) . '">';
		echo '<div class="' . $this->esc_attr( implode( ' ', $grid_classes ) ) . '">';

		foreach ( $this->fields as $field ) {
			$field_id    = $field->id();
			$field_name  = $name . '[' . $field_id . ']';
			$field_value = array_key_exists( $field_id, $values ) ? $values[ $field_id ] : $field->default();

			$columns = $field->layout( 'columns' );

			if ( ! is_array( $columns ) || empty( $columns ) ) {
				$columns = array(
					'default' => 12,
				);
			}

			$classes = array(
				'wpmoo-field',
				'wpmoo-field-' . $field->type(),
				'wpmoo-field--nested',
				'wpmoo-col',
			);

			$classes = array_merge( $classes, $this->build_grid_classes( $columns ) );
			$classes = array_unique( array_filter( $classes ) );

			echo '<div class="' . $this->esc_attr( implode( ' ', $classes ) ) . '">';

			if ( $field->label() ) {
				echo '<div class="wpmoo-title">';
				echo '<h4>' . $this->esc_html( $field->label() ) . '</h4>';

				if ( $field->description() ) {
					echo '<div class="wpmoo-subtitle-text">' . $this->esc_html( $field->description() ) . '</div>';
				}

				echo '</div>';
			}

			echo '<div class="wpmoo-fieldset__body">';

			if ( $field->before() ) {
				echo '<div class="wpmoo-field-before">' . $field->before_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '<div class="wpmoo-fieldset__control">';
			echo $field->render( $field_name, $field_value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';

			if ( $field->after() ) {
				echo '<div class="wpmoo-field-after">' . $field->after_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( $field->help() ) {
				echo '<p class="wpmoo-field-help">' . $field->help_html() . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</div>'; // .wpmoo-fieldset__body
			echo '</div>'; // .wpmoo-field
		}

		echo '</div>'; // .wpmoo-fieldset__grid
		echo '</div>'; // .wpmoo-fieldset-group wrapper

		return ob_get_clean();
	}

	/**
	 * Sanitize nested field values.
	 *
	 * @param mixed $value Submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $value ) {
		$clean  = array();
		$values = is_array( $value ) ? $value : array();

		foreach ( $this->fields as $field ) {
			$field_id    = $field->id();
			$field_value = array_key_exists( $field_id, $values ) ? $values[ $field_id ] : $field->default();
			$clean[ $field_id ] = $field->sanitize( $field_value );
		}

		return $clean;
	}

	/**
	 * Convert raw field definitions into Field instances.
	 *
	 * @param array<int, mixed> $fields Raw field definitions.
	 * @return array<int, Field>
	 */
	protected function prepare_fields( array $fields ) {
		$instances = array();

		foreach ( $fields as $field ) {
			if ( $field instanceof Field ) {
				$instances[] = $field;
				continue;
			}

			if ( $field instanceof FieldDefinitionBuilder ) {
				$field = $field->build();
			}

			if ( $field instanceof FieldDefinition ) {
				$field = $field->toArray();
			}

			if ( is_array( $field ) ) {
				if ( empty( $field['id'] ) ) {
					throw new InvalidArgumentException( 'Fieldset nested fields require an "id" value.' );
				}

				$field['field_manager'] = $this->field_manager;

				$instances[] = $this->field_manager->make( $field );
			}
		}

		return $instances;
	}

}

