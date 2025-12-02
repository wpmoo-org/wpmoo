<?php

namespace WPMoo\Layout\Component;

use WPMoo\Layout\Abstracts\AbstractLayout;

/**
 * Accordion layout component.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Accordion extends AbstractLayout {
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
