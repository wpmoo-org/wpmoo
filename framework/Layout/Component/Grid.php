<?php

namespace WPMoo\Layout\Component;

use WPMoo\Layout\Abstracts\AbstractLayout;
use WPMoo\Layout\Interfaces\LayoutInterface;

/**
 * Grid layout component.
 *
 * @package WPMoo\Layout\Component
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Grid extends AbstractLayout implements LayoutInterface {
    /**
     * Number of columns in the grid.
     *
     * @var int
     */
    protected int $columns = 1;
    
    /**
     * Constructor.
     *
     * @param string $id The grid ID.
     * @param string $title The grid title.
     */
    public function __construct(string $id, string $title = '') {
        $this->id = $id;
        // We don't store title in this implementation since AbstractLayout doesn't have a title property
        // If needed, we could add a title property to AbstractLayout or create a getter method
    }
    
    /**
     * Set the number of columns for the grid.
     *
     * @param int $columns Number of columns (at least 1).
     * @return self
     */
    public function columns(int $columns): self {
        $this->columns = max(1, $columns); // Ensure at least 1 column
        return $this;
    }

    /**
     * Get the number of columns.
     *
     * @return int
     */
    public function get_columns(): int {
        return $this->columns;
    }
}