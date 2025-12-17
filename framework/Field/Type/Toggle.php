<?php

namespace WPMoo\Field\Type;

use WPMoo\Field\Abstracts\AbstractField;

/**
 * Toggle field type.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Toggle extends AbstractField {
	/**
	 * On label.
	 *
	 * @var string
	 */
	protected string $on_label = 'On';

	/**
	 * Off label.
	 *
	 * @var string
	 */
	protected string $off_label = 'Off';

	/**
	 * Set on label.
	 *
	 * @param string $on_label On label.
	 * @return self
	 */
	public function on_label( string $on_label ): self {
		$this->on_label = $on_label;
		return $this;
	}

	/**
	 * Set off label.
	 *
	 * @param string $off_label Off label.
	 * @return self
	 */
	public function off_label( string $off_label ): self {
		$this->off_label = $off_label;
		return $this;
	}

	/**
	 * Get on label.
	 *
	 * @return string
	 */
	public function get_on_label(): string {
		return $this->on_label;
	}

	/**
	 * Get off label.
	 *
	 * @return string
	 */
	public function get_off_label(): string {
		return $this->off_label;
	}
}
