<?php

namespace WPMoo\Field\Validators;

use WPMoo\Field\Interfaces\FieldValidatorInterface;

/**
 * Base validator implementation.
 *
 * @package WPMoo\Field\Validators
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
abstract class BaseValidator implements FieldValidatorInterface {
	/**
	 * Validate field value.
	 *
	 * @param mixed        $value The value to validate.
	 * @param array<mixed> $field_options Additional field options that might be needed for validation.
	 * @return array{valid:bool, error:string|null} Array containing validation result ['valid' => bool, 'error' => string|null].
	 */
	public function validate( mixed $value, array $field_options = array() ): array {
		return array(
			'valid' => true,
			'error' => null,
		);
	}
}
