<?php
/**
 * Layout footer component (form actions + credits).
 */

namespace WPMoo\Layout;

use WPMoo\Page\Page;

class Footer {
	/**
	 * Custom action text.
	 *
	 * @var string
	 */
	protected $submit_label = '';

	/**
	 * Static constructor.
	 */
	public static function make(): self {
		return new self();
	}

	/**
	 * Override the submit button label.
	 */
	public function submit_label( string $label ): self {
		$this->submit_label = $label;
		return $this;
	}

	/**
	 * Render footer markup.
	 */
	public function render( Page $page ): string {
		$label = '' !== $this->submit_label
			? $this->submit_label
			: ( \function_exists( '__' ) ? \__( 'Save Changes', 'wpmoo' ) : 'Save Changes' );

		$html  = '<footer class="wpmoo-options-actions">';
		if ( \function_exists( 'submit_button' ) ) {
			\ob_start();
			\submit_button( $label );
			$html .= (string) \ob_get_clean();
		} else {
			$html .= '<p class="submit"><button type="submit">' . \esc_html( $label ) . '</button></p>';
		}

		$html .= '</footer>';

		return $html;
	}
}
