<?php

namespace WPMoo\Field\Interfaces;

/**
 * Contract for all field types.
 *
 * @package WPMoo
 * @since 0.1.0
 */
interface FieldInterface {
	/**
	 * Set field name.
	 *
	 * @param string $name Field name.
	 * @return self
	 */
	public function name( string $name );

	/**
	 * Set field label.
	 *
	 * @param string $label Field label.
	 * @return self
	 */
	public function label( string $label );

	/**
	 * Set field placeholder.
	 *
	 * @param string $placeholder Field placeholder.
	 * @return self
	 */
	public function placeholder( string $placeholder );
}
