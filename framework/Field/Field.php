<?php

namespace WPMoo\Field;

use WPMoo\Field\Builders\FieldBuilder;
use WPMoo\Field\Interfaces\FieldSanitizerInterface;
use WPMoo\Field\Sanitizers\TextSanitizer;
use WPMoo\Field\Sanitizers\TextareaSanitizer;
use WPMoo\Field\Sanitizers\ToggleSanitizer;

/**
 * Field builder (alias for Builder).
 *
 * @package WPMoo\Field
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
final class Field extends FieldBuilder {
	/**
	 * Get sanitizer for a specific field type.
	 *
	 * @param string $type Field type.
	 * @return FieldSanitizerInterface The sanitizer instance.
	 */
	public static function get_sanitizer( string $type ): FieldSanitizerInterface {
		switch ( $type ) {
			case 'input':
				return new TextSanitizer();
			case 'textarea':
				return new TextareaSanitizer();
			case 'toggle':
				return new ToggleSanitizer();
			default:
				return new TextSanitizer(); // Default to text sanitizer.
		}
	}
}
