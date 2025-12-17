<?php

namespace WPMoo\Field\Type\Textarea;

use WPMoo\Field\Abstracts\AbstractField;

/**
 * Textarea field type.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Textarea extends AbstractField {
	/**
	 * Rows count.
	 *
	 * @var int
	 */
	protected int $rows = 5;

	/**
	 * Cols count.
	 *
	 * @var int|null
	 */
	protected ?int $cols = null;

	/**
	 * Set rows count.
	 *
	 * @param int $rows Rows count.
	 * @return self
	 */
	public function rows( int $rows ): self {
		$this->rows = $rows;
		return $this;
	}

	/**
	 * Set cols count.
	 *
	 * @param int $cols Cols count.
	 * @return self
	 */
	public function cols( int $cols ): self {
		$this->cols = $cols;
		return $this;
	}

	/**
	 * Get rows count.
	 *
	 * @return int
	 */
	public function get_rows(): int {
		return $this->rows;
	}

	/**
	 * Get cols count.
	 *
	 * @return int|null
	 */
	public function get_cols(): ?int {
		return $this->cols;
	}
}
