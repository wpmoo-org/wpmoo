<?php

namespace WPMoo\CLI\Commands;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Support\Base;
use WPMoo\CLI\Console;

/**
 * Rename a starter plugin to a new Name/Slug/Namespace.
 *
 * Usage examples:
 *   php moo rename --name="Awesome Plugin" --namespace="Vendor\\Awesome" --slug=awesome-plugin
 *   php moo rename --slug=awesome-plugin   (keep current name/namespace)
 *   php moo rename --name="Awesome Plugin" (derive slug from name)
 */
class RenameCommand extends Base implements CommandInterface {
    public function handle(array $args = array()) {
        $base = self::base_path();

        // Support positional invocation like: php moo rename "My Plugin" [Namespace]
        $opts = $this->parseArgs($args);
        if (!isset($opts['name']) && !isset($opts['slug']) && !isset($opts['namespace'])) {
            $pos = $this->parsePositionals($args);
            $opts = array_merge($opts, $pos);
        }

        $meta = self::detect_project_metadata(rtrim($base, '/\\'));
        $oldName = isset($meta['name']) ? (string) $meta['name'] : '';
        $oldSlug = isset($meta['slug']) ? (string) $meta['slug'] : '';
        $mainFile = isset($meta['main']) ? (string) $meta['main'] : '';
        $oldNs = (string) (self::detect_primary_namespace($base) ?: '');
        $oldNs = rtrim($oldNs, '\\');

        // Derivations from provided name if needed
        $newName = $opts['name'] ?: $oldName;
        $derivedNamespace = $newName ? $this->deriveNamespace($newName) : '';
        $newNs   = $opts['namespace'] ?: ($derivedNamespace ?: $oldNs);
        $newSlug = $opts['slug'] ?: ($newName ? $this->deriveSlugHyphen($newName) : $oldSlug);

        if ('' === $newName && '' === $newSlug && '' === $newNs) {
            Console::error('Unable to determine new values. Provide at least --name, --slug, or --namespace.');
            return 1;
        }

        // Compute friendly forms like WPBones for display
        $slug_underscore = str_replace('-', '_', $newSlug);
        $vars_key        = $slug_underscore . '_vars';
        $hyphen_id       = $newSlug;

        Console::line();
        Console::comment('Renaming plugin...');
        Console::line();
        Console::line('  ' . $newName . '          Name of plugin');
        Console::line('  ' . $newNs . '            Namespace (PSR-4)');
        Console::line('  ' . $slug_underscore . '_slug' . '     Plugin slug');
        Console::line('  ' . $vars_key . '     Plugin vars (CPT/Taxonomy)');
        Console::line('  ' . $hyphen_id . '          Internal ID (CSS/JS)');
        Console::line('  ' . $hyphen_id . '.php  Main plugin file');
        Console::line();

        $changed = array();

        // 1) Rename main plugin file to <slug>.php when possible.
        if ($mainFile && $newSlug) {
            $dir = dirname($mainFile);
            $newMain = $dir . DIRECTORY_SEPARATOR . $newSlug . '.php';
            if (strcasecmp(basename($mainFile), basename($newMain)) !== 0) {
                if (@rename($mainFile, $newMain)) {
                    $changed[] = $newMain;
                    $mainFile = $newMain;
                    Console::line('   • Renamed main file to ' . self::relative_path($newMain));
                } else {
                    Console::warning('Could not rename main plugin file. You may rename it manually to ' . basename($newMain));
                }
            }
        }

        // 2) Update plugin header (Plugin Name, Text Domain) in main file.
        if ($mainFile && file_exists($mainFile)) {
            $contents = file_get_contents($mainFile);
            if (false !== $contents) {
                $updated = $contents;
                if ($newName) {
                    $updated = preg_replace('/^([ \t\/*#@]*Plugin Name:\s*).*/mi', '$1' . addcslashes($newName, '\\$'), $updated);
                }
                if ($newSlug) {
                    if (preg_match('/^([ \t\/*#@]*Text Domain:\s*).*/mi', $updated)) {
                        $updated = preg_replace('/^([ \t\/*#@]*Text Domain:\s*).*/mi', '$1' . addcslashes($newSlug, '\\$'), $updated);
                    } else {
                        // Insert Text Domain after Plugin Name line when missing.
                        $updated = preg_replace('/^([ \t\/*#@]*Plugin Name:.*)$/mi', "$1\n * Text Domain: " . addcslashes($newSlug, '\\$'), $updated, 1);
                    }
                }
                if ($updated !== null && $updated !== $contents) {
                    if (false !== file_put_contents($mainFile, $updated)) {
                        $changed[] = $mainFile;
                        Console::line('   • Updated plugin header in ' . self::relative_path($mainFile));
                    }
                }
            }
        }

        // 3) Rename POT file.
        if ($newSlug) {
            $langDir = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'languages';
            $oldPot = $langDir . DIRECTORY_SEPARATOR . $oldSlug . '.pot';
            $newPot = $langDir . DIRECTORY_SEPARATOR . $newSlug . '.pot';
            if ($oldSlug && file_exists($oldPot) && strcasecmp($oldPot, $newPot) !== 0) {
                if (@rename($oldPot, $newPot)) {
                    $changed[] = $newPot;
                    Console::line('   • Renamed POT file to ' . self::relative_path($newPot));
                }
            }
        }

        // 4) Update composer.json (name/package, psr-4 namespace).
        $composer = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($composer)) {
            $raw = file_get_contents($composer);
            if (false !== $raw) {
                $data = json_decode($raw, true) ?: array();
                $modified = false;
                // Adjust package name to keep vendor prefix if present
                if (!empty($opts['package'])) {
                    $data['name'] = (string) $opts['package'];
                    $modified = true;
                } elseif (!empty($data['name']) && is_string($data['name']) && false !== strpos($data['name'], '/')) {
                    list($vendor,) = explode('/', $data['name'], 2);
                    $data['name'] = $vendor . '/' . $newSlug;
                    $modified = true;
                }
                if ($newNs && isset($data['autoload']['psr-4']) && is_array($data['autoload']['psr-4'])) {
                    // Remove old root if present, add new root mapping.
                    $psr4 = $data['autoload']['psr-4'];
                    if ($oldNs && isset($psr4[$oldNs . '\\'])) {
                        unset($psr4[$oldNs . '\\']);
                        $modified = true;
                    }
                    if (!isset($psr4[$newNs . '\\'])) {
                        $psr4[$newNs . '\\'] = 'src/';
                        $modified = true;
                    }
                    $data['autoload']['psr-4'] = $psr4;
                }
                if ($modified) {
                    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    if (false !== $json) {
                        $json .= PHP_EOL;
                        if (false !== file_put_contents($composer, $json)) {
                            $changed[] = $composer;
                            Console::line('   • Updated composer.json');
                        }
                    }
                }
            }
        }

