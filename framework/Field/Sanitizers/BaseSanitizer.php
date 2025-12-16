<?php

namespace WPMoo\Field\Sanitizers;

use WPMoo\Field\Interfaces\FieldSanitizerInterface;

/**
 * Base sanitizer implementation.
 *
 * @package WPMoo\Field\Sanitizers
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
abstract class BaseSanitizer implements FieldSanitizerInterface {
    /**
     * Sanitize field value.
     *
     * @param mixed $value The value to sanitize.
     * @return mixed The sanitized value.
     */
    public function sanitize(mixed $value): mixed {
        return $value;
    }
}