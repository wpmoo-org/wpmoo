<?php
/**
 * The Local Facade for the 'wpmoo' plugin.
 *
 * @package WPMoo
 */

namespace WPMoo;

if (!class_exists('WPMoo\Moo')) {
    /**
     * @method static \WPMoo\Page\Page page(string $id, string $title)
     * @method static \WPMoo\Layout\Component\Tabs tabs(string $id)
     * @method static \WPMoo\Field\Interfaces\FieldInterface field(string $type, string $id)
     */
    class Moo {
        private const APP_ID = 'wpmoo';

        public static function __callStatic($method, $args) {
            return Core::get(self::APP_ID)->$method(...$args);
        }
    }
}
