<?php

namespace WPMoo\CLI\Support;

use WPMoo\Core\App;
use WPMoo\CLI\Console;
use WPMoo\Support\I18n\PotGenerator;

/**
 * Shared helpers for modular CLI commands.
 *
 * Extracted from the legacy WPMoo Core\CLI implementation. This class
 * intentionally contains no routing/entrypoint logic.
 */
class Base {
    /* ===== Version helpers ===== */

    protected static function sanitize_version_input( $value ) {
        $value = trim( (string) $value );
        $value = preg_replace( '/^v/i', '', $value );
        return $value;
    }

    protected static function is_valid_semver( $value ) {
        if ( '' === $value ) {
            return false;
        }
        return (bool) preg_match(
            '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/',
            $value
        );
    }

    protected static function bump_semver( $current, $type, $pre_release = null ) {
        if ( ! preg_match( '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-.+)?$/', $current, $matches ) ) {
            return null;
        }

        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3];

        switch ( $type ) {
            case 'major':
                ++$major; $minor = 0; $patch = 0; break;
            case 'minor':
                ++$minor; $patch = 0; break;
            case 'patch':
            default:
                ++$patch; break;
        }

        $new = $major . '.' . $minor . '.' . $patch;

        if ( $pre_release ) {
            $label = preg_replace( '/[^0-9A-Za-z\.-]/', '', $pre_release );
            if ( '' !== $label ) {
                $new .= '-' . $label;
            }
        }

