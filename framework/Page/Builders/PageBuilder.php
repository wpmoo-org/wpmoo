<?php

namespace WPMoo\Page\Builders;

use WPMoo\Page\Interfaces\PageInterface;
use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Page builder.
 *
 * @package WPMoo\Page
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class PageBuilder implements PageInterface {
	/**
	 * Page ID.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Page title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * Page capability.
	 *
	 * @var string
	 */
	private string $capability = 'manage_options';

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	private string $menu_slug;

	/**
	 * Menu position.
	 *
	 * @var int
	 */
	private int $menu_position = 100;

	/**
	 * Menu icon.
	 *
	 * @var string
	 */
	private string $menu_icon = '';

	/**
	 * Parent slug.
	 *
	 * @var string
	 */
	private string $parent_slug = '';

	/**
	 * Page description.
	 *
	 * @var string
	 */
	private string $description = '';

	/**
	 * Constructor.
	 *
	 * @param string $id Page ID.
	 * @param string $title Page title.
	 */
	public function __construct( string $id, string $title ) {
		$this->id    = $id;
		$this->title = $title;
		$this->menu_slug = $id; // Default to ID if menu slug is not specified.
	}

	/**
	 * Set page capability.
	 *
	 * @param string $capability Capability required to access the page.
	 * @return self
	 */
	public function capability( string $capability ): self {
		$this->capability = $capability;
		return $this;
	}

	/**
	 * Set menu slug.
	 *
	 * @param string $menu_slug Menu slug.
	 * @return self
	 */
	public function menu_slug( string $menu_slug ): self {
		$this->menu_slug = $menu_slug;
		return $this;
	}

	/**
	 * Set menu position.
	 *
	 * @param int $position Menu position.
	 * @return self
	 */
	public function menu_position( int $position ): self {
		$this->menu_position = $position;
		return $this;
	}

	/**
	 * Set menu icon.
	 *
	 * @param string $icon Menu icon class.
	 * @return self
	 */
	public function menu_icon( string $icon ): self {
		$this->menu_icon = $icon;
		return $this;
	}

	/**
	 * Set parent slug.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @return self
	 */
	public function parent_slug( string $parent_slug ): self {
		$this->parent_slug = $parent_slug;
		return $this;
	}

	/**
	 * Set page description.
	 *
	 * @param string $description Page description.
	 * @return self
	 */
	public function description( string $description ): self {
		$this->description = $description;
		return $this;
	}

	/**
	 * Get page ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get page title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get capability.
	 *
	 * @return string
	 */
	public function get_capability(): string {
		return $this->capability;
	}

	/**
	 * Get menu slug.
	 *
	 * @return string
	 */
	public function get_menu_slug(): string {
		return $this->menu_slug;
	}

	/**
	 * Get menu position.
	 *
	 * @return int
	 */
	public function get_menu_position(): int {
		return $this->menu_position;
	}

	/**
	 * Get menu icon.
	 *
	 * @return string
	 */
	public function get_menu_icon(): string {
		return $this->menu_icon;
	}

	/**
	 * Get parent slug.
	 *
	 * @return string
	 */
	public function get_parent_slug(): string {
		return $this->parent_slug;
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}
}
