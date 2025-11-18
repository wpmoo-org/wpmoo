<?php

namespace WPMoo\Field\Abstracts;

use WPMoo\Field\Contracts\FieldInterface;

/**
 * Base field implementation.
 *
 * @package WPMoo
 * @since 0.1.0
 */
abstract class AbstractField implements FieldInterface {
	/**
	 * Field ID.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Field name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Field label.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Field placeholder.
	 *
	 * @var string
	 */
	protected string $placeholder = '';

	/**
	 * Constructor.
	 *
	 * @param string $id Field ID.
	 */
	public function __construct( string $id ) {
		$this->id = $id;
		$this->name = $id; // Default name to ID
	}

	/**
	 * Set field name.
	 *
	 * @param string $name Field name.
	 * @return self
	 */
	public function name( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set field label.
	 *
	 * @param string $label Field label.
	 * @return self
	 */
	public function label( string $label ): self {
		$this->label = $label;
		return $this;
	}

	/**
	 * Set field placeholder.
	 *
	 * @param string $placeholder Field placeholder.
	 * @return self
	 */
	public function placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * Get field ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get field name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get field placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}
}
