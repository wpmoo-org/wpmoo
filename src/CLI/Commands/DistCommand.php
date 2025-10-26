<?php

namespace WPMoo\CLI\Commands;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Support\Base;
use WPMoo\CLI\Console;

class DistCommand extends Base implements CommandInterface {
    public function handle(array $args = array()) {
        $options = self::parse_dist_options($args);

        $source_root = $options['source']
            ? self::normalize_absolute_path($options['source'])
            : self::default_dist_source();

        if (!$source_root || !is_dir($source_root)) {
            Console::error('Unable to resolve source directory for distribution.');
            return 1;
        }

        $base_path    = self::framework_base_path();
        $is_framework = self::paths_equal($source_root, rtrim($base_path, '/\\'));

        $metadata = $is_framework ? array() : self::detect_project_metadata($source_root);

        $version = $options['version']
            ? self::sanitize_version_input($options['version'])
            : ($is_framework
                ? self::detect_current_version($base_path)
                : ($metadata['version'] ?? self::detect_current_version($source_root)));

        if (!$version) {
            $version = '0.0.0';
        }

        if ($options['label']) {
            $slug = self::sanitize_slug($options['label']);
        } elseif ($is_framework) {
            $slug = self::plugin_slug();
        } elseif (!empty($metadata['slug'])) {
            $slug = $metadata['slug'];
        } else {
            $slug = self::sanitize_slug(basename($source_root));
        }

        if ('' === $slug) {
            $slug = 'package';
        }

        $label = $slug . '-' . $version;

        $dist_root = $options['output']
            ? self::normalize_absolute_path($options['output'])
            : dirname($source_root) . DIRECTORY_SEPARATOR . 'dist';

        if (!$dist_root) {
            Console::error('Failed to resolve distribution output directory.');
            return 1;
        }

        if (!self::ensure_directory(rtrim($dist_root, '/\\') . DIRECTORY_SEPARATOR)) {
            Console::error('Unable to create distribution output directory.');
            return 1;
        }

        $temp_dir = self::create_temp_directory($slug . '-dist-');

        if (!$temp_dir) {
            Console::error('Unable to create temporary directory for distribution build.');
            return 1;
        }

        $target_root = $temp_dir . DIRECTORY_SEPARATOR . $label;

        if (!@mkdir($target_root, 0755, true)) {
            Console::error('Unable to prepare working directory for distribution.');
            self::delete_directory($temp_dir);
            return 1;
        }

        Console::line();
        Console::comment('Preparing distribution: ' . $label);

        if ($is_framework) {
            foreach (self::default_dist_includes($source_root) as $entry) {
                $source = $source_root . DIRECTORY_SEPARATOR . $entry;
                $target = $target_root . DIRECTORY_SEPARATOR . $entry;

                if (self::copy_within_dist($source, $target) && 'vendor' === $entry) {
                    self::prune_vendor_tree($target);
                }

                if (!file_exists($target)) {
                    Console::warning('Failed to include ' . $entry . ' in distribution.');
                }
            }

            self::ensure_minified_assets($target_root . DIRECTORY_SEPARATOR . 'assets');
            self::prune_assets_tree($target_root . DIRECTORY_SEPARATOR . 'assets');

            $composer_binary = self::locate_composer_binary($target_root);
            if ($composer_binary) {
                Console::comment('→ Installing production dependencies (--no-dev)');
                self::delete_directory($target_root . DIRECTORY_SEPARATOR . 'vendor');
                list($status, $output) = self::execute_command(
                    $composer_binary,
                    array('install','--no-dev','--prefer-dist','--no-interaction','--no-progress','--optimize-autoloader'),
                    $target_root
                );
                self::output_command_lines($output);
                if (0 !== $status) {
                    Console::warning('Composer install failed (exit code ' . $status . '). Reinstating bundled vendor directory.');
                    self::copy_within_dist(
                        $source_root . DIRECTORY_SEPARATOR . 'vendor',
                        $target_root . DIRECTORY_SEPARATOR . 'vendor'
                    );
                }
            } else {
                Console::comment('→ Composer binary not found; reusing existing vendor directory.');
            }

            self::remove_if_exists($target_root . DIRECTORY_SEPARATOR . 'composer.json');
            self::remove_if_exists($target_root . DIRECTORY_SEPARATOR . 'composer.lock');
            self::remove_if_exists($target_root . DIRECTORY_SEPARATOR . 'package.json');
            self::remove_if_exists($target_root . DIRECTORY_SEPARATOR . 'package-lock.json');
            self::remove_if_exists($target_root . DIRECTORY_SEPARATOR . 'pnpm-lock.yaml');
            self::remove_if_exists($target_root . DIRECTORY_SEPARATOR . 'yarn.lock');
            self::delete_directory($target_root . DIRECTORY_SEPARATOR . 'bin');
            self::delete_directory($target_root . DIRECTORY_SEPARATOR . 'node_modules');
            self::prune_vendor_tree($target_root . DIRECTORY_SEPARATOR . 'vendor');
        } else {
            $exclusions = self::default_deploy_exclusions();

            if (!self::copy_tree($source_root, $target_root, $exclusions)) {
                Console::error('Failed to copy project files into working directory.');
                self::delete_directory($temp_dir);
                return 1;
            }

            self::post_process_deploy($target_root, array());
            self::prune_vendor_tree($target_root . DIRECTORY_SEPARATOR . 'vendor');
        }

        $archive_path = rtrim($dist_root, '/\\') . DIRECTORY_SEPARATOR . $label . '.zip';
        if (!self::create_zip_archive($target_root, $archive_path)) {
            Console::error('Failed to create distribution archive.');
            self::delete_directory($temp_dir);
            return 1;
        }

        Console::info('Distribution archive created: ' . self::relative_path($archive_path));

        self::do_action_safe(
            'wpmoo_cli_dist_completed',
            array(
                'label'   => $label,
                'version' => $version,
                'archive' => $archive_path,
                'path'    => $target_root,
                'source'  => $source_root,
                'options' => $options,
            )
        );

        if (!$options['keep']) {
            self::delete_directory($temp_dir);
        } else {
            Console::comment('Working directory preserved at ' . self::relative_path($temp_dir));
        }

        Console::line();
        return 0;
    }
}

