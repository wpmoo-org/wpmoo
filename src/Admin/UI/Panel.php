<?php
/**
 * Shared admin panel container with WordPress postbox styling.
 *
 * @package WPMoo\Admin
 * @since 0.3.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Admin\UI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a panel component that wraps sections inside a WordPress postbox.
 */
class Panel {

	/**
	 * Panel identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Panel title.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Panel sections.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $sections = array();

	/**
	 * Whether the panel header is collapsible.
	 *
	 * @var bool
	 */
	protected $collapsible = true;

	/**
	 * Whether to wrap the panel in a postbox frame.
	 *
	 * @var bool
	 */
	protected $frame = true;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Panel configuration.
	 */
	public function __construct( array $config = array() ) {
		$defaults = array(
			'id'          => 'wpmoo-panel-' . uniqid(),
			'title'       => '',
			'sections'    => array(),
			'collapsible' => true,
			'frame'       => true,
		);

		$config = array_merge( $defaults, $config );

		$this->id          = $config['id'];
		$this->title       = $config['title'];
		$this->collapsible = (bool) $config['collapsible'];
		$this->frame       = (bool) $config['frame'];
		$this->sections    = $this->normalize_sections( $config['sections'] );
	}

	/**
	 * Create a new instance.
	 *
	 * @param array<string, mixed> $config Panel configuration.
	 * @return static
	 */
	public static function make( array $config = array() ): self {
		return new self( $config );
	}

	/**
	 * Render the panel markup.
	 *
	 * @return string
	 */
	public function render(): string {
		ob_start();

		$has_multiple_sections = count( $this->sections ) > 1;
		$classes               = array( 'wpmoo-panel' );

		if ( $this->frame ) {
			$classes[] = 'postbox';
		} else {
			$classes[] = 'wpmoo-panel--embedded';
		}

		echo '<div id="' . $this->esc_attr( $this->id ) . '" class="' . $this->esc_attr( implode( ' ', $classes ) ) . '" data-wpmoo-panel>';

		if ( $this->frame ) {
			echo '<div class="postbox-header">';
			echo '<h2 class="hndle">' . $this->esc_html( $this->title ) . '</h2>';

			if ( $this->collapsible ) {
				echo '<div class="handle-actions hide-if-no-js">';
				echo '<button type="button" class="handlediv" aria-expanded="true">';
				echo '<span class="screen-reader-text">' . $this->esc_html( $this->toggle_text() ) . '</span>';
				echo '<span class="toggle-indicator" aria-hidden="true"></span>';
				echo '</button>';
				echo '</div>';
			}

			echo '</div>'; // .postbox-header
			echo '<div class="inside">';
		}

		echo '<div class="wpmoo-panel__layout">';

		if ( $has_multiple_sections ) {
			echo '<nav class="wpmoo-panel__tabs" aria-label="' . $this->esc_attr__( 'Section navigation', 'wpmoo' ) . '">';
			foreach ( $this->sections as $index => $section ) {
				$is_active = 0 === $index ? ' is-active' : '';
				echo '<button type="button" class="wpmoo-panel__tab' . $is_active . '" data-panel-tab="' . $this->esc_attr( $section['id'] ) . '">';

				if ( $section['icon'] ) {
					echo '<span class="wpmoo-panel__tab-icon dashicons ' . $this->esc_attr( $section['icon'] ) . '" aria-hidden="true"></span>';
				}

				echo '<span class="wpmoo-panel__tab-label">' . $this->esc_html( $section['label'] ) . '</span>';
				echo '</button>';
			}
			echo '</nav>';
		}

		echo '<div class="wpmoo-panel__content">';

		foreach ( $this->sections as $index => $section ) {
			$is_active = 0 === $index ? ' is-active' : '';
			$hidden    = 0 === $index ? 'false' : 'true';

			echo '<section id="' . $this->esc_attr( $section['id'] ) . '" class="wpmoo-panel__section' . $is_active . '" data-panel-section="' . $this->esc_attr( $section['id'] ) . '" aria-hidden="' . $hidden . '">';

			if ( $section['description'] ) {
				echo '<p class="wpmoo-panel__section-description">' . $this->esc_html( $section['description'] ) . '</p>';
			}

			echo $section['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is pre-escaped by the caller.

			echo '</section>';
		}

		echo '</div>'; // .wpmoo-panel__content
		echo '</div>'; // .wpmoo-panel__layout

		if ( $this->frame ) {
			echo '</div>'; // .inside
		}

		echo '</div>'; // .wpmoo-panel wrapper

		return ob_get_clean();
	}

	/**
	 * Normalize panel sections.
	 *
	 * @param array<int, array<string, mixed>> $sections Section configuration.
	 * @return array<int, array<string, mixed>>
	 */
	protected function normalize_sections( array $sections ): array {
		$normalized = array();

		foreach ( $sections as $section ) {
			$defaults = array(
				'id'          => '',
				'label'       => '',
				'description' => '',
				'icon'        => '',
				'content'     => '',
			);

			$section = array_merge( $defaults, is_array( $section ) ? $section : array() );

			if ( '' === $section['id'] ) {
				$section['id'] = $this->slugify( $section['label'] ? $section['label'] : uniqid( 'section_', true ) );
			}

			if ( '' === $section['label'] ) {
				$section['label'] = ucfirst( str_replace( array( '-', '_' ), ' ', $section['id'] ) );
			}

			$normalized[] = $section;
		}

		return $normalized;
	}

	/**
	 * Slugify a string when WordPress helpers are unavailable.
	 *
	 * @param string $value Raw input.
	 * @return string
	 */
	protected function slugify( $value ) {
		if ( function_exists( 'sanitize_title' ) ) {
			return sanitize_title( $value );
		}

		$value = strtolower( preg_replace( '/[^a-zA-Z0-9]+/', '-', $value ) );

		return trim( $value, '-' );
	}

	/**
	 * Build the toggle button text.
	 *
	 * @return string
	 */
	protected function toggle_text(): string {
		if ( function_exists( '__' ) ) {
			return sprintf(
				/* translators: %s: panel title */
				__( 'Toggle panel: %s', 'wpmoo' ),
				$this->title
			);
		}

		return 'Toggle panel: ' . $this->title;
	}

	/**
	 * Escape HTML output.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_html( $value ) {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Escape attribute output.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_attr( $value ) {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Escape attribute output with translation.
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	protected function esc_attr__( $text, $domain ) {
		if ( function_exists( 'esc_attr__' ) ) {
			return esc_attr__( $text, $domain );
		}

		return $this->esc_attr( $text );
	}
}
