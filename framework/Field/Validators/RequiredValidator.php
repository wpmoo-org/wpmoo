<?php

namespace WPMoo\Field\Validators;

use WPMoo\Field\Interfaces\FieldValidatorInterface;

/**
 * Required field validator.
 *
 * @package WPMoo\Field\Validators
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class RequiredValidator extends BaseValidator implements FieldValidatorInterface {
    /**
     * Validate required field value.
     *
     * @param mixed $value The value to validate.
     * @param array $field_options Additional field options that might be needed for validation.
     * @return array Array containing validation result ['valid' => bool, 'error' => string|null].
     */
    public function validate(mixed $value, array $field_options = []): array {
        $required = $field_options['required'] ?? false;
        
        if ($required) {
            if (is_string($value) && trim($value) === '') {
                return ['valid' => false, 'error' => __('This field is required.', 'wpmoo')];
            } elseif ($value === null || $value === []) {
                return ['valid' => false, 'error' => 'This field is required.'];
            }
        }
        
        return ['valid' => true, 'error' => null];
    }
}