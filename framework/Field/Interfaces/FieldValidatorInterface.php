<?php

namespace WPMoo\Field\Interfaces;

/**
 * Validation contract.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
interface FieldValidatorInterface {
	/**
	 * Validate field value.
	 *
	 * @param mixed        $value The value to validate.
	 * @param array<mixed> $field_options Additional field options that might be needed for validation.
	 * @return array{valid:bool, error:string|null} Array containing validation result ['valid' => bool, 'error' => string|null].
	 */
	public function validate( $value, array $field_options = array() ): array;
}
