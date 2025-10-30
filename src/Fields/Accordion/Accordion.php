<?php
/**
 * Accordion field for grouping multiple inputs inside a collapsible container.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Accordion;

	use WPMoo\Fields\BaseField as Field;
	use WPMoo\Fields\FieldBuilder;
use WPMoo\Fields\Manager;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Renders an accordion made up of nested field definitions.
 */
class Accordion extends Field {

	/**
	 * Prepared accordion sections.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $sections = array();

	/**
	 * Field manager used for nested fields.
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
			: Manager::instance();

		$this->field_manager = $manager;

		$sections = array();

		if ( isset( $config['sections'] ) && is_array( $config['sections'] ) ) {
			$sections = $config['sections'];
		} elseif ( isset( $config['accordions'] ) && is_array( $config['accordions'] ) ) {
			// Support Codestar-style "accordions" key for familiarity.
			$sections = $config['accordions'];
		}

		// Remove custom keys before handing over to base constructor.
		unset( $config['sections'], $config['accordions'], $config['field_manager'] );

		parent::__construct( $config );

		$this->sections = $this->prepare_sections( $sections );
	}

	/**
	 * Render the accordion markup.
	 *
	 * @param string $name  Input base name.
	 * @param mixed  $value Saved value (expected array).
	 * @return string
	 */
	public function render( $name, $value ) {
		$value = is_array( $value ) ? $value : array();

		if ( empty( $this->sections ) ) {
			return '<p class="wpmoo-accordion__empty">' . esc_html( $this->empty_message() ) . '</p>';
		}

		ob_start();

		echo '<div class="wpmoo-accordion" data-wpmoo-accordion="' . esc_attr( $this->id() ) . '">';

		foreach ( $this->sections as $section ) {
			$this->render_section( $section, $name, $value );
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Sanitize nested field values.
	 *
	 * @param mixed $value Submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $value ) {
		$clean = array();
		$data  = is_array( $value ) ? $value : array();

		foreach ( $this->sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				$field_id           = $field->id();
				$field_value        = array_key_exists( $field_id, $data ) ? $data[ $field_id ] : $field->default();
				$clean[ $field_id ] = $field->sanitize( $field_value );
			}
		}

		return $clean;
	}

	/**
	 * Render a section and its nested fields.
	 *
	 * @param array<string, mixed> $section Section definition.
	 * @param string               $base_name Base input name.
	 * @param array<string, mixed> $values Saved values.
	 * @return void
	 */
	protected function render_section( array $section, $base_name, array $values ) {
		$title = $section['title'] ? $section['title'] : ucfirst( str_replace( array( '-', '_' ), ' ', $section['id'] ) );
		$open  = ! empty( $section['open'] );

		echo '<details class="wpmoo-accordion__item"' . ( $open ? ' open' : '' ) . '>';
		echo '<summary class="wpmoo-accordion__summary">';

		if ( ! empty( $section['icon'] ) ) {
			echo '<span class="wpmoo-accordion__icon ' . esc_attr( $section['icon'] ) . '"></span>';
		}

		echo '<span class="wpmoo-accordion__title">' . esc_html( $title ) . '</span>';
		echo '</summary>';

		if ( ! empty( $section['description'] ) ) {
			echo '<p class="wpmoo-accordion__description">' . esc_html( $section['description'] ) . '</p>';
		}

		echo '<div class="wpmoo-accordion__content">';

		foreach ( $section['fields'] as $field ) {
			$field_id    = $field->id();
			$field_name  = $base_name . '[' . $field_id . ']';
			$field_value = array_key_exists( $field_id, $values ) ? $values[ $field_id ] : $field->default();

			echo '<div class="wpmoo-field wpmoo-field-' . esc_attr( $field->type() ) . ' wpmoo-field--accordion">';

			if ( $field->label() ) {
				echo '<div class="wpmoo-title">';
				echo '<h4>' . esc_html( $field->label() ) . '</h4>';
				if ( $field->description() ) {
					echo '<div class="wpmoo-subtitle-text">' . esc_html( $field->description() ) . '</div>';
				}
				echo '</div>';
			}

			echo '<div class="wpmoo-fieldset">';
			echo $field->render( $field_name, $field_value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nested field handles escaping.
			echo '</div>';
			echo '<div class="clear"></div>';
			echo '</div>';
		}

		echo '</div>';
		echo '</details>';
	}

	/**
	 * Prepare accordion sections and instantiate nested fields.
	 *
	 * @param array<int, mixed> $sections Raw section definitions.
	 * @return array<int, array<string, mixed>>
	 */
	protected function prepare_sections( array $sections ) {
		$normalized = array();

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$defaults = array(
				'id'          => '',
				'title'       => '',
				'description' => '',
				'icon'        => '',
				'open'        => false,
				'fields'      => array(),
			);

			$section = array_merge( $defaults, $section );

			if ( '' === $section['id'] ) {
				$base          = $section['title'] ? $section['title'] : uniqid( 'accordion_', true );
				$section['id'] = Str::slug( $base );
			}

			$field_objects = array();

			if ( is_array( $section['fields'] ) ) {
				foreach ( $section['fields'] as $field_config ) {
					// Allow passing FieldBuilder instances for convenience.
					if ( $field_config instanceof FieldBuilder ) {
						$field_config = $field_config->build();
					}

					if ( ! is_array( $field_config ) || empty( $field_config['id'] ) ) {
						continue;
					}

					$field_config['field_manager'] = $this->field_manager;
					$field_objects[]               = $this->field_manager->make( $field_config );
				}
			}

			$section['fields'] = $field_objects;
			$normalized[]      = $section;
		}

		return $normalized;
	}

	/**
	 * Message shown when no sections are configured.
	 *
	 * @return string
	 */
	protected function empty_message() {
		if ( function_exists( '__' ) ) {
			return __( 'No accordion sections have been configured.', 'wpmoo' );
		}

		return 'No accordion sections have been configured.';
	}
}
