<?php
/**
 * Layout header component.
 */

namespace WPMoo\Layout;

use WPMoo\Page\Page;

class Header {
	/**
	 * Optional title override.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Whether to show the current page title under the framework title.
	 *
	 * @var bool
	 */
	protected $show_page_title = false;

	/**
	 * Whether the header is sticky.
	 *
	 * @var bool
	 */
	protected $sticky = true;

	/**
	 * Sticky offset (CSS length).
	 *
	 * @var string
	 */
	protected $sticky_offset = '0px';

	/**
	 * Static constructor.
	 */
	public static function make(): self {
		return new self();
	}

	/**
	 * Override the header title.
	 */
	public function title( string $title ): self {
		$this->title = $title;
		return $this;
	}

	/**
	 * Display the page title beneath the framework heading.
	 */
	public function show_page_title( bool $show = true ): self {
		$this->show_page_title = $show;
		return $this;
	}

	/**
	 * Enable/disable sticky behavior.
	 *
	 * @param bool $enabled Sticky flag.
	 * @return $this
	 */
	public function sticky( bool $enabled = true ): self {
		$this->sticky = $enabled;
		return $this;
	}

	/**
	 * Set sticky offset (top).
	 *
	 * @param string $offset CSS length value.
	 * @return $this
	 */
	public function sticky_offset( string $offset ): self {
		$this->sticky_offset = $offset;
		return $this;
	}

	/**
	 * Render the header markup.
	 */
	public function render( Page $page ): string {
		$configured_title = $page->config( 'framework_title' );
		$title = '' !== $this->title
			? $this->title
			: ( ! empty( $configured_title ) ? (string) $configured_title : $this->default_title() );

		$page_title = $page->page_title();

		$classes = array( 'wpmoo-header' );
		$style   = '';
		if ( $this->sticky ) {
			$classes[] = 'wpmoo-sticky';
			if ( '' !== $this->sticky_offset ) {
				$style = ' style="--wpmoo-sticky-top:' . \esc_attr( $this->sticky_offset ) . ';"';
			}
		}

		$html  = '<header class="' . \esc_attr( implode( ' ', $classes ) ) . '"' . $style . '>';
		$html .= '<h1>' . \esc_html( $title ) . '</h1>';
		if ( $this->show_page_title && '' !== $page_title ) {
			$html .= '<p class="wpmoo-header__subtitle">' . \esc_html( $page_title ) . '</p>';
		}
		$html .= '</header>';

		return $html;
	}

	/**
	 * Default framework title fallback.
	 */
	protected function default_title(): string {
		return \function_exists( '__' ) ? \__( 'WPMoo Framework', 'wpmoo' ) : 'WPMoo Framework';
	}
}
