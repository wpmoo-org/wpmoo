<?php

namespace WPMoo\Page\Interfaces;

/**
 * Page contract.
 *
 * Defines the contract for Page functionality.
 *
 * @package WPMoo\Page
 * @since 0.1.0
 * @link  https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link  https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
interface PageInterface {

	/**
	 * Set page capability.
	 *
	 * @param string $capability Capability required to access the page.
	 * @return self
	 */
	public function capability( string $capability ): self;

	/**
	 * Set menu slug.
	 *
	 * @param string $menu_slug Menu slug.
	 * @return self
	 */
	public function menu_slug( string $menu_slug ): self;

	/**
	 * Set menu position.
	 *
	 * @param int $position Menu position.
	 * @return self
	 */
	public function menu_position( int $position ): self;

	/**
	 * Set menu icon.
	 *
	 * @param string $icon Menu icon class.
	 * @return self
	 */
	public function menu_icon( string $icon ): self;

	/**
	 * Set parent slug.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @return self
	 */
	public function parent_slug( string $parent_slug ): self;

	/**
	 * Set page description.
	 *
	 * @param string $description Page description.
	 * @return self
	 */
	public function description( string $description ): self;

	/**
	 * Get page ID.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Get page title.
	 *
	 * @return string
	 */
	public function get_title(): string;

	/**
	 * Get capability.
	 *
	 * @return string
	 */
	public function get_capability(): string;

	/**
	 * Get menu slug.
	 *
	 * @return string
	 */
	public function get_menu_slug(): string;

	/**
	 * Get menu position.
	 *
	 * @return int
	 */
	public function get_menu_position(): int;

	/**
	 * Get menu icon.
	 *
	 * @return string
	 */
	public function get_menu_icon(): string;

	/**
	 * Get parent slug.
	 *
	 * @return string
	 */
	public function get_parent_slug(): string;

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function get_description(): string;
}
