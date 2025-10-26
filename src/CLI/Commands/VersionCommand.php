<?php

namespace WPMoo\CLI\Commands;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Support\Base;
use WPMoo\CLI\Console;

class VersionCommand extends Base implements CommandInterface {
    public function handle(array $args = array()) {
        $base = self::base_path();

        $current_version = self::detect_current_version($base);

        if (!$current_version) {
            Console::error('Could not determine current version from composer.json.');
            return 1;
        }

        $options = self::parse_version_arguments($args);

        $requested_version = null;

        if ($options['explicit']) {
            $requested_version = self::sanitize_version_input($options['explicit']);

            if (!self::is_valid_semver($requested_version)) {
                Console::error('Explicit version "' . $options['explicit'] . '" is not a valid semantic version (expected format x.y.z).');
                return 1;
            }
        } else {
            $bump_type         = $options['bump'] ? $options['bump'] : 'patch';
            $requested_version = self::bump_semver($current_version, $bump_type, $options['pre-release']);

            if (!$requested_version) {
                Console::error('Unable to compute new version from current value ' . $current_version);
                return 1;
            }
        }

        if ($requested_version === $current_version) {
            Console::comment('Version remains unchanged (' . $current_version . '). Nothing to do.');
            return 0;
        }

        Console::line();
        Console::comment('Updating WPMoo version: ' . $current_version . ' → ' . $requested_version);

        $updated_files = self::update_version_files(
            $base,
            $current_version,
            $requested_version,
            $options['dry-run']
        );

        if (empty($updated_files)) {
            Console::warning('No files required updating. Verify project structure.');
        } else {
            foreach ($updated_files as $file) {
                Console::line(
                    ($options['dry-run'] ? '[dry-run] ' : '') .
                    '   • ' . self::relative_path($file)
                );
            }
        }

        if ($options['dry-run']) {
            Console::info('Dry run completed. No files were modified.');
            Console::line();
            return 0;
        }

        Console::info('Version updated successfully to ' . $requested_version);

        self::do_action_safe(
            'wpmoo_cli_version_completed',
            $current_version,
            $requested_version,
            array(
                'files'   => $updated_files,
                'options' => $options,
            )
        );

        Console::line();
        return 0;
    }
}

