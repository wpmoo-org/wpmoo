<?php

namespace WPMoo\Field\Type;

use WPMoo\Field\Abstracts\AbstractField;

/**
 * Input field type.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Input extends AbstractField {
	/**
	 * Input type.
	 *
	 * @var string
	 */
	protected string $type = 'text';

	/**
	 * Min value.
	 *
	 * @var mixed
	 */
	protected $min = null;

	/**
	 * Max value.
	 *
	 * @var mixed
	 */
	protected $max = null;

	/**
	 * Step value.
	 *
	 * @var mixed
	 */
	protected $step = null;

	/**
	 * Set input type.
	 *
	 * @param string $type Input type.
	 * @return self
	 */
	public function type( string $type ): self {
		$this->type = $type;
		return $this;
	}

	/**
	 * Set min value.
	 *
	 * @param mixed $min Min value.
	 * @return self
	 */
	public function min( $min ): self {
		$this->min = $min;
		return $this;
	}

	/**
	 * Set max value.
	 *
	 * @param mixed $max Max value.
	 * @return self
	 */
	public function max( $max ): self {
		$this->max = $max;
		return $this;
	}

	/**
	 * Set step value.
	 *
	 * @param mixed $step Step value.
	 * @return self
	 */
	public function step( $step ): self {
		$this->step = $step;
		return $this;
	}

	/**
	 * Get input type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get min value.
	 *
	 * @return mixed
	 */
	public function get_min() {
		return $this->min;
	}

	/**
	 * Get max value.
	 *
	 * @return mixed
	 */
	public function get_max() {
		return $this->max;
	}

	/**
	 * Get step value.
	 *
	 * @return mixed
	 */
	public function get_step() {
		return $this->step;
	}
}
