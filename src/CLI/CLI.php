<?php

namespace WPMoo\CLI;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Commands\BuildCommand;
use WPMoo\CLI\Commands\DeployCommand;
use WPMoo\CLI\Commands\DistCommand;
use WPMoo\CLI\Commands\InfoCommand;
use WPMoo\CLI\Commands\UpdateCommand;
use WPMoo\CLI\Commands\VersionCommand;
use WPMoo\CLI\Commands\ReleaseCommand;
use WPMoo\CLI\Commands\RenameCommand;
use WPMoo\CLI\Console;
use WPMoo\CLI\Support\Base;

/**
 * CLI entrypoint and router.
 */
class CLI extends Base {
    /** @var array<string, CommandInterface> */
    protected $commands = array();

    public function __construct() {
        $this->commands = array(
            'info'    => new InfoCommand(),
            'update'  => new UpdateCommand(),
            'version' => new VersionCommand(),
            'dist'    => new DistCommand(),
            'build'   => new BuildCommand(),
            'deploy'  => new DeployCommand(),
            'release' => new ReleaseCommand(),
            'rename'  => new RenameCommand(),
        );
    }

    /**
     * Entry point compatible with bin/moo expectations.
     * @param array<int, mixed> $argv
     */
    public static function run($argv) {
        (new self())->handle($argv);
    }

    /**
     * Route and execute the command.
     * @param array<int, mixed> $argv
     * @return int
     */
    public function handle(array $argv) {
        $command = isset($argv[1]) ? (string) $argv[1] : 'help';

        if ('help' === $command || ! isset($this->commands[$command])) {
            $this->renderHelp();
            return 0;
        }

        $args = array_slice($argv, 2);
        return (int) $this->commands[$command]->handle($args);
    }

    /**
     * Render help: welcome header, usage, and available commands.
     */
    protected function renderHelp() {
        $this->renderWelcome();
        Console::line('Usage:');
        Console::line('  moo <command> [options]');
        Console::line();
        Console::comment('Available commands:');
        $definitions = $this->definitions();
        $width = 0;
        foreach (array_keys($definitions) as $cmd) { $width = max($width, strlen($cmd)); }
        foreach ($definitions as $cmd => $desc) {
            $padding = str_repeat(' ', $width - strlen($cmd) + 2);
            Console::line('  ' . $cmd . $padding . $desc);
        }
        Console::line();
    }

    /**
     * Command descriptions.
     * @return array<string,string>
     */
    protected function definitions() {
        return array(
            'info'    => 'Show framework info',
            'update'  => 'Run maintenance tasks (translations, etc.)',
            'version' => 'Bump framework version across manifests',
            'build'   => 'Build front-end assets',
            'deploy'  => 'Create a deployable copy (optionally zipped)',
            'dist'    => 'Produce a distributable archive',
            'release' => 'Release helpers (TBD)',
            'rename'  => 'Rename starter plugin (name/slug/namespace)'
        );
    }

    /**
     * Welcome header with environment summary.
     */
    protected function renderWelcome() {
        $summary = $this->environmentSummary();
        Console::line();
        foreach ($this->logoLines() as $line) { Console::banner($line); }
        $version = $summary['version'] ? $summary['version'] : 'dev';
        Console::comment('WPMoo Version ' . $version);
        Console::line();
        $wp_cli = $summary['wp_cli_version'] ? $summary['wp_cli_version'] : 'not detected';
        Console::comment('→ WP-CLI version: ' . $wp_cli);
        Console::comment(sprintf(
            '→ Current Plugin File, Name, Namespace: \'%s\', \'%s\', \'%s\'',
            $summary['plugin_file'] ? $summary['plugin_file'] : 'n/a',
            $summary['plugin_name'] ? $summary['plugin_name'] : 'n/a',
            $summary['plugin_namespace'] ? $summary['plugin_namespace'] : 'n/a'
        ));
        if ($summary['plugin_version']) { Console::comment('→ Plugin version: ' . $summary['plugin_version']); }
        Console::line();
    }

    /**
     * Gather environment metadata.
     * @return array<string,mixed>
     */
    protected function environmentSummary() {
        $base_path  = self::framework_base_path();
        $metadata   = self::detect_project_metadata(rtrim($base_path, '/\\'));
        $namespace  = self::detect_primary_namespace($base_path);
        $version    = defined('WPMOO_VERSION') ? WPMOO_VERSION : self::detect_current_version($base_path);
        $wp_cli_ver = $this->detectWpCliVersion();
        if (!$version && !empty($metadata['version'])) { $version = $metadata['version']; }
        return array(
            'version'          => $version,
            'wp_cli_version'   => $wp_cli_ver,
            'plugin_file'      => !empty($metadata['main']) ? basename((string) $metadata['main']) : null,
            'plugin_name'      => !empty($metadata['name']) ? $metadata['name'] : null,
            'plugin_version'   => !empty($metadata['version']) ? $metadata['version'] : null,
            'plugin_namespace' => $namespace,
        );
    }

    /** @return string|null */
    protected function detectWpCliVersion() {
        if (!function_exists('exec')) { return null; }
        $binary = self::locate_binary($this->wpBinaryCandidates(), array('wp', 'wp.bat'));
        if (!$binary) { return null; }
        list($status, $output) = self::execute_command($binary, array('--version'));
        if (0 !== $status || empty($output)) { return null; }
        $line = trim((string) $output[0]);
        if (preg_match('/([0-9]+\.[0-9.]+)/', $line, $m)) { return $m[1]; }
        return $line;
    }

    /** @return array<int,string> */
    protected function wpBinaryCandidates() {
        $candidates = array('vendor/bin/wp','vendor/bin/wp.bat','bin/wp','bin/wp.bat','wp-cli.phar','vendor/wp-cli.phar');
        for ($depth = 1; $depth <= 6; $depth++) {
            $prefix       = str_repeat('../', $depth);
            $candidates[] = $prefix . 'vendor/bin/wp';
            $candidates[] = $prefix . 'vendor/bin/wp.bat';
            $candidates[] = $prefix . 'bin/wp';
            $candidates[] = $prefix . 'bin/wp.bat';
            $candidates[] = $prefix . 'wp-cli.phar';
            $candidates[] = $prefix . 'vendor/wp-cli.phar';
            $candidates[] = $prefix . 'bin/wp-cli.phar';
        }
        return array_unique($candidates);
    }

    /** @return array<int,string> */
    protected function logoLines() {
        return array(
            '░██       ░██ ░█████████  ░███     ░███                       ',
            '░██       ░██ ░██     ░██ ░████   ░████                       ',
            '░██  ░██  ░██ ░██     ░██ ░██░██ ░██░██  ░███████   ░███████  ',
            '░██ ░████ ░██ ░█████████  ░██ ░████ ░██ ░██    ░██ ░██    ░██ ',
            '░██░██ ░██░██ ░██         ░██  ░██  ░██ ░██    ░██ ░██    ░██ ',
            '░████   ░████ ░██         ░██       ░██ ░██    ░██ ░██    ░██ ',
            '░███     ░███ ░██         ░██       ░██  ░███████   ░███████  ',
        );
    }
}
