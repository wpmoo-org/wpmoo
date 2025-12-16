<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Interface for field renderers.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
interface FieldRendererInterface {
    /**
     * Render a field.
     *
     * @param FieldInterface $field The field to render.
     * @param string $unique_slug The unique slug for the page.
     * @param mixed $value The current value of the field.
     * @return string The rendered HTML.
     */
    public function render(FieldInterface $field, string $unique_slug, $value): string;
}
