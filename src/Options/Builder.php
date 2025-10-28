<?php
/**
 * Fluent options page builder.
 *
 * @package WPMoo\Options
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Options;

use InvalidArgumentException;
use WPMoo\Fields\Manager;
use WPMoo\Support\Concerns\TranslatesStrings;

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
	 * Sections configuration.
	 *
	 * @var array<int, array<string, mixed>>
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
	 */
	public function __construct( string $option_key, Manager $field_manager ) {
		if ( empty( $option_key ) ) {
			throw new InvalidArgumentException( $this->translate( 'Option key cannot be empty.' ) );
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
	 * @param array<int, array<string, mixed>> $sections Sections array.
	 * @return $this
	 */
	public function sections( array $sections ): self {
		$this->config['sections'] = $sections;

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
