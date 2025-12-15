<?php

namespace WPMoo;

use ReflectionClass;
use WPMoo\Field\Field;

abstract class Facade {

    /**
     * Holds the automatically detected app IDs for each child facade.
     * @var array<string, string>
     */
    protected static array $app_ids = [];

    public static function __callStatic($method, $args) {
        $called_class = static::class;

        // If the ID for this specific facade has not been determined yet, detect and store it.
        if (!isset(static::$app_ids[$called_class])) {
            static::$app_ids[$called_class] = static::detect_app_id();
        }

        // Use the app_id specific to the child class that was called.
        $app_id = static::$app_ids[$called_class];

        return Core::get($app_id)->$method(...$args);
    }

    /**
     * Create an input field.
     *
     * @param string $id Field ID.
     * @return \WPMoo\Field\Type\Input
     */
    public static function input(string $id) {
        return Field::input($id);
    }

    /**
     * Create a textarea field.
     *
     * @param string $id Field ID.
     * @return \WPMoo\Field\Type\Textarea
     */
    public static function textarea(string $id) {
        return Field::textarea($id);
    }

    /**
     * Create a toggle field.
     *
     * @param string $id Field ID.
     * @return \WPMoo\Field\Type\Toggle
     */
    public static function toggle(string $id) {
        return Field::toggle($id);
    }


    /**
     * Magic Method: Extracts the slug from the file path of the inheriting class.
     */
    public static function detect_app_id(): string { // Changed method name
        // 1. Get the identity of the class calling this method (inheriting class) using Reflection.
        $reflector = new ReflectionClass(static::class);

        // 2. Get the full path of the file where the class is located.
        // E.g.: /var/www/html/wp-content/plugins/super-form/src/App.php
        $file_path = $reflector->getFileName(); // Changed to snake_case

        if (!$file_path) { // Changed to snake_case
            throw new \RuntimeException("Class file path not found.");
        }

        // 3. Make the path plugin-relative using a WordPress function.
        // Result: super-form/src/App.php
        $plugin_basename = plugin_basename($file_path); // Changed to snake_case

        // 4. Get the part before the first '/' (Folder name = Slug).
        // Result: super-form
        $parts = explode('/', $plugin_basename); // Changed to snake_case

        // If it's a single-file plugin (without a folder), get the file name.
        $slug = $parts[0];

        if (str_ends_with($slug, '.php')) {
            $slug = basename($slug, '.php');
        }

        return $slug;
    }
}
