<?php

namespace WPMoo\Layout\Component;

use WPMoo\Layout\Abstracts\AbstractLayout;
use WPMoo\Layout\Interfaces\LayoutInterface;

/**
 * Fieldset layout component.
 *
 * @package WPMoo\Layout\Component
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Fieldset extends AbstractLayout implements LayoutInterface {
    /**
     * Fieldset legend/title.
     *
     * @var string
     */
    private string $title;
    
    /**
     * Content fields for this fieldset.
     *
     * @var array
     */
    private array $content = [];
    
    /**
     * Constructor.
     *
     * @param string $id Layout ID.
     * @param string $title Fieldset title/legend.
     */
    public function __construct(string $id, string $title) {
        $this->id = $id;
        $this->title = $title;
    }
    
    /**
     * Set parent ID.
     *
     * @param string $parent Parent ID to link to.
     * @return self
     */
    public function parent(string $parent): self {
        $this->parent = $parent;
        return $this;
    }
    
    /**
     * Set content fields for the fieldset.
     *
     * @param array $content Array of field components.
     * @return self
     */
    public function content(array $content): self {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Set content fields for the fieldset (alternative method name for API consistency).
     *
     * @param array $fields Array of field components.
     * @return self
     */
    public function fields(array $fields): self {
        return $this->content($fields);
    }
    
    /**
     * Get content fields.
     *
     * @return array
     */
    public function get_content(): array {
        return $this->content;
    }
    
    /**
     * Get fieldset title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }
    
    /**
     * Get layout ID.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }
    
    /**
     * Get parent ID.
     *
     * @return string
     */
    public function get_parent(): string {
        return $this->parent;
    }
}