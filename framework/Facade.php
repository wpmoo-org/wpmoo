<?php

namespace WPMoo;

use ReflectionClass;

abstract class Facade {

    /**
     * Holds the automatically detected or manually set ID.
     */
    protected static ?string $app_id = null;

    public static function __callStatic($method, $args) {
        // If the ID has not been determined yet, try to find it automatically.
        if (static::$app_id === null) {
            static::$app_id = static::detect_app_id(); // Changed call site
        }

        return Core::get(static::$app_id)->$method(...$args);
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
