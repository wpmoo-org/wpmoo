<?php

namespace WPMoo\Field\Interfaces;

/**
 * Contract for all field types.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
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

	/**
	 * Get field ID.
	 *
	 * @return string Field ID.
	 */
	public function get_id(): string;
	
	/**
	 * Set field options.
	 *
	 * @param array $options Field options.
	 * @return self
	 */
	public function options(array $options);
	
	/**
	 * Get field options.
	 *
	 * @return array
	 */
	public function get_options(): array;
}
