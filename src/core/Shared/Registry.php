<?php

namespace WPMoo\Shared;

use WPMoo\Page\Builders\PageBuilder;
use WPMoo\Layout\Component\Tabs;
use WPMoo\Layout\Component\Accordion;

/**
 * Shared registry for framework objects.
 *
 * @package WPMoo\Shared
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Registry {
	/**
	 * Registered pages.
	 *
	 * @var array<string, PageBuilder>
	 */
	private array $pages = [];

	/**
	 * Registered layouts.
	 *
	 * @var array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	private array $layouts = [];

	/**
	 * Singleton instance.
	 *
	 * @var ?self
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add a page to the registry.
	 *
	 * @param PageBuilder $page Page builder instance.
	 * @return void
	 */
	public function add_page( PageBuilder $page ): void {
		$this->pages[ $page->get_id() ] = $page;
	}

	/**
	 * Get a page by ID.
	 *
	 * @param string $id Page ID.
	 * @return PageBuilder|null
	 */
	public function get_page( string $id ): ?PageBuilder {
		return $this->pages[ $id ] ?? null;
	}

	/**
	 * Get all pages.
	 *
	 * @return array<string, PageBuilder>
	 */
	public function get_pages(): array {
		return $this->pages;
	}

	/**
	 * Add a layout to the registry.
	 *
	 * @param Tabs|Accordion $layout Layout component instance.
	 * @return void
	 */
	public function add_layout( $layout ): void {
		$this->layouts[ $layout->get_id() ] = $layout;
	}

	/**
	 * Get a layout by ID.
	 *
	 * @param string $id Layout ID.
	 * @return Tabs|Accordion|null
	 */
	public function get_layout( string $id ) {
		return $this->layouts[ $id ] ?? null;
	}

	/**
	 * Get all layouts.
	 *
	 * @return array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	public function get_layouts(): array {
		return $this->layouts;
	}

	/**
	 * Get layouts by parent ID.
	 *
	 * @param string $parentId Parent ID to filter by.
	 * @return array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion>
	 */
	public function get_layouts_by_parent( string $parentId ): array {
		$layouts = [];
		foreach ( $this->layouts as $layout ) {
			if ( $layout->get_parent() === $parentId ) {
				$layouts[ $layout->get_id() ] = $layout;
			}
		}
		return $layouts;
	}

	/**
	 * Private constructor for singleton pattern.
	 */
	private function __construct() {}
}
