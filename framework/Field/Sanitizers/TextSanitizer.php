<?php

namespace WPMoo\Field\Sanitizers;

use WPMoo\Field\Interfaces\FieldSanitizerInterface;

/**
 * Text field sanitizer.
 *
 * @package WPMoo\Field\Sanitizers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class TextSanitizer extends BaseSanitizer implements FieldSanitizerInterface {
    /**
     * Sanitize text field value.
     *
     * @param mixed $value The value to sanitize.
     * @return string The sanitized value.
     */
    public function sanitize(mixed $value): string {
        return sanitize_text_field($value);
    }
}