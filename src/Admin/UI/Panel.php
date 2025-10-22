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

use WPMoo\Support\Concerns\EscapesOutput;
use WPMoo\Support\Concerns\GeneratesGridClasses;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a panel component that wraps sections inside a WordPress postbox.
 */
class Panel {
	use EscapesOutput;
	use GeneratesGridClasses;

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
	 * Whether multiple accordion sections can stay open simultaneously.
	 *
	 * @var bool
	 */
	protected $accordion_multi = false;

	/**
	 * Whether the panel should persist active section state.
	 *
	 * @var bool
	 */
	protected $persist = true;

	/**
	 * Active section identifier.
	 *
	 * @var string
	 */
	protected $active = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Panel configuration.
	 */
	public function __construct( array $config = array() ) {
		$defaults = array(
			'id'              => 'wpmoo-panel-' . uniqid(),
			'title'           => '',
			'sections'        => array(),
			'collapsible'     => true,
			'frame'           => true,
			'persist'         => true,
			'accordion_multi' => false,
			'active'          => '',
		);

		$config = array_merge( $defaults, $config );

		$this->id              = $config['id'];
		$this->title           = $config['title'];
		$this->collapsible     = (bool) $config['collapsible'];
		$this->frame           = (bool) $config['frame'];
		$this->persist         = (bool) $config['persist'] ;
		$this->accordion_multi = (bool) $config['accordion_multi'];
		$this->active          = is_string( $config['active'] ) ? $config['active'] : '';
		$this->sections        = $this->normalize_sections( $config['sections'] );
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
		$panel_id_attr         = $this->id ? $this->id : 'wpmoo-panel-' . uniqid();
		$active_section        = $this->resolve_active_section();

		if ( $this->frame ) {
			$classes[] = 'postbox';
		} else {
			$classes[] = 'wpmoo-panel--embedded';
		}

		echo '<div id="' . $this->esc_attr( $panel_id_attr ) . '" class="' . $this->esc_attr( implode( ' ', $classes ) ) . '" data-wpmoo-panel data-panel-id="' . $this->esc_attr( $panel_id_attr ) . '" data-panel-active="' . $this->esc_attr( $active_section ) . '" data-panel-persist="' . ( $this->persist ? '1' : '0' ) . '" data-panel-multi="' . ( $this->accordion_multi ? '1' : '0' ) . '">';

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
			echo '<nav class="wpmoo-panel__tabs" role="tablist" aria-label="' . $this->esc_attr__( 'Section navigation', 'wpmoo' ) . '">';
			foreach ( $this->sections as $index => $section ) {
				$section_id = $section['id'];
				$tab_id     = $section_id . '-tab';
				$is_active  = $section_id === $active_section || ( '' === $active_section && 0 === $index );
				$classes    = 'wpmoo-panel__tab' . ( $is_active ? ' is-active' : '' );
				$selected   = $is_active ? 'true' : 'false';

				echo '<button type="button" class="' . $this->esc_attr( $classes ) . '" id="' . $this->esc_attr( $tab_id ) . '" role="tab" aria-selected="' . $selected . '" aria-controls="' . $this->esc_attr( $section_id ) . '" data-panel-tab="' . $this->esc_attr( $section_id ) . '">';

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
			$section_id = $section['id'];
			$tab_id     = $section_id . '-tab';
			$is_active  = $section_id === $active_section || ( '' === $active_section && 0 === $index );
			$hidden     = $is_active ? 'false' : 'true';
			$expanded   = $is_active ? 'true' : 'false';

			$layout = array(
				'columns' => array(
					'default' => 12,
				),
			);

			if ( isset( $section['layout'] ) && is_array( $section['layout'] ) ) {
				$layout = array_merge( $layout, $section['layout'] );
			}

			if ( empty( $layout['columns'] ) || ! is_array( $layout['columns'] ) ) {
				$layout['columns'] = array(
					'default' => isset( $layout['size'] ) ? $layout['size'] : 12,
				);
			}

			$default_span = isset( $layout['columns']['default'] )
				? $this->normalise_grid_span( $layout['columns']['default'] )
				: 12;

			$section_classes = array( 'wpmoo-panel__section' );

			if ( $is_active ) {
				$section_classes[] = 'is-active';
			}

			$section_classes[] = 'wpmoo-col';
			$section_classes   = array_merge( $section_classes, $this->build_grid_classes( $layout['columns'] ) );
			$section_classes   = array_unique( array_filter( $section_classes ) );
			echo '<section id="' . $this->esc_attr( $section_id ) . '" class="' . $this->esc_attr( implode( ' ', $section_classes ) ) . '" data-panel-section="' . $this->esc_attr( $section_id ) . '" role="tabpanel" aria-hidden="' . $hidden . '" aria-labelledby="' . $this->esc_attr( $tab_id ) . '">';

			echo '<button type="button" class="wpmoo-panel__section-toggle' . ( $is_active ? ' is-active' : '' ) . '" data-panel-switch="' . $this->esc_attr( $section_id ) . '" aria-expanded="' . $expanded . '">';

			if ( $section['icon'] ) {
				echo '<span class="wpmoo-panel__section-toggle-icon dashicons ' . $this->esc_attr( $section['icon'] ) . '" aria-hidden="true"></span>';
			}

			echo '<span class="wpmoo-panel__section-toggle-label">' . $this->esc_html( $section['label'] ) . '</span>';
			echo '<span class="wpmoo-panel__section-toggle-indicator" aria-hidden="true"></span>';
			echo '</button>';

			echo '<div class="wpmoo-panel__section-body">';

			if ( $section['description'] ) {
				echo '<p class="wpmoo-panel__section-description">' . $this->esc_html( $section['description'] ) . '</p>';
			}

			echo $section['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is pre-escaped by the caller.

            echo '</div>'; // .wpmoo-panel__section-body

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
				'title'       => '',
				'description' => '',
				'icon'        => '',
				'content'     => '',
				'layout'      => array(),
			);

			$section = array_merge( $defaults, is_array( $section ) ? $section : array() );

			if ( '' === $section['id'] ) {
				$section['id'] = Str::slug( $section['label'] ? $section['label'] : uniqid( 'section_', true ) );
			}

			if ( '' === $section['label'] && ! empty( $section['title'] ) ) {
				$section['label'] = $section['title'];
			}

			if ( '' === $section['label'] ) {
				$section['label'] = ucfirst( str_replace( array( '-', '_' ), ' ', $section['id'] ) );
			}

			$normalized[] = $section;
		}

		return $normalized;
	}

	/**
	 * Resolve the active section identifier.
	 *
	 * @return string
	 */
	protected function resolve_active_section(): string {
		if ( $this->active ) {
			foreach ( $this->sections as $section ) {
				if ( $section['id'] === $this->active ) {
					return $this->active;
				}
			}
		}

		return isset( $this->sections[0]['id'] ) ? $this->sections[0]['id'] : '';
	}

	/**
	 * Slugify a string when WordPress helpers are unavailable.
	 *
	 * @param string $value Raw input.
	 * @return string
	 */
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

}
