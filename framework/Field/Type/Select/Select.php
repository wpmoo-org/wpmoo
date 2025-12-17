<?php

namespace WPMoo\Field\Type\Select;

use WPMoo\Field\Abstracts\AbstractField;
use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Select field type.
 *
 * @package WPMoo\Field\Type
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Select extends AbstractField implements FieldInterface {
	/**
	 * Multiple select.
	 *
	 * @var bool
	 */
	protected bool $is_multiple = false;

	/**
	 * Set multiple select.
	 *
	 * @param bool $is_multiple Is multiple.
	 * @return self
	 */
	public function multiple( bool $is_multiple = true ): self {
		$this->is_multiple = $is_multiple;
		return $this;
	}

	/**
	 * Get is multiple.
	 *
	 * @return bool
	 */
	public function get_multiple(): bool {
		return $this->is_multiple;
	}

	/**
	 * Validate the field value.
	 *
	 * @param mixed $value The value to validate.
	 * @return array{valid:bool, error:string|null} Array containing validation result.
	 */
	public function validate( mixed $value ): array {
		// 1. Run base validation (e.g. required).
		$base_result = parent::validate( $value );
		if ( ! $base_result['valid'] ) {
			return $base_result;
		}

		// 2. Validate against options.
		if ( empty( $value ) ) {
			return array( 'valid' => true, 'error' => null );
		}

		$options = $this->get_options();
		$valid_keys = array_keys( $options );

		if ( $this->is_multiple ) {
			if ( ! is_array( $value ) ) {
				return array( 'valid' => false, 'error' => 'Invalid format.' );
			}
			foreach ( $value as $val ) {
				if ( ! in_array( $val, $valid_keys ) ) {
					return array( 'valid' => false, 'error' => 'Invalid option selected.' );
				}
			}
		} else {
			if ( ! in_array( $value, $valid_keys ) ) {
				return array( 'valid' => false, 'error' => 'Invalid option selected.' );
			}
		}

		return array( 'valid' => true, 'error' => null );
	}
}
