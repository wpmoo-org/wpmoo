<?php

namespace WPMoo\Layout\Contracts;

/**
 * Layout contract.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
interface LayoutInterface {
	/**
	 * Get layout ID.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Get parent ID.
	 *
	 * @return string
	 */
	public function get_parent(): string;

	/**
	 * Set parent ID.
	 *
	 * @param string $parent Parent ID to link to.
	 * @return self
	 */
	public function parent( string $parent ): self;
}
