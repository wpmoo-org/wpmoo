<?php

namespace WPMoo\Layout\Component;

use WPMoo\Layout\Abstracts\AbstractLayout;
use WPMoo\Layout\Interfaces\LayoutInterface;

/**
 * Container layout component that can hold other layout components like tabs, accordions, etc.
 *
 * @package WPMoo\Layout\Component
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Container extends AbstractLayout implements LayoutInterface {
	/**
	 * The type of container (e.g., 'tabs', 'accordion', 'grid', etc.)
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Item components of this container.
	 *
	 * @var array<mixed>
	 */
	private array $items = array();

	/**
	 * Constructor.
	 *
	 * @param string $id Layout ID.
	 * @param string $type Container type.
	 */
	public function __construct( string $id, string $type ) {
		$this->id = $id;
		$this->type = $type;
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
	 * Add an item component to this container.
	 *
	 * @param mixed $item The item component to add.
	 * @return self
	 */
	public function add_item( $item ): self {
		$this->items[] = $item;
		return $this;
	}

	/**
	 * Set multiple item components.
	 *
	 * @param array<mixed> $items Array of item components.
	 * @return self
	 */
	public function items( array $items ): self {
		$this->items = $items;
		return $this;
	}

	/**
	 * Get container type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get item components.
	 *
	 * @return array<mixed>
	 */
	public function get_items(): array {
		return $this->items;
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
