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
     * Render the header markup.
     */
    public function render( Page $page ): string {
        $configured_title = $page->config( 'framework_title' );
        $title = '' !== $this->title
            ? $this->title
            : ( ! empty( $configured_title ) ? (string) $configured_title : $this->default_title() );

        $page_title = $page->page_title();

        $html  = '<header class="wpmoo-header">';
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
