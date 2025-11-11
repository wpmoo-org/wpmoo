<?php
/**
 * Accordion layout component for grouping nested fields.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout\Accordion;

use WPMoo\Fields\BaseField;
use WPMoo\Fields\Manager;
use WPMoo\Layout\LayoutComponent;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Pico-styled accordion that hosts nested fields.
 */
class Accordion extends LayoutComponent {

	/**
	 * Prepared items.
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
	 * Disallowed nested types (avoid recursion).
	 *
	 * @var string[]
	 */
	protected $disallowed_types = array( 'accordion' );

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Component configuration.
	 */
	public function __construct( array $config ) {
		parent::__construct( $config );

		$this->field_manager = isset( $config['field_manager'] ) && $config['field_manager'] instanceof Manager
			? $config['field_manager']
			: Manager::instance();

		$this->items = $this->normalize_items( isset( $config['items'] ) ? $config['items'] : array() );
	}

	/**
	 * Render the accordion with nested fields.
	 *
	 * @param string $name  Input name root.
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
			return $output . $this->after_html();
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

		return $output . $this->after_html();
	}

	/**
	 * Sanitize nested values.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $value ) {
		$value = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( $this->flatten_nested_fields() as $field_id => $field ) {
			if ( method_exists( $field, 'should_save' ) && ! $field->should_save() ) {
				continue;
			}

			$submitted          = array_key_exists( $field_id, $value ) ? $value[ $field_id ] : null;
			$clean[ $field_id ] = $field->sanitize( $submitted );
		}

		return $clean;
	}

	/**
	 * Render the intro block (label + descriptions).
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
	 * Normalize accordion items.
	 *
	 * @param mixed $items Items definition.
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
	 * Prepare nested field definition.
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
	 * Flatten nested fields keyed by id.
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
	 * Build nested input name.
	 *
	 * @param string $base     Base name.
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