        return $new;
    }

    protected static function update_version_files( $base_path, $current_version, $new_version, $dry_run = false ) {
        $updated = array();

        $files = array(
            $base_path . 'composer.json'           => 'json',
            $base_path . 'package.json'            => 'json',
            $base_path . 'wpmoo.php'               => 'bootstrap',
            $base_path . 'src/Options/Page.php'    => 'php',
            $base_path . 'src/Metabox/Metabox.php' => 'php',
        );

        foreach ( $files as $path => $type ) {
            if ( ! file_exists( $path ) ) {
                continue;
            }

            if ( 'json' === $type ) {
                if ( $dry_run ) { $updated[] = $path; continue; }
                $contents = file_get_contents( $path );
                if ( false === $contents ) { Console::warning( 'Failed to read ' . self::relative_path( $path ) ); continue; }
                $data = json_decode( $contents, true );
                if ( ! is_array( $data ) ) { Console::warning( 'Invalid JSON in ' . self::relative_path( $path ) ); continue; }
                $data['version'] = $new_version;
                if ( ! self::write_json_file( $path, $data ) ) { Console::warning( 'Could not write updated JSON to ' . self::relative_path( $path ) ); continue; }
                $updated[] = $path;
            } elseif ( 'php' === $type ) {
                if ( self::replace_version_literal( $path, $current_version, $new_version, $dry_run ) ) {
                    $updated[] = $path;
                }
            } elseif ( 'bootstrap' === $type ) {
                if ( self::update_bootstrap_version( $path, $current_version, $new_version, $dry_run ) ) {
                    $updated[] = $path;
                }
            }
        }
        // Also update plugin main header and readme.txt if present (plugin projects).
        $meta = self::detect_project_metadata( rtrim( $base_path, '/\\' ) );
        if ( ! empty( $meta['main'] ) && file_exists( $meta['main'] ) ) {
            $path = $meta['main'];
            $contents = file_get_contents( $path );
            if ( false !== $contents ) {
                $new = preg_replace( '/^([ \t\/*#@]*Version:\s*).*/mi', '$1' . addcslashes( $new_version, '\\$' ), $contents, 1, $cnt );
                if ( null !== $new && ( $dry_run || false !== file_put_contents( $path, $new ) ) ) {
                    $updated[] = $path;
                }
            }
        }
        $readme = rtrim( $base_path, '/\\' ) . DIRECTORY_SEPARATOR . 'readme.txt';
        if ( file_exists( $readme ) ) {
            $contents = file_get_contents( $readme );
            if ( false !== $contents ) {
                $new = preg_replace( '/^([ \t\/*#@]*Stable tag:\s*).*/mi', '$1' . addcslashes( $new_version, '\\$' ), $contents, 1, $cnt );
                if ( null !== $new && ( $dry_run || false !== file_put_contents( $readme, $new ) ) ) {
                    $updated[] = $readme;
                }
            }
        }

        return $updated;
    }

    protected static function write_json_file( $path, array $data ) {
        $json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        if ( false === $json ) { return false; }
        $json .= PHP_EOL;
        return false !== file_put_contents( $path, $json );
    }

    protected static function replace_version_literal( $path, $current_version, $new_version, $dry_run = false ) {
        $contents = file_get_contents( $path );
        if ( false === $contents ) { Console::warning( 'Failed to read ' . self::relative_path( $path ) ); return false; }
        $count   = 0;
        $updated = str_replace( "'" . $current_version . "'", "'" . $new_version . "'", $contents, $count );
        if ( 0 === $count ) {
            $updated = str_replace( '"' . $current_version . '"', '"' . $new_version . '"', $contents, $count );
        }
        if ( 0 === $count ) {
            $pattern = "/(['\"])((?:0|[1-9]\\d*)\\.(?:0|[1-9]\\d*)\\.(?:0|[1-9]\\d*)(?:-[^'\"])?)\\1/";
            $updated = preg_replace( $pattern, '\\1' . addcslashes( $new_version, '\\$' ) . '\\1', $contents, 1, $count );
        }
        if ( 0 === $count || null === $updated ) { return false; }
        if ( $dry_run ) { return true; }
        return false !== file_put_contents( $path, $updated );
    }

    protected static function update_bootstrap_version( $path, $current_version, $new_version, $dry_run = false ) {
        $contents = file_get_contents( $path );
        if ( false === $contents ) { Console::warning( 'Failed to read ' . self::relative_path( $path ) ); return false; }
        $count   = 0;
        $pattern = "/(define\(\s*['\"]WPMOO_VERSION['\"]\s*,\s*['\"])([^'\"]+)(['\"]\s*\))/";
        $updated = preg_replace( $pattern, '\\1' . addcslashes( $new_version, '\\$' ) . '\\3', $contents, 1, $count );
        if ( 0 === $count || null === $updated ) { return false; }
        if ( $dry_run ) { return true; }
        return false !== file_put_contents( $path, $updated );
    }

    /* ===== Build helpers ===== */

    protected static function parse_build_options( array $args ) {
        $options = array(
            'pm'            => null,
            'script'        => 'build',
            'force-install' => false,
            'skip-install'  => false,
        );
        foreach ( $args as $arg ) {
            if ( ! is_string( $arg ) || '' === $arg ) { continue; }
            if ( 0 === strpos( $arg, '--pm=' ) ) { $options['pm'] = substr( $arg, 5 ); }
            elseif ( 0 === strpos( $arg, '--pkgm=' ) ) { $options['pm'] = substr( $arg, 7 ); }
            elseif ( '--install' === $arg || '--force-install' === $arg ) { $options['force-install'] = true; }
            elseif ( '--no-install' === $arg ) { $options['skip-install'] = true; }
            elseif ( 0 === strpos( $arg, '--script=' ) ) { $script = substr( $arg, 9 ); if ( '' !== $script ) { $options['script'] = $script; } }
        }
        return $options;
    }

    protected static function perform_build( array $options = array() ) {
        $defaults = array(
            'pm'            => null,
            'script'        => 'build',
            'force-install' => false,
            'skip-install'  => false,
            'allow-missing' => false,
        );
        $options = array_merge( $defaults, $options );
        $base    = self::base_path();
        $pkg     = $base . 'package.json';
        if ( ! file_exists( $pkg ) ) {
            if ( $options['allow-missing'] ) { Console::comment( '→ No package.json detected; skipping asset build.' ); return true; }
            Console::error( 'No package.json detected; cannot run build.' );
            return false;
        }
        $manager = self::detect_package_manager( $base, $options['pm'] );
        if ( ! $manager ) { Console::error( 'Could not determine an available package manager. Install npm, yarn, pnpm, or bun (or pass --pm=<manager>).' ); return false; }
        $name   = $manager['name'];
        $binary = $manager['binary'];
        Console::comment( '→ Using ' . $name . ' (' . $binary . ')' );
        $should_install = (bool) $options['force-install'];
        if ( ! $should_install && ! $options['skip-install'] && ! is_dir( $base . 'node_modules' ) ) { $should_install = true; }
        if ( $should_install ) {
            Console::comment( '   • Installing dependencies' );
            list( $install_status, $install_output ) = self::execute_command( $binary, self::install_arguments( $name ), $base );
            self::output_command_lines( $install_output );
            if ( 0 !== $install_status ) { Console::error( 'Dependency installation failed with status ' . $install_status . '.' ); return false; }
        } elseif ( ! is_dir( $base . 'node_modules' ) ) {
            Console::warning( '   • node_modules missing; continuing without installation (build may fail).' );
        }
        Console::comment( '   • Running ' . $name . ' ' . self::format_run_command( $name, $options['script'] ) );
        list( $build_status, $build_output ) = self::execute_command( $binary, self::build_arguments( $name, $options['script'] ), $base );
        self::output_command_lines( $build_output );
        if ( 0 !== $build_status ) { Console::error( 'Build script exited with status ' . $build_status . '.' ); return false; }
        Console::info( '→ Asset build completed.' );
        self::do_action_safe( 'wpmoo_cli_build_completed', $name, $base, $options );
        return true;
    }

    protected static function detect_package_manager( $base_path, $hint = null ) {
        $pm = null;
        $bin = null;
        $candidates = array();
        if ( $hint ) { $candidates[] = $hint; }
        $candidates = array_merge( $candidates, array( 'pnpm', 'bun', 'yarn', 'npm' ) );
        foreach ( $candidates as $name ) {
            $bin = self::search_system_path( $name );
            if ( $bin ) { $pm = $name; break; }
        }
        if ( ! $pm || ! $bin ) { return null; }
        return array( 'name' => $pm, 'binary' => $bin );
    }

    protected static function install_arguments( $manager ) {
        switch ( $manager ) {
            case 'pnpm': return array( 'install', '--frozen-lockfile' );
            case 'yarn': return array( 'install', '--frozen-lockfile' );
            case 'bun':  return array( 'install' );
            case 'npm':
            default:     return array( 'ci' );
        }
    }

    protected static function build_arguments( $manager, $script ) {
        switch ( $manager ) {
            case 'pnpm': return array( 'run', $script );
            case 'yarn': return array( $script );
            case 'bun':  return array( 'run', $script );
            case 'npm':
            default:     return array( 'run', $script );
        }
    }

    protected static function format_run_command( $manager, $script ) {
        switch ( $manager ) {
            case 'yarn': return $script;
            default:     return 'run ' . $script;
        }
    }

    /* ===== Deploy helpers ===== */

    protected static function parse_deploy_options( array $args ) {
        $options = self::parse_build_options( $args );
        $options['target']    = null;
        $options['zip']       = false;
        $options['zip-path']  = null;
        $options['no-build']  = false;
        $options['work-path'] = null;
        foreach ( $args as $arg ) {
            if ( ! is_string( $arg ) || '' === $arg ) { continue; }
            if ( '--no-build' === $arg ) { $options['no-build'] = true; }
            elseif ( '--zip' === $arg || '--create-zip' === $arg ) { $options['zip'] = true; }
            elseif ( 0 === strpos( $arg, '--zip=' ) ) { $options['zip'] = true; $zip_value = substr( $arg, 6 ); $options['zip-path'] = '' !== $zip_value ? $zip_value : null; }
            elseif ( 0 === strpos( $arg, '--pm=' ) || 0 === strpos( $arg, '--pkgm=' ) || 0 === strpos( $arg, '--script=' ) ) { /* handled above */ }
            elseif ( '--install' === $arg || '--force-install' === $arg || '--no-install' === $arg ) { /* handled above */ }
            elseif ( '-' !== substr( $arg, 0, 1 ) && null === $options['target'] ) { $options['target'] = $arg; }
        }
        if ( $options['target'] && self::ends_with_zip( $options['target'] ) ) {
            $options['zip'] = true;
            if ( ! $options['zip-path'] ) { $options['zip-path'] = $options['target']; }
        }
        return $options;
    }

    protected static function default_deploy_directory() {
        $base   = self::base_path();
        $parent = dirname( rtrim( $base, '/\\' ) );
        return rtrim( $parent, '/\\' ) . DIRECTORY_SEPARATOR . 'dist';
    }

    protected static function default_deploy_zip_path( $target, $slug ) {
        $dir = is_dir( $target ) ? $target : dirname( $target );
        return rtrim( $dir, '/\\' ) . DIRECTORY_SEPARATOR . $slug . '.zip';
    }

    protected static function default_deploy_exclusions() {
        return array(
            '/.git', '/.github', '/.cache', '/bin', '/temp', '/tests', '/.DS_Store',
            '/node_modules', '/vendor/bin', '/vendor/composer/installers',
            '/composer.json', '/composer.lock', '/package.json', '/package-lock.json', '/pnpm-lock.yaml', '/yarn.lock',
            '/.gitignore', '/.gitattributes', '/phpcs.xml', '/phpcs.xml.dist', '/.phpcs.xml', '/.phpcs.xml.dist',
            '/deploy.php', '/webpack.mix.js', '/webpack.config.js', '/gulpfile.js', '/vite.config.js',
        );
    }

    protected static function relative_path( $path ) {
        $base = self::base_path();
        $normalized_base = str_replace( '\\', '/', $base );
        $normalized_path = str_replace( '\\', '/', $path );
        if ( 0 === strpos( $normalized_path, $normalized_base ) ) {
            return ltrim( substr( $normalized_path, strlen( $normalized_base ) ), '/' );
        }
        return $path;
    }

    protected static function normalize_absolute_path( $path ) {
        if ( null === $path || '' === $path ) { return null; }
        $trimmed = rtrim( (string) $path );
        $real    = realpath( $trimmed );
        if ( false !== $real ) { return rtrim( $real, '/\\' ); }
        $dir = dirname( $trimmed );
        if ( ! is_dir( $dir ) ) { return null; }
        return rtrim( $trimmed, '/\\' );
    }

    protected static function ends_with_zip( $value ) { return (bool) preg_match( '/\.zip$/i', (string) $value ); }
    protected static function path_is_within( $path, $container ) {
        $p = str_replace( '\\', '/', realpath( $path ) ?: $path );
        $c = rtrim( str_replace( '\\', '/', realpath( $container ) ?: $container ), '/' ) . '/';
        return 0 === strpos( $p . '/', $c );
    }

    protected static function ensure_directory( $directory ) {
        if ( is_dir( $directory ) ) { return true; }
        if ( @mkdir( $directory, 0755, true ) ) { return true; }
        Console::error( 'Unable to create directory: ' . self::relative_path( $directory ) );
        return false;
    }

    protected static function create_temp_directory( $prefix = 'wpmoo-' ) {
        $base = sys_get_temp_dir();
        for ( $i = 0; $i < 5; $i++ ) {
            $path = rtrim( $base, '/\\' ) . DIRECTORY_SEPARATOR . $prefix . uniqid();
            if ( @mkdir( $path, 0755, true ) ) { return $path; }
        }
        return null;
    }

    protected static function copy_tree( $source_root, $destination_root, array $exclusions ) {
        $source_root      = rtrim( $source_root, '/\\' );
        $destination_root = rtrim( $destination_root, '/\\' );
        $skip = array();
        foreach ( $exclusions as $rule ) { $skip[ rtrim( $rule, '/\\' ) ] = true; }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $source_root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ( $iterator as $item ) {
            $path     = $item->getPathname();
            $relative = substr( $path, strlen( $source_root ) );
            $relative = str_replace( '\\', '/', $relative );
            foreach ( $skip as $rule => $true ) {
                if ( 0 === strpos( $relative, $rule ) ) { continue 2; }
            }
            $target = $destination_root . $relative;
            if ( $item->isDir() ) {
                if ( ! is_dir( $target ) && ! @mkdir( $target, 0755, true ) ) { return false; }
            } else {
                $dir = dirname( $target );
                if ( ! is_dir( $dir ) && ! @mkdir( $dir, 0755, true ) ) { return false; }
                if ( ! @copy( $path, $target ) ) { return false; }
            }
        }
        return true;
    }

    protected static function copy_within_dist( $source, $destination ) {
        if ( is_dir( $source ) ) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ( $it as $item ) {
                $rel = substr( $item->getPathname(), strlen( rtrim( $source, '/\\' ) ) );
                $dst = rtrim( $destination, '/\\' ) . $rel;
                if ( $item->isDir() ) { if ( ! is_dir( $dst ) ) { @mkdir( $dst, 0755, true ); } }
                else { $dir = dirname( $dst ); if ( ! is_dir( $dir ) ) { @mkdir( $dir, 0755, true ); } @copy( $item->getPathname(), $dst ); }
            }
            return true;
        }
        $dir = dirname( $destination );
        if ( ! is_dir( $dir ) ) { @mkdir( $dir, 0755, true ); }
        return @copy( $source, $destination );
    }

    protected static function post_process_deploy( $working_dir, array $options ) {
        $moo_path = $working_dir . DIRECTORY_SEPARATOR . 'moo';
        if ( file_exists( $moo_path ) ) { @unlink( $moo_path ); }
        $composer_success = self::optimise_composer_dependencies( $working_dir );
        $keep_composer_json = self::apply_filters_safe( 'wpmoo_cli_deploy_keep_composer_json', false, $working_dir, $options, $composer_success );
        if ( ! $keep_composer_json ) { self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'composer.json' ); }
        $keep_composer_lock = self::apply_filters_safe( 'wpmoo_cli_deploy_keep_composer_lock', false, $working_dir, $options, $composer_success );
        if ( ! $keep_composer_lock ) { self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'composer.lock' ); }
        $keep_package_manifest = self::apply_filters_safe( 'wpmoo_cli_deploy_keep_package_json', false, $working_dir, $options, $composer_success );
        if ( ! $keep_package_manifest ) {
            self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'package.json' );
            self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'package-lock.json' );
            self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'pnpm-lock.yaml' );
            self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'yarn.lock' );
        }
        self::delete_directory( $working_dir . DIRECTORY_SEPARATOR . 'bin' );
        self::refresh_embedded_framework( $working_dir );
        self::prune_vendor_tree( $working_dir . DIRECTORY_SEPARATOR . 'vendor' );
    }

    protected static function optimise_composer_dependencies( $working_dir ) {
        $composer_json = $working_dir . DIRECTORY_SEPARATOR . 'composer.json';
        if ( ! file_exists( $composer_json ) ) { return false; }
        if ( self::composer_uses_path_repositories( $composer_json ) ) { Console::comment( '→ Composer path repositories detected; skipping dependency optimisation.' ); return false; }
        $composer_binary = self::locate_composer_binary( $working_dir );
        if ( ! $composer_binary ) { Console::comment( '→ Composer binary not found; skipping dependency optimisation.' ); return false; }
        Console::comment( '→ Optimising composer dependencies (--no-dev)' );
        $args = array( 'install', '--no-dev', '--prefer-dist', '--no-interaction', '--no-progress', '--optimize-autoloader' );
        list( $status, $output ) = self::execute_command( $composer_binary, $args, $working_dir );
        self::output_command_lines( $output );
        if ( 0 !== $status ) { Console::warning( 'Composer install failed (exit code ' . $status . '). Existing vendor directory retained.' ); return false; }
        return true;
    }

    protected static function locate_composer_binary( $working_dir ) {
        $candidates = array(
            $working_dir . DIRECTORY_SEPARATOR . 'composer.phar',
            $working_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer',
            $working_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer.phar',
        );
        foreach ( $candidates as $candidate ) { if ( file_exists( $candidate ) ) { return $candidate; } }
        $composer = self::search_system_path( 'composer' ); if ( $composer ) { return $composer; }
        $composer_phar = self::search_system_path( 'composer.phar' ); if ( $composer_phar ) { return $composer_phar; }
        return null;
    }

    protected static function prune_vendor_tree( $vendor_path ) {
        if ( ! $vendor_path || ! is_dir( $vendor_path ) ) { return; }
        $root = rtrim( $vendor_path, '/\\' );
        // Top-level entries we can safely remove without iterating while mutating.
        $prune = array( '/bin', '/.git', '/.github', '/tests', '/docs', '/examples', '/.gitignore', '/.gitattributes' );
        foreach ( $prune as $rel ) {
            $target = $root . $rel;
            if ( is_dir( $target ) ) {
                self::delete_directory( $target );
            } elseif ( is_file( $target ) ) {
                @unlink( $target );
            }
        }
    }

    protected static function ensure_minified_assets( $assets_dir ) {
        if ( ! is_dir( $assets_dir ) ) { return; }
        // Placeholder for asset minification; currently noop to avoid bringing in toolchains.
    }

    protected static function prune_assets_tree( $assets_dir ) {
        if ( ! is_dir( $assets_dir ) ) { return; }
        $prune = array( '/src', '/.sass-cache', '/.cache', '/.git', '/.github' );
        foreach ( $prune as $rule ) { self::delete_directory( rtrim( $assets_dir, '/\\' ) . $rule ); }
    }

    protected static function delete_directory( $path ) {
        if ( ! $path || ! file_exists( $path ) ) { return; }
        if ( is_file( $path ) || is_link( $path ) ) { @unlink( $path ); return; }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ( $it as $item ) {
            $p = $item->getPathname();
            if ( $item->isDir() ) { @rmdir( $p ); } else { @unlink( $p ); }
        }
        @rmdir( $path );
    }

    protected static function remove_if_exists( $path ) { if ( file_exists( $path ) && ! is_dir( $path ) ) { @unlink( $path ); } }

    protected static function create_zip_archive( $source_dir, $zip_path ) {
        if ( ! class_exists( '\\ZipArchive' ) ) { Console::error( 'PHP ZipArchive not available.' ); return false; }
        $zip = new \ZipArchive();
        if ( true !== $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) { return false; }
        $root = rtrim( realpath( $source_dir ) ?: $source_dir, '/\\' ) . DIRECTORY_SEPARATOR;
        $len  = strlen( $root );
        $it   = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
        foreach ( $it as $file ) {
            $path = $file->getPathname();
            $local = substr( $path, $len );
            if ( $file->isDir() ) { $zip->addEmptyDir( $local ); }
            else { $zip->addFile( $path, $local ); }
        }
        $zip->close();
        return true;
    }

    protected static function refresh_embedded_framework( $working_dir ) {
        $framework_path = $working_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'wpmoo-org';
        if ( ! is_dir( $framework_path ) ) { return; }
        $framework_path .= DIRECTORY_SEPARATOR . 'wpmoo';
        if ( is_link( $framework_path ) || is_file( $framework_path ) ) { @unlink( $framework_path ); }
        else { self::delete_directory( $framework_path ); }
        if ( ! is_dir( $framework_path ) && ! @mkdir( $framework_path, 0755, true ) ) { Console::warning( 'Unable to rebuild embedded WPMoo runtime directory.' ); return; }
        $source_root = rtrim( self::framework_base_path(), '/\\' );
        self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'wpmoo.php', $framework_path . DIRECTORY_SEPARATOR . 'wpmoo.php' );
        self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'src', $framework_path . DIRECTORY_SEPARATOR . 'src' );
        self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'languages', $framework_path . DIRECTORY_SEPARATOR . 'languages' );
        self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'assets', $framework_path . DIRECTORY_SEPARATOR . 'assets' );
        self::ensure_minified_assets( $framework_path . DIRECTORY_SEPARATOR . 'assets' );
        self::prune_assets_tree( $framework_path . DIRECTORY_SEPARATOR . 'assets' );
        // Remove CLI sources from the embedded runtime copy (not needed on wp.org).
        self::delete_directory( $framework_path . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'CLI' );
        self::remove_if_exists( $framework_path . DIRECTORY_SEPARATOR . 'composer.json' );
        self::remove_if_exists( $framework_path . DIRECTORY_SEPARATOR . 'composer.lock' );
        self::remove_if_exists( $framework_path . DIRECTORY_SEPARATOR . 'package.json' );
        self::remove_if_exists( $framework_path . DIRECTORY_SEPARATOR . 'package-lock.json' );
        self::remove_if_exists( $framework_path . DIRECTORY_SEPARATOR . 'pnpm-lock.yaml' );
        self::remove_if_exists( $framework_path . DIRECTORY_SEPARATOR . 'yarn.lock' );
        self::delete_directory( $framework_path . DIRECTORY_SEPARATOR . 'bin' );
        self::delete_directory( $framework_path . DIRECTORY_SEPARATOR . 'node_modules' );
        self::delete_directory( $framework_path . DIRECTORY_SEPARATOR . 'vendor' );
    }

    /* ===== Dist helpers ===== */

    protected static function parse_dist_options( array $args ) {
        $options = array( 'label' => null, 'output' => null, 'source' => null, 'version' => null, 'keep' => false );
        $count = count( $args );
        for ( $index = 0; $index < $count; $index++ ) {
            $raw = $args[ $index ]; if ( ! is_string( $raw ) ) { continue; }
            $arg = trim( $raw ); if ( '' === $arg ) { continue; }
            if ( 0 === strpos( $arg, '--label=' ) ) { $options['label'] = substr( $arg, 8 ); continue; }
            if ( '--label' === $arg && isset( $args[ $index + 1 ] ) ) { $options['label'] = trim( (string) $args[ ++$index ] ); continue; }
            if ( 0 === strpos( $arg, '--output=' ) ) { $options['output'] = substr( $arg, 9 ); continue; }
            if ( '--output' === $arg && isset( $args[ $index + 1 ] ) ) { $options['output'] = trim( (string) $args[ ++$index ] ); continue; }
            if ( '--keep' === $arg ) { $options['keep'] = true; continue; }
            if ( 0 === strpos( $arg, '--source=' ) ) { $options['source'] = substr( $arg, 9 ); continue; }
            if ( '--source' === $arg && isset( $args[ $index + 1 ] ) ) { $options['source'] = trim( (string) $args[ ++$index ] ); continue; }
            if ( 0 === strpos( $arg, '--version=' ) ) { $options['version'] = substr( $arg, 10 ); continue; }
            if ( '--version' === $arg && isset( $args[ $index + 1 ] ) ) { $options['version'] = trim( (string) $args[ ++$index ] ); continue; }
        }
        return $options;
    }

    protected static function default_dist_source() {
        // Prefer the current plugin/project root if it looks like a plugin.
        $base = rtrim( self::base_path(), '/\\' );
        $meta = self::detect_project_metadata( $base );
        if ( ! empty( $meta['main'] ) ) {
            return $base;
        }
        // Fallback: try CWD when invoked from a project directory.
        $cwd = getcwd();
        if ( $cwd ) {
            $cwdn = rtrim( $cwd, '/\\' );
            $m2   = self::detect_project_metadata( $cwdn );
            if ( ! empty( $m2['main'] ) ) {
                return $cwdn;
            }
        }
        // Default to the framework itself.
        return rtrim( self::framework_base_path(), '/\\' );
    }

    protected static function default_dist_includes( $source_root ) {
        return array( 'wpmoo.php', 'src', 'languages', 'assets', 'vendor', 'README.md', 'LICENSE' );
    }

    protected static function paths_equal( $a, $b ) {
        $ra = realpath( $a ); $rb = realpath( $b );
        if ( false !== $ra && false !== $rb ) { return rtrim( $ra, '/\\' ) === rtrim( $rb, '/\\' ); }
        return rtrim( $a, '/\\' ) === rtrim( $b, '/\\' );
    }

    /* ===== Project/namespace helpers ===== */

    protected static function detect_project_metadata( $path ) {
        $metadata = array( 'name' => null, 'version' => null, 'main' => null, 'slug' => null );
        $composer = rtrim( $path, '/\\' ) . DIRECTORY_SEPARATOR . 'composer.json';
        if ( file_exists( $composer ) ) {
            $contents = file_get_contents( $composer );
            if ( false !== $contents ) {
                $data = json_decode( $contents, true );
                if ( is_array( $data ) ) {
                    $metadata['name']    = isset( $data['name'] ) ? $data['name'] : null;
                    $metadata['version'] = isset( $data['version'] ) ? $data['version'] : null;
                    if ( ! empty( $data['autoload']['psr-4'] ) ) {
                        $namespaces = array_keys( $data['autoload']['psr-4'] );
                        if ( ! empty( $namespaces ) ) {
                            $metadata['namespace'] = rtrim( (string) $namespaces[0], '\\' );
                        }
                    }
                }
            }
        }
        // Detect main plugin file (any file with plugin header under root).
        foreach ( glob( rtrim( $path, '/\\' ) . DIRECTORY_SEPARATOR . '*.php' ) as $file ) {
            $contents = file_get_contents( $file );
            if ( false !== $contents && preg_match( '/^[ \t\/*#@]*Plugin Name:\s*(.*)$/mi', $contents ) ) {
                $metadata['main'] = $file;
                // slug from file basename if not set
                $metadata['slug'] = basename( $file, '.php' );
                break;
            }
        }
        if ( empty( $metadata['slug'] ) ) {
            $metadata['slug'] = self::sanitize_slug( basename( rtrim( $path, '/\\' ) ) );
        }
        return $metadata;
    }

    protected static function detect_primary_namespace( $base_path ) {
        $composer = rtrim( $base_path, '/\\' ) . DIRECTORY_SEPARATOR . 'composer.json';
        if ( ! file_exists( $composer ) ) { return null; }
        $contents = file_get_contents( $composer );
        if ( false === $contents ) { return null; }
        $data = json_decode( $contents, true );
        if ( ! is_array( $data ) ) { return null; }
        $namespaces = array();
        if ( ! empty( $data['autoload']['psr-4'] ) && is_array( $data['autoload']['psr-4'] ) ) { $namespaces = array_keys( $data['autoload']['psr-4'] ); }
        elseif ( ! empty( $data['autoload-dev']['psr-4'] ) && is_array( $data['autoload-dev']['psr-4'] ) ) { $namespaces = array_keys( $data['autoload-dev']['psr-4'] ); }
        if ( empty( $namespaces ) ) { return null; }
        return rtrim( (string) $namespaces[0], '\\' );
    }

    protected static function detect_current_version( $base_path ) {
        $base_path = rtrim( $base_path, '/\\' );
        // 1) composer.json version
        $composer = $base_path . DIRECTORY_SEPARATOR . 'composer.json';
        if ( file_exists( $composer ) ) {
            $contents = file_get_contents( $composer );
            if ( false !== $contents ) {
                $data = json_decode( $contents, true );
                if ( is_array( $data ) && isset( $data['version'] ) && '' !== (string) $data['version'] ) {
                    return (string) $data['version'];
                }
            }
        }
        // 2) main plugin header Version:
        $meta = self::detect_project_metadata( $base_path );
        if ( ! empty( $meta['main'] ) && file_exists( $meta['main'] ) ) {
            $header = @file_get_contents( $meta['main'] );
            if ( false !== $header && preg_match( '/^[ \t\/*#@]*Version:\s*(.*)$/mi', $header, $m ) ) {
                $ver = trim( (string) $m[1] );
                if ( '' !== $ver ) { return $ver; }
            }
        }
        // 3) readme.txt Stable tag:
        $readme = $base_path . DIRECTORY_SEPARATOR . 'readme.txt';
        if ( file_exists( $readme ) ) {
            $content = file_get_contents( $readme );
            if ( false !== $content && preg_match( '/^[ \t\/*#@]*Stable tag:\s*(.*)$/mi', $content, $m ) ) {
                $ver = trim( (string) $m[1] );
                if ( '' !== $ver && strtolower( $ver ) !== 'trunk' ) { return $ver; }
            }
        }
        return null;
    }

    protected static function plugin_slug() { return self::sanitize_slug( basename( rtrim( self::base_path(), '/\\' ) ) ); }
    protected static function sanitize_slug( $value ) { $value = strtolower( (string) $value ); $value = preg_replace( '/[^a-z0-9\-]+/', '-', $value ); return trim( $value, '-' ); }

    /* ===== Translation helpers ===== */

    protected static function parse_options( array $args ) {
        $options = array();
        foreach ( $args as $arg ) { if ( 0 === strpos( $arg, '--wp-path=' ) ) { $options['wp-path'] = substr( $arg, 10 ); } }
        return $options;
    }

    protected static function refresh_translations( array $options = array() ) {
        Console::comment( '→ Refreshing translation template(s)' );
        $pot_path = self::generate_pot( $options );
        if ( ! $pot_path ) { Console::warning( 'Skipped translation template generation (see messages above).' ); return null; }
        self::update_po_files( dirname( $pot_path ), $pot_path );
        return $pot_path;
    }

    protected static function generate_pot( array $options = array() ) {
        $base_path     = self::base_path();
        $source_dir    = realpath( $base_path . 'src' );
        $languages_dir = $base_path . 'languages' . DIRECTORY_SEPARATOR;
        $domain        = App::instance()->textdomain();
        if ( false === $source_dir ) { Console::warning( 'Source directory not found for translation scan.' ); return null; }
        if ( ! self::ensure_directory( $languages_dir ) ) { return null; }
        $pot_path = $languages_dir . $domain . '.pot';
        $generator = new PotGenerator( $domain, $base_path );
        if ( $generator->generate( $source_dir, $pot_path ) ) { Console::comment( '   • Generated POT via built-in scanner' ); return $pot_path; }
        Console::warning( 'Built-in scanner could not generate translations; attempting WP-CLI fallback.' );
        if ( ! function_exists( 'exec' ) ) { Console::warning( 'PHP exec() is disabled; WP-CLI fallback cannot run.' ); return null; }
        $wp_binary = self::locate_binary( self::wp_binary_candidates(), array( 'wp', 'wp.bat' ) );
        if ( ! $wp_binary ) { Console::warning( 'WP-CLI binary not found. Install wp-cli or expose it via PATH to enable POT generation.' ); return null; }
        Console::comment( '   • Generating POT via ' . basename( $wp_binary ) );
        $wp_path = self::resolve_wp_path( $options );
        if ( $wp_path ) { Console::comment( '   • WordPress path: ' . self::relative_path( $wp_path ) ); }
        $arguments = array( 'i18n', 'make-pot', $source_dir, $pot_path, '--domain=' . $domain, '--exclude=vendor,node_modules,tests,temp,bin,languages,public_html', '--package-name=WPMoo', '--allow-root' );
        if ( $wp_path ) { $arguments[] = '--path=' . $wp_path; }
        list( $status, $output ) = self::execute_command( $wp_binary, $arguments );
        self::output_command_lines( $output );
        if ( 0 !== $status ) { Console::error( 'WP-CLI make-pot exited with a non-zero status.' ); self::maybe_hint_missing_i18n_package( $output ); return null; }
        return $pot_path;
    }

    protected static function update_po_files( $languages_dir, $pot_path ) {
        $glob_pattern = rtrim( $languages_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '*.po';
        $po_files     = glob( $glob_pattern );
        if ( empty( $po_files ) ) { Console::comment( '   • No .po files detected; skipping msgmerge step.' ); return; }
        $msgmerge = self::locate_binary( array(), array( 'msgmerge' ) );
        if ( ! $msgmerge ) { Console::warning( 'msgmerge binary not found. Install gettext utilities to auto-update .po files.' ); return; }
        foreach ( $po_files as $po_file ) {
            Console::comment( '   • Updating ' . self::relative_path( $po_file ) );
            list( $status, $output ) = self::execute_command( $msgmerge, array( '--update', '--backup=off', $po_file, $pot_path ) );
            self::output_command_lines( $output );
            if ( 0 !== $status ) { Console::warning( 'msgmerge failed for ' . basename( $po_file ) ); }
        }
    }

    protected static function locate_binary( array $relative_candidates, array $names ) {
        $base = self::base_path();
        foreach ( $relative_candidates as $candidate ) {
            $path = $base . ltrim( $candidate, '/\\' );
            if ( file_exists( $path ) && is_file( $path ) ) { $real = realpath( $path ); return $real ? $real : $path; }
        }
        if ( ! function_exists( 'exec' ) ) { return null; }
        foreach ( $names as $name ) { $located = self::search_system_path( $name ); if ( $located ) { return $located; } }
        return null;
    }

    protected static function search_system_path( $binary ) {
        if ( ! function_exists( 'exec' ) ) { return null; }
        $output = array(); $status = 1; $platform = PHP_OS_FAMILY;
        if ( 'Windows' === $platform ) { @exec( 'where ' . $binary . ' 2>&1', $output, $status ); }
        else { @exec( 'command -v ' . escapeshellarg( $binary ) . ' 2>&1', $output, $status ); }
        if ( 0 === $status && ! empty( $output[0] ) ) { return trim( $output[0] ); }
        return null;
    }

    protected static function execute_command( $binary, array $arguments, $cwd = null ) {
        $prefix = self::command_prefix( $binary );
        $cmd    = $prefix;
        foreach ( $arguments as $argument ) { $cmd .= ' ' . self::escape_argument( $argument ); }
        $cmd   .= ' 2>&1';
        $output = array();
        $status = 0;
        $previous_cwd = null;
        if ( null !== $cwd && '' !== $cwd && is_dir( $cwd ) ) {
            $previous_cwd = getcwd();
            if ( false === @chdir( $cwd ) ) { $previous_cwd = null; }
        }
        @exec( $cmd, $output, $status );
        if ( null !== $previous_cwd ) { @chdir( $previous_cwd ); }
        return array( $status, $output );
    }

    protected static function command_prefix( $binary ) {
        $resolved = realpath( $binary );
        $path     = $resolved ? $resolved : $binary;
        if ( preg_match( '/\.phar$/i', $path ) ) { return escapeshellcmd( PHP_BINARY ) . ' ' . escapeshellarg( $path ); }
        return escapeshellcmd( $path );
    }

    protected static function escape_argument( $argument ) {
        if ( '' === $argument ) { return "''"; }
        if ( '' === trim( $argument ) ) { return "''"; }
        return escapeshellarg( $argument );
    }

    protected static function output_command_lines( array $lines ) { foreach ( $lines as $line ) { Console::line( '      ' . $line ); } }

    /* ===== Environment/base path ===== */

    protected static function base_path() {
        $app        = App::instance();
        $candidates = array( $app->path( '' ), $app->path( '../' ), $app->path( '../../' ), $app->path( '../../../' ) );
        foreach ( $candidates as $candidate ) {
            $resolved = realpath( $candidate );
            if ( false === $resolved ) { continue; }
            if ( is_dir( $resolved . DIRECTORY_SEPARATOR . 'src' ) ) { return rtrim( $resolved, '/\\' ) . DIRECTORY_SEPARATOR; }
        }
        $raw  = $app->path( '../' );
        $real = realpath( $raw );
        $base = $real ? $real : $raw;
        return rtrim( $base, '/\\' ) . DIRECTORY_SEPARATOR;
    }

    protected static function framework_base_path() {
        if ( defined( 'WPMOO_PATH' ) ) { return rtrim( WPMOO_PATH, '/\\' ) . DIRECTORY_SEPARATOR; }
        return self::base_path();
    }

    /* ===== WP/CLI filter/action shims ===== */

    protected static function apply_filters_safe( $hook, $value, ...$args ) {
        if ( function_exists( 'apply_filters' ) ) { return apply_filters( $hook, $value, ...$args ); }
        return $value;
    }

    protected static function do_action_safe( $hook, ...$args ) {
        if ( function_exists( 'do_action' ) ) { do_action( $hook, ...$args ); }
    }

    protected static function resolve_wp_path( array $options ) {
        if ( isset( $options['wp-path'] ) ) {
            $explicit = rtrim( $options['wp-path'] );
            $resolved = realpath( $explicit );
            if ( false === $resolved ) { Console::warning( 'Provided --wp-path could not be resolved: ' . $explicit ); }
            elseif ( self::looks_like_wp_root( $resolved . DIRECTORY_SEPARATOR ) ) { return rtrim( $resolved, '/\\' ); }
            else { Console::warning( 'Provided --wp-path does not look like a WordPress root: ' . $explicit ); }
        }
        $path = self::base_path();
        for ( $depth = 0; $depth <= 8; $depth++ ) {
            if ( self::looks_like_wp_root( $path ) ) { return rtrim( realpath( $path ) ?: $path, '/\\' ); }
            foreach ( array( 'public_html', 'wordpress', 'wp' ) as $subdir ) {
                $candidate = $path . $subdir . DIRECTORY_SEPARATOR;
                if ( self::looks_like_wp_root( $candidate ) ) { return rtrim( realpath( $candidate ) ?: $candidate, '/\\' ); }
            }
            $parent = realpath( $path . '../' );
            if ( ! $parent || $parent === $path ) { break; }
            $path = rtrim( $parent, '/\\' ) . DIRECTORY_SEPARATOR;
        }
        return null;
    }

    protected static function looks_like_wp_root( $path ) {
        return is_dir( $path ) && file_exists( $path . 'wp-load.php' ) && file_exists( $path . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php' );
    }

    protected static function maybe_hint_missing_i18n_package( array $output ) {
        foreach ( $output as $line ) {
            if ( false !== stripos( $line, 'not a registered wp command' ) ) { Console::warning( 'Tip: Install the i18n commands via "wp package install wp-cli/i18n-command".' ); return; }
        }
    }

    protected static function composer_uses_path_repositories( $composer_json ) {
        if ( ! $composer_json || ! file_exists( $composer_json ) ) { return false; }
        $contents = file_get_contents( $composer_json );
        if ( false === $contents ) { return false; }
        $data = json_decode( $contents, true );
        if ( ! is_array( $data ) || empty( $data['repositories'] ) || ! is_array( $data['repositories'] ) ) { return false; }
        foreach ( $data['repositories'] as $repository ) {
            if ( is_array( $repository ) && isset( $repository['type'] ) && 'path' === $repository['type'] ) { return true; }
        }
        return false;
    }
}
