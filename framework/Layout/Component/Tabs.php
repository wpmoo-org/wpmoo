<?php

namespace WPMoo\Layout\Component;

use WPMoo\Layout\Abstracts\AbstractLayout;

/**
 * Tabs layout component.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Tabs extends AbstractLayout {
	/**
	 * Tabs orientation: horizontal or vertical.
	 *
	 * @var string
	 */
	private string $orientation = 'horizontal';

	/**
	 * Tab items configuration.
	 *
	 * @var array<int, array{id: string, title: string, content: array<mixed>}>
	 */
	private array $items = array();

	/**
	 * Constructor.
	 *
	 * @param string $id Layout ID.
	 */
	public function __construct( string $id ) {
		$this->id = $id;
	}

	/**
	 * Set parent ID.
	 *
	 * @param string $parent Parent ID to link to.
	 * @return self
	 */
	public function parent( string $parent ): self {
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Set tabs to vertical orientation.
	 *
	 * @return self
	 */
	public function vertical(): self {
		$this->orientation = 'vertical';
		return $this;
	}

	/**
	 * Set tabs to horizontal orientation (default).
	 *
	 * @return self
	 */
	public function horizontal(): self {
		$this->orientation = 'horizontal';
		return $this;
	}

	/**
	 * Set tab items using the common items structure.
	 *
	 * @param array<int, array{id: string, title: string, content: array<mixed>}> $items Array of tab configurations.
	 * @return self
	 */
	public function items( array $items ): self {
		$this->items = $items;
		return $this;
	}

	/**
	 * Get orientation.
	 *
	 * @return string
	 */
	public function get_orientation(): string {
		return $this->orientation;
	}

	/**
	 * Get items.
	 *
	 * @return array<int, array{id: string, title: string, content: array<mixed>}>
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Add a single tab to the tabs collection.
	 *
	 * @param string       $id Tab ID.
	 * @param string       $title Tab title.
	 * @param array<mixed> $content Array of fields or components.
	 * @return self
	 */
	public function add_tab( string $id, string $title, array $content = array() ): self {
		$this->items[] = array(
			'id'      => $id,
			'title'   => $title,
			'content' => $content,
		);
		return $this;
	}

	/**
	 * Get layout ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get parent ID.
	 *
	 * @return string
	 */
	public function get_parent(): string {
		return $this->parent;
	}
}
