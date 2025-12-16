<?php

namespace WPMoo\Field\Validators;

use WPMoo\Field\Interfaces\FieldValidatorInterface;

/**
 * Number field validator.
 *
 * @package WPMoo\Field\Validators
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class NumberValidator extends BaseValidator implements FieldValidatorInterface {
	/**
	 * Validate number field value.
	 *
	 * @param mixed $value The value to validate.
	 * @param array $field_options Additional field options that might be needed for validation.
	 * @return array Array containing validation result ['valid' => bool, 'error' => string|null].
	 */
	public function validate( mixed $value, array $field_options = array() ): array {
		if ( ! is_numeric( $value ) && ( ! is_string( $value ) || '' === $value ) ) {
			return array(
				'valid' => true,
				'error' => null,
			); // Allow empty values to be handled by required validator.
		}

		if ( ! is_numeric( $value ) ) {
			return array(
				'valid' => false,
				'error' => __( 'Please enter a valid number.', 'wpmoo' ),
			);
		}

		// Check min/max constraints if provided.
		$min = $field_options['min'] ?? null;
		$max = $field_options['max'] ?? null;

		$num_value = floatval( $value );

		if ( isset( $min ) && $num_value < floatval( $min ) ) {
			return array(
				'valid' => false,
				'error' => sprintf(
					/* translators: %s is the minimum allowed value */
					__( 'Value must be greater than or equal to %s.', 'wpmoo' ),
					$min
				),
			);
		}

		if ( isset( $max ) && $num_value > floatval( $max ) ) {
			return array(
				'valid' => false,
				'error' => sprintf(
					/* translators: %s is the maximum allowed value */
					__( 'Value must be less than or equal to %s.', 'wpmoo' ),
					$max
				),
			);
		}

		return array(
			'valid' => true,
			'error' => null,
		);
	}
}
