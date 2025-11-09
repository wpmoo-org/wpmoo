<?php
/**
 * Layout sidebar component (Pico-style accordion nav).
 */

namespace WPMoo\Layout;

use WPMoo\Page\Page;

class Sidebar {
	/**
	 * Whether to open only the active page group.
	 *
	 * @var bool
	 */
	protected $auto_open_active = true;

	/**
	 * Static constructor.
	 */
	public static function make(): self {
		return new self();
	}

	/**
	 * Control whether only the active page group is opened.
	 */
	public function auto_open_active( bool $enabled = true ): self {
		$this->auto_open_active = $enabled;
		return $this;
	}

	/**
	 * Render the sidebar markup using the nav registry.
	 *
	 * @param Page                           $page         Current page instance.
	 * @param array<int|string, array<mixed>> $nav_registry Registered pages/sections.
	 * @return string
	 */
	public function render( Page $page, array $nav_registry ): string {
		if ( empty( $nav_registry ) ) {
			return '';
		}

		$framework_title = $page->config( 'framework_title' );
		if ( empty( $framework_title ) ) {
			$framework_title = \function_exists( '__' ) ? \__( 'WPMoo Framework', 'wpmoo' ) : 'WPMoo Framework';
		}

		$nav_label = \function_exists( '__' ) ? \__( 'Sections menu', 'wpmoo' ) : 'Sections menu';
		$current_slug = (string) $page->config( 'menu_slug' );

		$html  = '<aside class="wpmoo-layout__sidebar" aria-label="' . \esc_attr( $nav_label ) . '">';
		$html .= '<header class="wpmoo-layout__brand">';
		$html .= '<p class="wpmoo-layout__framework">' . \esc_html( $framework_title ) . '</p>';
		$html .= '</header>';
		$html .= '<nav class="wpmoo-layout__nav-groups">';

		foreach ( $nav_registry as $slug => $entry ) {
			$is_current_page = (string) $slug === $current_slug;
			$page_title      = isset( $entry['title'] ) ? (string) $entry['title'] : \ucfirst( str_replace( '-', ' ', (string) $slug ) );
			$sections        = isset( $entry['sections'] ) && is_array( $entry['sections'] ) ? $entry['sections'] : array();

			$details_attr = '';
			if ( $this->auto_open_active && $is_current_page ) {
				$details_attr = ' open';
			}

			$summary_attr = $is_current_page ? ' aria-current="page"' : '';

			$html .= '<details class="wpmoo-nav-group"' . $details_attr . '>';
			$html .= '<summary' . $summary_attr . '>' . \esc_html( $page_title ) . '</summary>';

			if ( ! empty( $sections ) ) {
				$html .= '<ul>';
				$section_index = 0;
				$base_url      = '#';
				if ( ! $is_current_page ) {
					if ( \function_exists( 'admin_url' ) ) {
						$base_url = \admin_url( 'admin.php?page=' . $slug );
					} else {
						$base_url = 'admin.php?page=' . $slug;
					}
				}

				foreach ( $sections as $section ) {
					$section_id    = isset( $section['id'] ) ? (string) $section['id'] : '';
					$section_title = isset( $section['title'] ) ? (string) $section['title'] : \ucfirst( str_replace( '-', ' ', $section_id ) );

					if ( '' === $section_id ) {
						$section_id = 'section-' . ( $section_index + 1 );
					}

					$link_class   = 'wpmoo-layout__sub-link';
					$aria_current = '';
					if ( $is_current_page && 0 === $section_index ) {
						$link_class   .= ' is-active';
						$aria_current = ' aria-current="true"';
					}

					$target = $is_current_page ? '#' . $section_id : $base_url . '#' . $section_id;

					$html .= '<li>';
					$html .= '<a class="' . \esc_attr( $link_class ) . '" href="' . \esc_url( $target ) . '"' . $aria_current . '>';
					$html .= \esc_html( $section_title );
					$html .= '</a>';
					$html .= '</li>';

					$section_index++;
				}

				$html .= '</ul>';
			}

			$html .= '</details>';
		}

		$html .= '</nav>';
		$html .= '</aside>';

		return $html;
	}
}
