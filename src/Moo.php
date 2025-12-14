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
    class Moo extends Facade { // Extend the new Facade class
        // APP_ID and __callStatic are now handled by the parent Facade class
    }
}
