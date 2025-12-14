<?php
/**
 * WPMoo Framework Loader.
 *
 * This file is responsible for negotiating which version of the WPMoo framework
 * to load when multiple plugins are using it. It ensures only the latest version
 * is loaded. This file should be immutable and have no namespace.
 *
 * @package WPMoo
 */

if (class_exists('WPMoo_Loader')) {
    return;
}

final class WPMoo_Loader {
    /**
     * Holds the registered instances of the framework.
     *
     * @var array<string, array{path: string, version: string}>
     */
    private static array $versions = [];

    /**
     * Flag to ensure the boot process is hooked only once.
     *
     * @var bool
     */
    private static bool $booted = false;

    /**
     * Registers a version of the framework.
     *
     * @param string $path    The full path to the main plugin file or boot file.
     * @param string $version The version of the framework being registered.
     */
    public static function register(string $path, string $version): void {
        self::$versions[$version] = ['path' => $path];

        if (!self::$booted) {
            // Hook into plugins_loaded at a very early priority to run the negotiator.
            add_action('plugins_loaded', [__CLASS__, 'negotiate_and_boot'], -100);
            self::$booted = true;
        }
    }

    /**
     * Finds the highest version and loads its bootstrap file.
     */
    public static function negotiate_and_boot(): void {
        if (empty(self::$versions)) {
            return;
        }

        // Find the highest version available.
        $versions = array_keys(self::$versions);
        usort($versions, 'version_compare');
        $winner_version = end($versions);
        $winner_path = self::$versions[$winner_version]['path'];

        if (file_exists($winner_path)) {
            require_once $winner_path;
        }
    }

    /**
     * Sets up the PSR-4 autoloader for WPMoo classes.
     * This method must be called by every plugin using the framework.
     *
     * @param string $framework_base_path The path to the 'framework' directory.
     */
    public static function load_autoloader(string $framework_base_path): void {
        if (file_exists($framework_base_path . '/vendor/autoload.php')) {
            require_once $framework_base_path . '/vendor/autoload.php';
        } else {
            // Fallback to a simple PSR-4 autoloader for distributed versions.
            spl_autoload_register(
                function ($class) use ($framework_base_path) {
                    $prefix = 'WPMoo\\';
                    $base_dir = $framework_base_path . '/';
                    $len = strlen($prefix);
                    if (strncmp($class, $prefix, $len) !== 0) {
                        return;
                    }
                    $relative_class = substr($class, $len);
                    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                    if (file_exists($file)) {
                        require $file;
                    }
                }
            );
        }
    }
}
