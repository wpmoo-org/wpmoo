<?php
/**
 * Layout footer component.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout;

use WPMoo\Page\Page;

/**
 * Footer layout component for framework admin pages.
 */
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
