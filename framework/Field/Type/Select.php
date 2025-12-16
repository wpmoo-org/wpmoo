<?php

namespace WPMoo\Field\Type;

use WPMoo\Field\Abstracts\AbstractField;
use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Select field type.
 *
 * @package WPMoo\Field\Type
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class Select extends AbstractField implements FieldInterface {
    /**
     * Field options.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Set field options.
     *
     * @param array $options Field options.
     * @return self
     */
    public function options(array $options): self {
        $this->options = $options;
        return $this;
    }

    /**
     * Get field options.
     *
     * @return array
     */
    public function get_options(): array {
        return $this->options;
    }
    
}