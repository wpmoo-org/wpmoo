<?php
/**
 * The Local Facade for the 'wpmoo' plugin.
 *
 * @package WPMoo
 */

namespace WPMoo;

if ( ! class_exists( 'WPMoo\Moo' ) ) {
	/**
	 * The Local Facade for the 'wpmoo' plugin.
	 *
	 * @method static \WPMoo\Page\Builders\PageBuilder page(string $id, string $title)
	 * @method static \WPMoo\Layout\Component\Tabs tabs(string $id)
	 * @method static \WPMoo\Field\Interfaces\FieldInterface field(string $type, string $id)
	 * @method static \WPMoo\Layout\Component\Container container(string $type, string $id)
	 * @method static \WPMoo\Layout\Component\Tab tab(string $id, string $title)
	 * @method static \WPMoo\Layout\Component\Accordion accordion(string $id, string $title)
	 * @method static mixed create_field(string $type, string $id)
	 * @method static mixed create_layout(string $type, string $id, string $title = '')
	 * @method static void register_field_type(string $type, string $class)
	 * @method static void register_layout_type(string $type, string $class)
	 */
	class Moo extends Facade {
		// Extend the new Facade class.
		// APP_ID and __callStatic are now handled by the parent Facade class.
	}
}