        // 5) Replace namespaces across src/ directory.
        if ($oldNs && $newNs && $oldNs !== $newNs) {
            $this->replaceInTree(rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'src', array(
                'namespace ' . $oldNs => 'namespace ' . $newNs,
                'use ' . $oldNs . '\\' => 'use ' . $newNs . '\\',
                $oldNs . '\\' => $newNs . '\\',
            ), $changed);
        }

        // 6) Replace text domain in PHP files.
        if ($oldSlug && $newSlug && $oldSlug !== $newSlug) {
            $this->replaceInTree(rtrim($base, '/\\'), array(
                "'" . $oldSlug . "'" => "'" . $newSlug . "'",
                '"' . $oldSlug . '"' => '"' . $newSlug . '"',
            ), $changed, function ($path) use ($base) {
                // Limit to project PHP files; skip vendor and dist.
                $rel = str_replace('\n', '/', self::relative_path($path));
                return (bool) preg_match('#/(vendor|dist|node_modules)/#', '/' . $rel) ? false : (substr($path, -4) === '.php');
            });
        }

        Console::line();
        Console::info('Rename completed.');
        if (!empty($changed)) {
            foreach (array_unique($changed) as $file) {
                Console::line('   • ' . self::relative_path($file));
            }
        }
        Console::line();
        return 0;
    }

    /**
     * Parse CLI args for rename command.
     * @param array<int, mixed> $args
     * @return array{name:?string,slug:?string,namespace:?string,package:?string}
     */
    protected function parseArgs(array $args) {
        $out = array('name' => null, 'slug' => null, 'namespace' => null, 'package' => null);
        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            $raw = (string) $args[$i];
            if ('' === $raw) { continue; }
            if (0 === strpos($raw, '--name=')) { $out['name'] = substr($raw, 7); continue; }
            if ('--name' === $raw && isset($args[$i+1])) { $out['name'] = (string) $args[++$i]; continue; }
            if (0 === strpos($raw, '--slug=')) { $out['slug'] = substr($raw, 7); continue; }
            if ('--slug' === $raw && isset($args[$i+1])) { $out['slug'] = (string) $args[++$i]; continue; }
            if (0 === strpos($raw, '--namespace=')) { $out['namespace'] = substr($raw, 12); continue; }
            if ('--namespace' === $raw && isset($args[$i+1])) { $out['namespace'] = (string) $args[++$i]; continue; }
            if (0 === strpos($raw, '--package=')) { $out['package'] = substr($raw, 10); continue; }
            if ('--package' === $raw && isset($args[$i+1])) { $out['package'] = (string) $args[++$i]; continue; }
        }
        return $out;
    }

    /**
     * Parse positional args: name [namespace]
     * @param array<int,mixed> $args
     * @return array{name:?string,namespace:?string}
     */
    protected function parsePositionals(array $args) {
        $out = array('name' => null, 'namespace' => null);
        $pos = array();
        foreach ($args as $a) {
            if (!is_string($a) || '' === trim($a)) { continue; }
            if (0 === strpos($a, '--')) { continue; }
            $pos[] = $a;
        }
        if (!empty($pos)) {
            $out['name'] = (string) $pos[0];
            if (isset($pos[1])) { $out['namespace'] = (string) $pos[1]; }
        }
        return $out;
    }

    /**
     * Derive a PSR-4 root namespace from a human-readable name.
     */
    protected function deriveNamespace($name) {
        $clean = preg_replace('/[^A-Za-z0-9]+/', ' ', (string) $name);
        $parts = preg_split('/\s+/', trim((string) $clean));
        $studly = '';
        foreach ($parts as $p) { $studly .= ucfirst(strtolower($p)); }
        return $studly ?: '';
    }

    /**
     * Derive a hyphenated slug from a human-readable name.
     */
    protected function deriveSlugHyphen($name) {
        $slug = strtolower((string) $name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim((string) $slug, '-');
        return $slug ?: 'plugin';
    }

    /**
     * Replace occurrences across a tree with basic filters.
     * @param string $root
     * @param array<string,string> $map
     * @param array<int,string> $changed
     * @param callable|null $filter path filter returning bool
     * @return void
     */
    protected function replaceInTree($root, array $map, array &$changed, $filter = null) {
        if (!is_dir($root)) { return; }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($filter && !$filter($path)) { continue; }
            if ($file->isDir()) { continue; }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, array('php','json','md','txt'), true)) { continue; }
            $contents = @file_get_contents($path);
            if (false === $contents) { continue; }
            $updated = $contents;
            foreach ($map as $search => $replace) {
                $updated = str_replace($search, $replace, $updated);
            }
            if ($updated !== $contents) {
                if (false !== @file_put_contents($path, $updated)) {
                    $changed[] = $path;
                }
            }
        }
    }
}
