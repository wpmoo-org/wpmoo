<?php

namespace WPMoo\Layout\Abstracts;

use WPMoo\Layout\Interfaces\LayoutInterface;

/**
 * Base layout implementation.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
abstract class AbstractLayout implements LayoutInterface {
	/**
	 * Layout ID.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Parent ID to link to a page or other container.
	 *
	 * @var string
	 */
	protected string $parent = '';
}
