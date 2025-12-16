<?php

namespace WPMoo\WordPress\Compatibility;

/**
 * Version compatibility checker for WPMoo framework.
 *
 * @package WPMoo\WordPress\Compatibility
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class VersionCompatibilityChecker {
    
    /**
     * Check if a plugin's required framework version is compatible with the current framework version.
     *
     * @param string $required_version The version required by the plugin (e.g., '1.0.0', '^1.0', '~1.0.0').
     * @param string $current_version The current framework version.
     * @return array ['compatible' => bool, 'message' => string]
     */
    public static function isCompatible(string $required_version, string $current_version): array {
        // If the required version is a simple version string (e.g., '1.0.0')
        if (!preg_match('/[~^*]/', $required_version)) {
            $compatible = version_compare($current_version, $required_version, '>=');
            $message = $compatible 
                ? "Current framework version ({$current_version}) meets required version ({$required_version})"
                : "Current framework version ({$current_version}) does not meet required version ({$required_version})";
            
            return [
                'compatible' => $compatible,
                'message' => $message
            ];
        }
        
        // Handle Composer-style version constraints
        $result = self::checkVersionConstraint($required_version, $current_version);
        
        return [
            'compatible' => $result['compatible'],
            'message' => $result['message']
        ];
    }
    
    /**
     * Check version against Composer-style constraints.
     *
     * @param string $constraint The version constraint (e.g., '^1.0', '~1.0.0', '>=1.0.0').
     * @param string $version The version to check.
     * @return array ['compatible' => bool, 'message' => string]
     */
    private static function checkVersionConstraint(string $constraint, string $version): array {
        // Remove any whitespace
        $constraint = trim($constraint);
        
        // Handle different constraint types
        if (str_starts_with($constraint, '^')) {
            // Caret operator: allows patch-level changes if a minor version is specified
            // ^1.2.3 is equivalent to >=1.2.3 <2.0.0
            $required = substr($constraint, 1);
            return self::checkCaretConstraint($required, $version);
        } elseif (str_starts_with($constraint, '~')) {
            // Tilde operator: allows patch-level changes
            // ~1.2.3 is equivalent to >=1.2.3 <1.3.0
            $required = substr($constraint, 1);
            return self::checkTildeConstraint($required, $version);
        } elseif (preg_match('/^([<>=!]+)\s*(.+)$/', $constraint, $matches)) {
            // Direct comparison operators (>=, <=, >, <, !=)
            $operator = $matches[1];
            $required = $matches[2];
            return self::checkDirectComparison($operator, $required, $version);
        } else {
            // Fallback to simple comparison
            $compatible = version_compare($version, $constraint, '>=');
            return [
                'compatible' => $compatible,
                'message' => $compatible 
                    ? "Version {$version} satisfies constraint {$constraint}"
                    : "Version {$version} does not satisfy constraint {$constraint}"
            ];
        }
    }
    
    /**
     * Check caret constraint (^ operator).
     *
     * @param string $required The required version after caret.
     * @param string $version The version to check.
     * @return array ['compatible' => bool, 'message' => string]
     */
    private static function checkCaretConstraint(string $required, string $version): array {
        $required_parts = explode('.', $required);
        $version_parts = explode('.', $version);
        
        // Pad arrays to same length with zeros
        while (count($required_parts) < 3) $required_parts[] = '0';
        while (count($version_parts) < 3) $version_parts[] = '0';
        
        // Parse version numbers
        $required_major = intval($required_parts[0]);
        $required_minor = intval($required_parts[1] ?? 0);
        $required_patch = intval($required_parts[2] ?? 0);
        
        $version_major = intval($version_parts[0]);
        $version_minor = intval($version_parts[1]);
        $version_patch = intval($version_parts[2]);
        
        // Check if version is greater than or equal to required
        $gte = version_compare($version, $required, '>=');
        
        // Check if version is less than next major version
        $next_major = ($required_major + 1) . '.0.0';
        $lt_next_major = version_compare($version, $next_major, '<');
        
        $compatible = $gte && $lt_next_major;
        
        return [
            'compatible' => $compatible,
            'message' => $compatible
                ? "Version {$version} satisfies caret constraint ^{$required} (>= {$required} and < {$next_major})"
                : "Version {$version} does not satisfy caret constraint ^{$required} (>= {$required} and < {$next_major})"
        ];
    }
    
    /**
     * Check tilde constraint (~ operator).
     *
     * @param string $required The required version after tilde.
     * @param string $version The version to check.
     * @return array ['compatible' => bool, 'message' => string]
     */
    private static function checkTildeConstraint(string $required, string $version): array {
        $required_parts = explode('.', $required);
        $version_parts = explode('.', $version);
        
        // Pad arrays to same length with zeros
        while (count($required_parts) < 3) $required_parts[] = '0';
        while (count($version_parts) < 3) $version_parts[] = '0';
        
        // Parse version numbers
        $required_major = intval($required_parts[0]);
        $required_minor = intval($required_parts[1] ?? 0);
        $required_patch = intval($required_parts[2] ?? 0);
        
        $version_major = intval($version_parts[0]);
        $version_minor = intval($version_parts[1]);
        $version_patch = intval($version_parts[2]);
        
        // Check if version is greater than or equal to required
        $gte = version_compare($version, $required, '>=');
        
        // Check if version is less than next minor version
        $next_minor = $required_major . '.' . ($required_minor + 1) . '.0';
        $lt_next_minor = version_compare($version, $next_minor, '<');
        
        $compatible = $gte && $lt_next_minor;
        
        return [
            'compatible' => $compatible,
            'message' => $compatible
                ? "Version {$version} satisfies tilde constraint ~{$required} (>= {$required} and < {$next_minor})"
                : "Version {$version} does not satisfy tilde constraint ~{$required} (>= {$required} and < {$next_minor})"
        ];
    }
    
    /**
     * Check direct comparison operators.
     *
     * @param string $operator The comparison operator.
     * @param string $required The required version.
     * @param string $version The version to check.
     * @return array ['compatible' => bool, 'message' => string]
     */
    private static function checkDirectComparison(string $operator, string $required, string $version): array {
        $compatible = version_compare($version, $required, $operator);
        
        return [
            'compatible' => $compatible,
            'message' => $compatible
                ? "Version {$version} satisfies constraint {$operator}{$required}"
                : "Version {$version} does not satisfy constraint {$operator}{$required}"
        ];
    }
}