<?php
/**
 * Accordion layout field (PicoCSS-based details/summary stack).
 *
 * @package WPMoo\Fields\Accordion
 */

namespace WPMoo\Fields\Accordion;

use WPMoo\Fields\BaseField;
use WPMoo\Fields\FieldBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Renders a list of collapsible items (details/summary) for layout content.
 *
 * This field does not store data; it simply renders structured content defined
 * via the builder's ->items() helper.
 */
class Accordion extends BaseField {
	/**
	 * Prepared accordion items.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $items = array();

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 */
	public function __construct( array $config ) {
		$config['save_field'] = false;
		parent::__construct( $config );
		$this->items = $this->normalize_items( isset( $config['items'] ) ? $config['items'] : array() );
	}

	/**
	 * Render the accordion markup.
	 *
	 * @param string $name  Field name (unused).
	 * @param mixed  $value Stored value (unused).
	 * @return string
	 */
	public function render( $name, $value ) {
		$output = $this->before_html();
		$label  = $this->label();
		$desc   = $this->description();
		$label_desc = $this->label_description();

		if ( '' !== $label || '' !== $desc || '' !== $label_desc ) {
			$output .= '<div class="wpmoo-accordion__intro">';
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
		}

		if ( empty( $this->items ) ) {
			$message = function_exists( '__' )
				? __( 'No accordion items have been configured.', 'wpmoo' )
				: 'No accordion items have been configured.';
			$output .= '<div class="wpmoo-accordion wpmoo-accordion--empty"><p>' . esc_html( $message ) . '</p></div>';
			return $output . $this->after_html();
		}

		$output .= '<div class="wpmoo-accordion" role="presentation">';
		foreach ( $this->items as $item ) {
			$open   = $item['open'];
			$open_attr = $open ? ' open' : '';
			$aria_expanded = $open ? 'true' : 'false';
			$aria_hidden   = $open ? 'false' : 'true';

			$output .= '<details class="wpmoo-accordion__item"' . $open_attr . '>';
			$output .= '<summary class="wpmoo-accordion__summary" role="button" tabindex="0" aria-expanded="' . esc_attr( $aria_expanded ) . '">';
			$output .= '<span class="wpmoo-accordion__summary-text">' . esc_html( $item['summary'] ) . '</span>';
			if ( '' !== $item['summary_description'] ) {
				$output .= '<small class="wpmoo-accordion__summary-description">' . $item['summary_description'] . '</small>';
			}
			$output .= '</summary>';
			$output .= '<div class="wpmoo-accordion__content" aria-hidden="' . esc_attr( $aria_hidden ) . '">';
			$output .= $item['content'];
			$output .= '</div>';
			$output .= '</details>';
		}
		$output .= '</div>';

		return $output . $this->after_html();
	}

	/**
	 * Normalize items configuration.
	 *
	 * @param mixed $items Raw items list.
	 * @return array<int, array<string, mixed>>
	 */
	protected function normalize_items( $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $items as $item ) {
			if ( $item instanceof FieldBuilder ) {
				$item = $item->build();
			}
			if ( ! is_array( $item ) ) {
				continue;
			}

			$summary = isset( $item['summary'] ) ? (string) $item['summary'] : '';
			if ( '' === trim( $summary ) ) {
				continue;
			}

			$content = '';
			if ( isset( $item['content'] ) ) {
				if ( is_callable( $item['content'] ) ) {
					$content = call_user_func( $item['content'], $this );
				} else {
					$content = (string) $item['content'];
				}
			}

			$normalized[] = array(
				'summary'             => $summary,
				'summary_description' => isset( $item['summary_description'] ) ? $this->sanitize_markup( $item['summary_description'] ) : '',
				'content'             => $this->sanitize_markup( $content ),
				'open'                => isset( $item['open'] ) ? (bool) $item['open'] : false,
			);
		}

		return $normalized;
	}
}
