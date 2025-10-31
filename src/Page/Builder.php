<?php
/**
 * Fluent options page builder.
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Page;

use InvalidArgumentException;
use WPMoo\Fields\Manager;
use WPMoo\Sections\SectionBuilder;
use WPMoo\Support\Concerns\TranslatesStrings;
use WPMoo\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fluent builder for options pages.
 */
class Builder {
	use TranslatesStrings;

	/**
	 * Option key.
	 *
	 * @var string
	 */
	protected $option_key;

	/**
	 * Page configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $config = array();

	/**
	 * Sections configuration (builders only; arrays are normalized via sections()).
	 *
	 * @var array<int, SectionBuilder>
	 */
	protected $sections = array();

	/**
	 * Field manager instance.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Whether the builder has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * Cached page instance after registration.
	 *
	 * @var Page|null
	 */
	protected $page = null;

	/**
	 * Constructor.
	 *
	 * @param string  $option_key    Option key.
	 * @param Manager $field_manager Field manager.
	 * @throws InvalidArgumentException When option key is empty.
	 */
	public function __construct( string $option_key, Manager $field_manager ) {
		if ( empty( $option_key ) ) {
			/* phpcs:disable WordPress.Security.EscapeOutput */
			throw new InvalidArgumentException( $this->translate( 'Option key cannot be empty.' ) );
			/* phpcs:enable WordPress.Security.EscapeOutput */
		}

		$this->option_key    = $option_key;
		$this->field_manager = $field_manager;
		$this->config        = array(
			'option_key' => $option_key,
			'menu_slug'  => $option_key,
		);
	}

	/**
	 * Set page title.
	 *
	 * @param string $title Page title.
	 * @return $this
	 */
	public function pageTitle( string $title ): self {
		$this->config['page_title'] = $title;

		return $this;
	}

	/**
	 * Set menu title.
	 *
	 * @param string $title Menu title.
	 * @return $this
	 */
	public function menuTitle( string $title ): self {
		$this->config['menu_title'] = $title;

		return $this;
	}

	/**
	 * Set menu slug.
	 *
	 * @param string $slug Menu slug.
	 * @return $this
	 */
	public function menuSlug( string $slug ): self {
		$this->config['menu_slug'] = $slug;

		return $this;
	}

	/**
	 * Set parent slug (for submenu).
	 *
	 * @param string $parent Parent slug.
	 * @return $this
	 */
	public function parentSlug( string $parent ): self {
		$this->config['parent_slug'] = $parent;

		return $this;
	}

	/**
	 * Set capability required.
	 *
	 * @param string $capability Capability.
	 * @return $this
	 */
	public function capability( string $capability ): self {
		$this->config['capability'] = $capability;

		return $this;
	}

	/**
	 * Set menu icon.
	 *
	 * @param string $icon Icon URL or dashicon class.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->config['icon'] = $icon;

		return $this;
	}

	/**
	 * Set menu position.
	 *
	 * @param int $position Position.
	 * @return $this
	 */
	public function position( int $position ): self {
		$this->config['position'] = $position;

		return $this;
	}

	/**
	 * Add a section with fields.
	 *
	 * @param string $id          Section ID.
	 * @param string $title       Section title.
	 * @param string $description Section description.
	 * @return SectionBuilder
	 */
	public function section( string $id, string $title = '', string $description = '' ): SectionBuilder {
		$section = new SectionBuilder( $id, $title, $description );

		$this->sections[] = $section;

		return $section;
	}

	/**
	 * Add sections from array (backward compatibility).
	 *
	 * Accepts a list of section arrays or SectionBuilder instances.
	 * Normalizes arrays to SectionBuilder to keep a single code path.
	 *
	 * @param array<int, mixed> $sections Sections array or builders.
	 * @return $this
	 * @throws InvalidArgumentException When a section definition is invalid.
	 */
	public function sections( array $sections ): self {
		foreach ( $sections as $section ) {
			if ( $section instanceof SectionBuilder ) {
				$this->sections[] = $section;
				continue;
			}

			if ( is_array( $section ) ) {
				$id          = isset( $section['id'] ) && is_string( $section['id'] ) ? $section['id'] : '';
				$title       = isset( $section['title'] ) && is_string( $section['title'] ) ? $section['title'] : '';
				$description = isset( $section['description'] ) && is_string( $section['description'] ) ? $section['description'] : '';

				if ( '' === $id ) {
					$id = '' !== $title ? $title : ( 'section_' . uniqid() );
				}

				$builder = new SectionBuilder( $id, $title, $description );

				if ( ! empty( $section['icon'] ) && is_string( $section['icon'] ) ) {
					$builder->icon( $section['icon'] );
				}

				if ( isset( $section['layout'] ) && is_array( $section['layout'] ) && isset( $section['layout']['columns'] ) && is_array( $section['layout']['columns'] ) ) {
					$builder->size( $section['layout']['columns'] );
				}

				if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
					$builder->fields( $section['fields'] );
				}

				$this->sections[] = $builder;
				continue;
			}

			/* phpcs:disable WordPress.Security.EscapeOutput */
			throw new InvalidArgumentException( $this->translate( 'Invalid section definition provided to sections().' ) );
			/* phpcs:enable WordPress.Security.EscapeOutput */
		}

		// Ensure we don't accidentally bypass builder normalization later.
		unset( $this->config['sections'] );

		return $this;
	}

	/**
	 * Generic config setter.
	 *
	 * @param string $key   Config key.
	 * @param mixed  $value Config value.
	 * @return $this
	 */
	public function config( string $key, $value ): self {
		$this->config[ $key ] = $value;

		return $this;
	}

	/**
	 * Register the options page.
	 *
	 * @return Page
	 */
	public function register(): Page {
		if ( $this->registered && $this->page instanceof Page ) {
			return $this->page;
		}

		// Build sections from SectionBuilder instances.
		if ( ! empty( $this->sections ) && ! isset( $this->config['sections'] ) ) {
			$this->config['sections'] = array();

			foreach ( $this->sections as $section_builder ) {
				$this->config['sections'][] = $section_builder->build();
			}
		}

		$this->page = new Page( $this->config, $this->field_manager );
		$this->page->boot();

		// Register in Options static cache.
		Options::registerPage( $this->page );

		$this->registered = true;

		return $this->page;
	}
}
