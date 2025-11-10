<?php
/**
 * Accordion layout field that hosts nested WPMoo fields.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Accordion;

use WPMoo\Fields\BaseField;
use WPMoo\Fields\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Renders collapsible panels containing fully fledged fields.
 */
class Accordion extends BaseField {
	/**
	 * Prepared accordion items with nested fields.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $items = array();

	/**
	 * Field manager used to instantiate nested fields.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Forbidden nested field types (avoid recursion).
	 *
	 * @var string[]
	 */
	protected $disallowed_types = array( 'accordion' );

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 */
	public function __construct( array $config ) {
		$this->field_manager = isset( $config['field_manager'] ) && $config['field_manager'] instanceof Manager
			? $config['field_manager']
			: Manager::instance();

		parent::__construct( $config );

		$this->items = $this->normalize_items( isset( $config['items'] ) ? $config['items'] : array() );
	}

	/**
	 * Render the accordion markup.
	 *
	 * @param string $name  Field name (used when storing nested values).
	 * @param mixed  $value Stored value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$value  = is_array( $value ) ? $value : array();
		$output = $this->before_html();
		$output .= $this->render_intro_block();

		if ( empty( $this->items ) ) {
			$message = function_exists( '__' )
				? __( 'No accordion panels have been configured.', 'wpmoo' )
				: 'No accordion panels have been configured.';

			$output .= '<div class="wpmoo-accordion wpmoo-accordion--empty"><p>' . esc_html( $message ) . '</p></div>';
			$output .= $this->after_html();
			return $output;
		}

		$output .= '<div class="wpmoo-accordion" role="presentation">';

		foreach ( $this->items as $accordion ) {
			$open          = $accordion['open'];
			$open_attr     = $open ? ' open' : '';
			$aria_expanded = $open ? 'true' : 'false';
			$aria_hidden   = $open ? 'false' : 'true';

			$output .= '<details class="wpmoo-accordion__item"' . $open_attr . '>';
			$output .= '<summary class="wpmoo-accordion__summary" role="button" tabindex="0" aria-expanded="' . esc_attr( $aria_expanded ) . '">';

			if ( '' !== $accordion['icon'] ) {
				$output .= '<span class="wpmoo-accordion__summary-icon" aria-hidden="true"><i class="' . esc_attr( $accordion['icon'] ) . '"></i></span>';
			}

			$output .= '<span class="wpmoo-accordion__summary-text">' . esc_html( $accordion['title'] ) . '</span>';

			if ( '' !== $accordion['description'] ) {
				$output .= '<small class="wpmoo-accordion__summary-description">' . $accordion['description'] . '</small>';
			}

			$output .= '</summary>';
			$output .= '<div class="wpmoo-accordion__content" aria-hidden="' . esc_attr( $aria_hidden ) . '">';

			foreach ( $accordion['fields'] as $field_id => $field ) {
				$field_name  = $this->build_nested_input_name( $name, $field_id );
				$field_value = array_key_exists( $field_id, $value ) ? $value[ $field_id ] : $field->default();

				$output .= '<div class="wpmoo-field wpmoo-accordion__field">';
				$output .= $field->render( $field_name, $field_value );

				if ( $field->description() ) {
					$output .= '<small class="description">' . esc_html( $field->description() ) . '</small>';
				}

				$output .= '</div>';
			}

			$output .= '</div>';
			$output .= '</details>';
		}

		$output .= '</div>';

		$help = $this->help_html();
		if ( '' !== $help ) {
			$output .= '<small>' . $help . '</small>';
		}

		$output .= $this->after_html();

		return $output;
	}

	/**
	 * Sanitize nested field values.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		$value = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( $this->flatten_nested_fields() as $field_id => $field ) {
			if ( method_exists( $field, 'should_save' ) && ! $field->should_save() ) {
				continue;
			}

			$submitted         = array_key_exists( $field_id, $value ) ? $value[ $field_id ] : null;
			$clean[ $field_id ] = $field->sanitize( $submitted );
		}

		return $clean;
	}

	/**
	 * Render the introductory block with labels/descriptions.
	 *
	 * @return string
	 */
	protected function render_intro_block(): string {
		$label      = $this->label();
		$desc       = $this->description();
		$label_desc = $this->label_description();

		if ( '' === $label && '' === $desc && '' === $label_desc ) {
			return '';
		}

		$output = '<div class="wpmoo-accordion__intro">';

		if ( '' !== $label ) {
			$output .= '<h3 class="wpmoo-accordion__title">' . esc_html( $label ) . '</h3>';
		}

		if ( '' !== $label_desc ) {
			$output .= '<p class="wpmoo-accordion__label-desc">' . $this->sanitize_markup( $label_desc ) . '</p>';
		}

		if ( '' !== $desc ) {
			$output .= '<p class="wpmoo-accordion__description">' . esc_html( $desc ) . '</p>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Normalize accordion item definitions with nested fields.
	 *
	 * @param mixed $items Raw items config.
	 * @return array<int, array<string, mixed>>
	 */
	protected function normalize_items( $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $items as $index => $accordion ) {
			if ( ! is_array( $accordion ) ) {
				continue;
			}

			$title = isset( $accordion['title'] ) ? (string) $accordion['title'] : '';
			if ( '' === $title ) {

				$title = function_exists( '__' )
					/* translators: %d: Accordion index number. */
					? sprintf( __( 'Accordion %d', 'wpmoo' ), $index + 1 )
					: 'Accordion ' . ( $index + 1 );
			}

			$fields = array();
			if ( isset( $accordion['fields'] ) && is_array( $accordion['fields'] ) ) {
				foreach ( $accordion['fields'] as $field ) {
					$nested = $this->prepare_nested_field( $field );
					if ( null === $nested ) {
						continue;
					}

					$fields[ $nested->id() ] = $nested;
				}
			}

			if ( empty( $fields ) ) {
				continue;
			}

			$normalized[] = array(
				'title'       => $title,
				'description' => isset( $accordion['description'] ) ? $this->sanitize_markup( $accordion['description'] ) : '',
				'icon'        => isset( $accordion['icon'] ) ? (string) $accordion['icon'] : '',
				'open'        => isset( $accordion['open'] ) ? (bool) $accordion['open'] : false,
				'fields'      => $fields,
			);
		}

		return $normalized;
	}

	/**
	 * Prepare a nested field definition.
	 *
	 * @param mixed $field Field definition.
	 * @return BaseField|null
	 */
	protected function prepare_nested_field( $field ) {
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
	 * Build the flattened list of nested fields keyed by id.
	 *
	 * @return array<string, BaseField>
	 */
	protected function flatten_nested_fields(): array {
		$fields = array();

		foreach ( $this->items as $accordion ) {
			foreach ( $accordion['fields'] as $field ) {
				$fields[ $field->id() ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Build a proper nested input name.
	 *
	 * @param string $base     Base input name.
	 * @param string $field_id Nested field id.
	 * @return string
	 */
	protected function build_nested_input_name( string $base, string $field_id ): string {
		if ( '' === $field_id ) {
			return $base;
		}

		return $base . '[' . $field_id . ']';
	}
}
