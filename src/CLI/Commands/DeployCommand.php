<?php

namespace WPMoo\CLI\Commands;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Support\Base;
use WPMoo\CLI\Console;

class DeployCommand extends Base implements CommandInterface {
    public function handle(array $args = array()) {
        $options = self::parse_deploy_options($args);
        $base    = self::base_path();
        $slug    = self::plugin_slug();

        Console::line();
        Console::comment('Preparing deployable package…');

        $target_input = $options['target'];

        if (null === $target_input || '' === $target_input) {
            $target_input = self::default_deploy_directory();
            Console::comment('→ No destination provided; will use ' . self::relative_path($target_input));
        }

        $target_path = self::normalize_absolute_path($target_input);

        if (!$target_path) {
            Console::error('Unable to resolve deployment path.');
            Console::line();
            return 1;
        }

        $options['target'] = $target_path;

        $is_zip      = (bool) $options['zip'];
        $zip_path    = null;
        $working_dir = $target_path;
        $cleanup_dir = false;

        if ($is_zip || self::ends_with_zip($target_path)) {
            $zip_path = $options['zip-path'];

            if (!$zip_path) {
                if (self::ends_with_zip($target_path)) {
                    $zip_path = $target_path;
                } else {
                    $zip_path = self::default_deploy_zip_path($target_path, $slug);
                }
            }

            $zip_path = self::normalize_absolute_path($zip_path);

            if (!$zip_path) {
                Console::error('Unable to resolve zip output path.');
                Console::line();
                return 1;
            }

            $working_dir = self::create_temp_directory($slug . '-deploy-');

            if (!$working_dir) {
                Console::error('Could not create temporary directory for archive generation.');
                Console::line();
                return 1;
            }

            $cleanup_dir = true;
            $is_zip      = true;

            if (!self::ensure_directory(dirname($zip_path) . DIRECTORY_SEPARATOR)) {
                Console::error('Unable to create directories for zip output.');
                self::delete_directory($working_dir);
                Console::line();
                return 1;
            }
        } elseif (self::path_is_within($target_path, $base)) {
            Console::error('Deployment path cannot be inside the source directory.');
            Console::line();
            return 1;
        }

        $options['zip']       = $is_zip;
        $options['zip-path']  = $zip_path;
        $options['work-path'] = $working_dir;

        self::do_action_safe('wpmoo_cli_deploy_start', $base, $options);

        if (!$options['no-build']) {
            self::do_action_safe('wpmoo_cli_deploy_before_build', $base, $options);

            Console::comment('→ Building assets before packaging');

            $build_success = self::perform_build(array(
                'pm'            => $options['pm'],
                'script'        => $options['script'],
                'force-install' => $options['force-install'],
                'skip-install'  => $options['skip-install'],
                'allow-missing' => true,
            ));

            if (!$build_success) {
                if ($cleanup_dir) {
                    self::delete_directory($working_dir);
                }
                Console::error('Deployment aborted due to build failure.');
                Console::line();
                return 1;
            }

            self::do_action_safe('wpmoo_cli_deploy_after_build', $base, $options);
        } else {
            Console::comment('→ Skipping asset build (--no-build specified)');
        }

        $exclusions = self::apply_filters_safe(
            'wpmoo_cli_deploy_exclusions',
            self::default_deploy_exclusions(),
            $base,
            $options
        );

        if (!is_array($exclusions)) {
            $exclusions = self::default_deploy_exclusions();
        }

        if (!$is_zip) {
            if (is_dir($working_dir)) {
                Console::comment('→ Clearing destination directory');
                self::delete_directory($working_dir);
            }

            if (!self::ensure_directory(rtrim($working_dir, '/\\') . DIRECTORY_SEPARATOR)) {
                Console::error('Unable to prepare destination directory.');
                Console::line();
                return 1;
            }
        }

        Console::comment('→ Copying files to ' . self::relative_path($working_dir));

        $copy_ok = self::copy_tree(
            rtrim($base, '/\\'),
            rtrim($working_dir, '/\\'),
            $exclusions
        );

        if (!$copy_ok) {
            if ($cleanup_dir) {
                self::delete_directory($working_dir);
            }

            Console::error('Failed to copy files for deployment.');
            Console::line();
            return 1;
        }

        self::post_process_deploy($working_dir, $options);

        if ($is_zip && $zip_path) {
            Console::comment('→ Creating archive ' . self::relative_path($zip_path));

            $zip_ok = self::create_zip_archive($working_dir, $zip_path);

            if (!$zip_ok) {
                Console::error('Failed to create deployment archive.');
                if ($cleanup_dir) {
                    self::delete_directory($working_dir);
                }
                Console::line();
                return 1;
            }

            Console::info('Deployment archive ready at ' . self::relative_path($zip_path));
        } else {
            Console::info('Deployment directory ready at ' . self::relative_path($working_dir));
        }

        self::do_action_safe(
            'wpmoo_cli_deploy_completed',
            $base,
            array(
                'destination' => $is_zip && $zip_path ? $zip_path : $working_dir,
                'options'     => $options,
            )
        );

        if ($cleanup_dir) {
            self::delete_directory($working_dir);
        }

        Console::line();
        return 0;
    }
}

