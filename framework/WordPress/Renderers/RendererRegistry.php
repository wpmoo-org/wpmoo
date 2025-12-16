<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Registry for field renderers.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class RendererRegistry {
    /**
     * Registered renderers.
     *
     * @var array<string, FieldRendererInterface>
     */
    private array $renderers = [];
    
    /**
     * Constructor to register default renderers.
     */
    public function __construct() {
        $this->register_default_renderers();
    }
    
    /**
     * Register default renderers.
     */
    private function register_default_renderers(): void {
        $this->register_renderer('input', new InputRenderer());
        $this->register_renderer('textarea', new TextareaRenderer());
        $this->register_renderer('toggle', new ToggleRenderer());
        $this->register_renderer('select', new SelectRenderer());
    }
    
    /**
     * Register a renderer for a field type.
     *
     * @param string $field_type The field type.
     * @param FieldRendererInterface $renderer The renderer instance.
     * @return void
     */
    public function register_renderer(string $field_type, FieldRendererInterface $renderer): void {
        $this->renderers[$field_type] = $renderer;
    }
    
    /**
     * Get a renderer for a field type.
     *
     * @param string $field_type The field type.
     * @return FieldRendererInterface|null The renderer instance or null if not found.
     */
    public function get_renderer(string $field_type): ?FieldRendererInterface {
        return $this->renderers[$field_type] ?? null;
    }
    
    /**
     * Get renderer for a field instance.
     *
     * @param FieldInterface $field The field instance.
     * @return FieldRendererInterface|null The renderer instance or null if not found.
     */
    public function get_renderer_for_field(FieldInterface $field): ?FieldRendererInterface {
        $field_type = $this->get_field_type($field);
        return $this->get_renderer($field_type);
    }
    
    /**
     * Determine field type from field instance.
     *
     * @param FieldInterface $field The field instance.
     * @return string The field type.
     */
    private function get_field_type(FieldInterface $field): string {
        $field_class = get_class($field);
        $field_type = strtolower(pathinfo($field_class, PATHINFO_FILENAME));
        
        // Normalize field type names
        if (strpos($field_class, 'Input') !== false) {
            $field_type = 'input';
        } elseif (strpos($field_class, 'Textarea') !== false) {
            $field_type = 'textarea';
        } elseif (strpos($field_class, 'Toggle') !== false) {
            $field_type = 'toggle';
        } elseif (strpos($field_class, 'Select') !== false) {
            $field_type = 'select';
        }
        
        return $field_type;
    }
}