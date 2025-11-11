<?php
/**
 * Tabs layout component rendered via Pico-inspired radio controls.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout\Tabs;

use WPMoo\Fields\BaseField;
use WPMoo\Fields\FieldBuilder;
use WPMoo\Fields\Manager;
use WPMoo\Layout\LayoutComponent;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Tabbed layout hosting nested fields per panel.
 */
class Tabs extends LayoutComponent {

	/**
	 * Tab items.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $items = array();

	/**
	 * Field manager reference.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Forbidden nested types.
	 *
	 * @var string[]
	 */
	protected $disallowed_types = array( 'tabs' );

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
	 * Render tab navigation and panels.
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Stored value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$value = is_array( $value ) ? $value : array();

		$output = $this->before_html();

		if ( empty( $this->items ) ) {
			$message = function_exists( '__' ) ? __( 'No tabs configured.', 'wpmoo' ) : 'No tabs configured.';
			$output .= '<div class="wpmoo-tabs wpmoo-tabs--empty"><p>' . esc_html( $message ) . '</p></div>';
			return $output . $this->after_html();
		}

		$tabs_id       = $this->id() ? sanitize_title( $this->id() ) : uniqid( 'wpmoo_tabs_', true );
		$control_name  = $tabs_id . '_control';
		$output       .= '<div class="wpmoo-tabs" data-wpmoo-tabs="' . esc_attr( $tabs_id ) . '">';
		$output       .= '<div class="wpmoo-tabs__controls">';

		foreach ( $this->items as $index => $item ) {
			$input_id   = $tabs_id . '__' . $item['id'];
			$panel_id   = $input_id . '__panel';
			$is_checked = 0 === $index ? ' checked="checked"' : '';

			$output .= '<input type="radio" class="wpmoo-tabs__control" name="' . esc_attr( $control_name ) . '" id="' . esc_attr( $input_id ) . '"' . $is_checked . ' />';
			$output .= '<label class="wpmoo-tabs__label" for="' . esc_attr( $input_id ) . '">';
			$output .= $this->render_icon_markup( $item );
			$output .= esc_html( $item['title'] );
			$output .= '</label>';

			$item_value = isset( $value[ $item['id'] ] ) && is_array( $value[ $item['id'] ] ) ? $value[ $item['id'] ] : array();
			$output    .= '<section class="wpmoo-tabs__panel" id="' . esc_attr( $panel_id ) . '">';

			if ( '' !== $item['description'] ) {
				$output .= '<p class="wpmoo-tabs__description">' . esc_html( $item['description'] ) . '</p>';
			}

			foreach ( $item['fields'] as $field_id => $field ) {
				$field_name  = $this->build_nested_input_name( $name, $item['id'], $field_id );
				$field_value = array_key_exists( $field_id, $item_value ) ? $item_value[ $field_id ] : $field->default();

				$output .= '<div class="wpmoo-field wpmoo-tabs__field">';
				$output .= $field->render( $field_name, $field_value );
				if ( $field->description() ) {
					$output .= '<small class="description">' . esc_html( $field->description() ) . '</small>';
				}
				$output .= '</div>';
			}

			$output .= '</section>';
		}

		$output .= '</div>';
		$output .= '</div>';

		return $output . $this->after_html();
	}

	/**
	 * Sanitize nested values.
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
				$submitted = isset( $value[ $item_id ][ $field_id ] ) ? $value[ $item_id ][ $field_id ] : null;
				$clean[ $item_id ][ $field_id ] = $field->sanitize( $submitted );
			}
		}

		return $clean;
	}

	/**
	 * Normalize items configuration.
	 *
	 * @param mixed $items Raw items.
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
			if ( '' === $title && isset( $item['label'] ) ) {
				$title = (string) $item['label'];
			}
			if ( '' === $title ) {
				/* translators: %d: Tab index starting from 1. */
				$title = function_exists( '__' ) ? sprintf( __( 'Tab %d', 'wpmoo' ), $index + 1 ) : 'Tab ' . ( $index + 1 );
			}

			$tab_type = isset( $item['type'] ) ? strtolower( (string) $item['type'] ) : 'tab';
			if ( 'tab' !== $tab_type ) {
				continue;
			}

			$section_id = isset( $item['id'] ) && '' !== (string) $item['id']
				? sanitize_title( (string) $item['id'] )
				: sanitize_title( $title . '-' . $index );

			$icon_type = isset( $item['icon_type'] ) ? strtolower( (string) $item['icon_type'] ) : 'dashicons';
			$icon_type = in_array( $icon_type, array( 'dashicons', 'fontawesome', 'url' ), true ) ? $icon_type : 'dashicons';
			$icon_value = isset( $item['icon'] ) ? (string) $item['icon'] : '';

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
				'icon_type'   => $icon_type,
				'icon'        => $icon_value,
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

	/**
	 * Render icon markup if configured.
	 *
	 * @param array<string, mixed> $item Tab definition.
	 * @return string
	 */
	protected function render_icon_markup( array $item ): string {
		$icon = isset( $item['icon'] ) ? (string) $item['icon'] : '';
		if ( '' === $icon ) {
			return '';
		}

		$icon_type = isset( $item['icon_type'] ) ? $item['icon_type'] : 'dashicons';

		switch ( $icon_type ) {
			case 'fontawesome':
				return '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i>';
			case 'url':
				return '<img src="' . esc_url( $icon ) . '" alt="" class="wpmoo-tabs__icon" />';
			case 'dashicons':
			default:
				return '<span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
		}
	}
}
