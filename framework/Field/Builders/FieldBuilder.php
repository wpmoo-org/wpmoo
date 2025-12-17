<?php

namespace WPMoo\Field\Builders;

use WPMoo\Field\Type\Input\Input;
use WPMoo\Field\Type\Textarea\Textarea;
use WPMoo\Field\Type\Toggle\Toggle;

/**
 * Fluent field builder.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class FieldBuilder {
	/**
	 * Create an input field builder.
	 *
	 * @param string $id Field ID.
	 * @return Input
	 */
	public static function input( string $id ): Input {
		return new Input( $id );
	}

	/**
	 * Create a textarea field builder.
	 *
	 * @param string $id Field ID.
	 * @return Textarea
	 */
	public static function textarea( string $id ): Textarea {
		return new Textarea( $id );
	}

	/**
	 * Create a toggle field builder.
	 *
	 * @param string $id Field ID.
	 * @return Toggle
	 */
	public static function toggle( string $id ): Toggle {
		return new Toggle( $id );
	}
}
