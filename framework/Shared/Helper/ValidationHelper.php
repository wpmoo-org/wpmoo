<?php

namespace WPMoo\Shared\Helper;

/**
 * Validation helper for WPMoo framework.
 *
 * Provides validation functions for various component types to ensure
 * proper format and security.
 *
 * @package WPMoo\Shared\Helper
 * @since 0.2.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class ValidationHelper {
    
    /**
     * Validates a component ID format.
     *
     * @param string $id The ID to validate.
     * @param string $component_type The type of component (for error messages).
     * @return bool True if valid, throws exception if invalid.
     * @throws \InvalidArgumentException If the ID format is invalid.
     */
    public static function validate_id_format(string $id, string $component_type = 'component'): bool {
        if (empty($id)) {
            throw new \InvalidArgumentException("{$component_type} ID cannot be empty.");
        }
        
        if (!preg_match('/^[a-z0-9_-]+$/', $id)) {
            throw new \InvalidArgumentException(
                "Invalid {$component_type} ID: {$id}. Must contain only lowercase letters, numbers, hyphens, and underscores."
            );
        }
        
        return true;
    }
    
    /**
     * Validates a plugin slug format.
     *
     * @param string $slug The slug to validate.
     * @return bool True if valid, throws exception if invalid.
     * @throws \InvalidArgumentException If the slug format is invalid.
     */
    public static function validate_plugin_slug(string $slug): bool {
        if (empty($slug)) {
            throw new \InvalidArgumentException("Plugin slug cannot be empty.");
        }
        
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new \InvalidArgumentException(
                "Invalid plugin slug: {$slug}. Must contain only lowercase letters, numbers, and hyphens."
            );
        }
        
        return true;
    }
    
    /**
     * Validates a version string format.
     *
     * @param string $version The version to validate.
     * @return bool True if valid, throws exception if invalid.
     * @throws \InvalidArgumentException If the version format is invalid.
     */
    public static function validate_version_format(string $version): bool {
        if (empty($version)) {
            throw new \InvalidArgumentException("Version cannot be empty.");
        }
        
        // Basic semantic versioning format: X.Y or X.Y.Z, optionally with pre-release or build metadata
        if (!preg_match('/^[\d]+\.[\d]+(?:\.[\d]+)?(?:-[a-zA-Z0-9.]+)?(?:\+[a-zA-Z0-9.]+)?$/', $version)) {
            throw new \InvalidArgumentException(
                "Invalid version format: {$version}. Must follow semantic versioning (e.g., 1.0.0)."
            );
        }
        
        return true;
    }
    
    /**
     * Validates a file path exists and is readable.
     *
     * @param string $path The path to validate.
     * @return bool True if valid, throws exception if invalid.
     * @throws \InvalidArgumentException If the path is invalid.
     */
    public static function validate_file_path(string $path): bool {
        if (empty($path)) {
            throw new \InvalidArgumentException("File path cannot be empty.");
        }
        
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(
                "File does not exist: {$path}"
            );
        }
        
        if (!is_readable($path)) {
            throw new \InvalidArgumentException(
                "File is not readable: {$path}"
            );
        }
        
        return true;
    }
}