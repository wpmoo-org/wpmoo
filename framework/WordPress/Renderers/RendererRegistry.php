<?php

namespace WPMoo\WordPress\Renderers;

use InvalidArgumentException;
use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Renderer registry for managing field renderers.
 *
 * This registry allows for registering and retrieving field renderers dynamically.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class RendererRegistry
{
    /**
     * Registered field renderers.
     *
     * @var array<string, FieldRendererInterface>
     */
    private array $renderers = [];

    /**
     * Default field renderers provided by the framework.
     *
     * @var array<string, class-string<FieldRendererInterface>>
     */
    private array $default_renderers = [
        'input'    => \WPMoo\Field\Type\Input\WordPress\InputRenderer::class,
        'textarea' => \WPMoo\Field\Type\Textarea\WordPress\TextareaRenderer::class,
        'toggle'   => \WPMoo\Field\Type\Toggle\WordPress\ToggleRenderer::class,
        'select'   => \WPMoo\Field\Type\Select\WordPress\SelectRenderer::class,
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->register_default_renderers();
    }

    /**
     * Register default field renderers provided by the framework.
     */
    private function register_default_renderers(): void
    {
        foreach ($this->default_renderers as $type => $class) {
            $this->register_renderer($type, new $class());
        }
    }

    /**
     * Register a new field renderer.
     *
     * @param string                 $field_type The field type slug (e.g., 'input', 'textarea').
     * @param FieldRendererInterface $renderer   The renderer instance.
     * @return void
     * @throws InvalidArgumentException If the renderer does not implement FieldRendererInterface.
     */
    public function register_renderer(string $field_type, FieldRendererInterface $renderer): void
    {
        if (!($renderer instanceof FieldRendererInterface)) {
            throw new InvalidArgumentException(sprintf(
                /* translators: %s: Renderer class name. */
                esc_html__('Renderer class must implement FieldRendererInterface: %s', 'wpmoo'),
                get_class($renderer)
            ));
        }

        $this->renderers[$field_type] = $renderer;
    }

    /**
     * Get a field renderer by type.
     *
     * @param string $field_type The field type slug.
     * @return FieldRendererInterface|null The renderer instance or null if not found.
     */
    public function get_renderer(string $field_type): ?FieldRendererInterface
    {
        return $this->renderers[$field_type] ?? null;
    }

    /**
     * Get a renderer for a given field instance.
     *
     * @param FieldInterface $field The field instance.
     * @return FieldRendererInterface|null The renderer instance or null if not found.
     */
    public function get_renderer_for_field(FieldInterface $field): ?FieldRendererInterface
    {
        return $this->get_renderer($field->get_type());
    }
}
