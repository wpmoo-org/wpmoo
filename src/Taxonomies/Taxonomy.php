<?php

namespace WPMoo\Taxonomies;

use WPMoo\Columns\Column;
use WPMoo\Taxonomies\Builder;

/**
 * Taxonomy
 *
 * Create WordPress Taxonomies
 */
class Taxonomy
{
    /**
     * The names passed to the Taxonomy
     * @var mixed
     */
    public $names;

    /**
     * The Taxonomy name
     * @var string
     */
    public $name;

    /**
     * The singular label for the Taxonomy
     * @var string
     */
    public $singular;

    /**
     * The plural label for the Taxonomy
     * @var string
     */
    public $plural;

    /**
     * The Taxonomy slug
     * @var string
     */
    public $slug;

    /**
     * Custom options for the Taxonomy
     * @var array
     */
    public $options;

    /**
     * Custom labels for the Taxonomy
     * @var array
     */
    public $labels;

    /**
     * PostTypes to register the Taxonomy to
     * @var array
     */
    public $posttypes = [];

    /**
     * The column manager for the Taxonomy
     * @var mixed
     */
    public $columns;

    /**
     * Create a Taxonomy
     * @param mixed $names The name(s) for the Taxonomy
     */
    public function __construct($names, $options = [], $labels = [])
    {
        $this->names($names);

        $this->options($options);

        $this->labels($labels);
    }

    /**
     * Set the names for the Taxonomy
     * @param  mixed $names The name(s) for the Taxonomy
     * @return $this
     */
    public function names($names)
    {
        if (is_string($names)) {
            $names = ['name' => $names];
        }

        $this->names = $names;

        // create names for the Taxonomy
        $this->createNames();

        return $this;
    }

    /**
     * Set options for the Taxonomy
     * @param  array $options
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the Taxonomy labels
     * @param  array  $labels
     * @return $this
     */
    public function labels(array $labels = [])
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Assign a PostType to register the Taxonomy to
     * @param  mixed $posttypes
     * @return $this
     */
    public function posttype($posttypes)
    {
        $posttypes = is_string($posttypes) ? [$posttypes] : $posttypes;

        foreach ($posttypes as $posttype) {
            $this->posttypes[] = $posttype;
        }

        return $this;
    }

    /**
     * Get the Column Manager for the Taxonomy
     * @return Columns
     */
    public function columns()
    {
        if (!isset($this->columns)) {
            $this->columns = new Column;
        }

        return $this->columns;
    }

    /**
     * Register the Taxonomy to WordPress
     * @return void
     */
    public function register()
    {
        (new Builder($this))->register();
    }

    /**
     * Create names for the Taxonomy
     * @return void
     */
    public function createNames()
    {
        $required = [
            'name',
            'singular',
            'plural',
            'slug',
        ];

        foreach ($required as $key) {
            // if the name is set, assign it
            if (isset($this->names[$key])) {
                $this->$key = $this->names[$key];
                continue;
            }

            // if the key is not set and is singular or plural
            if (in_array($key, ['singular', 'plural'])) {
                // create a human friendly name
                $name = ucwords(strtolower(str_replace(['-', '_'], ' ', $this->names['name'])));
            }

            if ($key === 'slug') {
                // create a slug friendly name
                $name = strtolower(str_replace([' ', '_'], '-', $this->names['name']));
            }

            // if is plural or slug, append an 's'
            if (in_array($key, ['plural', 'slug'])) {
                $name .= 's';
            }

            // asign the name to the PostType property
            $this->$key = $name;
        }
    }

    /**
     * Create options for Taxonomy
     * @return array Options to pass to register_taxonomy
     */
    public function createOptions()
    {
        // default options
        $options = [
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => [
                'slug' => $this->slug,
            ],
        ];

        // replace defaults with the options passed
        $options = array_replace_recursive($options, $this->options);

        // create and set labels
        if (!isset($options['labels'])) {
            $options['labels'] = $this->createLabels();
        }

        return $options;
    }

    /**
     * Create labels for the Taxonomy
     * @return array
     */
    public function createLabels()
    {
        // default labels
        $labels = [
            'name'                       => $this->plural,
            'singular_name'              => $this->singular,
            'menu_name'                  => $this->plural,
            'all_items'                  => function_exists('__') ? sprintf(__('All %s', 'wpmoo'), $this->plural) : "All {$this->plural}",
            'edit_item'                  => function_exists('__') ? sprintf(__('Edit %s', 'wpmoo'), $this->singular) : "Edit {$this->singular}",
            'view_item'                  => function_exists('__') ? sprintf(__('View %s', 'wpmoo'), $this->singular) : "View {$this->singular}",
            'update_item'                => function_exists('__') ? sprintf(__('Update %s', 'wpmoo'), $this->singular) : "Update {$this->singular}",
            'add_new_item'               => function_exists('__') ? sprintf(__('Add New %s', 'wpmoo'), $this->singular) : "Add New {$this->singular}",
            'new_item_name'              => function_exists('__') ? sprintf(__('New %s Name', 'wpmoo'), $this->singular) : "New {$this->singular} Name",
            'parent_item'                => function_exists('__') ? sprintf(__('Parent %s', 'wpmoo'), $this->plural) : "Parent {$this->plural}",
            'parent_item_colon'          => function_exists('__') ? sprintf(__('Parent %s:', 'wpmoo'), $this->plural) : "Parent {$this->plural}:",
            'search_items'               => function_exists('__') ? sprintf(__('Search %s', 'wpmoo'), $this->plural) : "Search {$this->plural}",
            'popular_items'              => function_exists('__') ? sprintf(__('Popular %s', 'wpmoo'), $this->plural) : "Popular {$this->plural}",
            'separate_items_with_commas' => function_exists('__') ? sprintf(__('Separate %s with commas', 'wpmoo'), $this->plural) : "Seperate {$this->plural} with commas",
            'add_or_remove_items'        => function_exists('__') ? sprintf(__('Add or remove %s', 'wpmoo'), $this->plural) : "Add or remove {$this->plural}",
            'choose_from_most_used'      => function_exists('__') ? sprintf(__('Choose from most used %s', 'wpmoo'), $this->plural) : "Choose from most used {$this->plural}",
            'not_found'                  => function_exists('__') ? sprintf(__('No %s found', 'wpmoo'), $this->plural) : "No {$this->plural} found",
        ];

        return array_replace($labels, $this->labels);
    }
}
