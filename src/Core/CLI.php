<?php
/**
 * Command line entry point for WPMoo.
 *
 * @package WPMoo\Core
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Core;

use WPMoo\Support\I18n\PotGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides routing for CLI commands.
 */
class CLI {

	/**
	 * Run the command router.
	 *
	 * @param array<int, mixed> $argv Command line arguments.
	 * @return void
	 */
	public static function run( $argv ) {
		$command = isset( $argv[1] ) ? $argv[1] : 'help';

		switch ( $command ) {
			case 'info':
				self::cmd_info();
				break;
			case 'update':
				self::cmd_update( array_slice( $argv, 2 ) );
				break;
			default:
				self::help();
				break;
		}
	}

	/**
	 * Output generic help text.
	 *
	 * @return void
	 */
	protected static function help() {
		Console::line();
		Console::comment( '🐮  WPMoo CLI' );
		Console::line( 'Usage:' );
		Console::line( '  php bin/moo info        Show framework info' );
		Console::line( '  php bin/moo update [--wp-path=<path>]   Run maintenance tasks (translations, etc.)' );
		Console::line( '  php bin/moo help        Show this help' );
		Console::line();
	}

	/**
	 * Display framework information.
	 *
	 * @return void
	 */
	protected static function cmd_info() {
		$php = PHP_VERSION;
		$wp  = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'n/a (CLI)';

		Console::info( 'WPMoo — WordPress Micro OOP Framework' );
		Console::comment( 'PHP: ' . $php );
		Console::comment( 'WP : ' . $wp );
		Console::line();
	}

	/**
	 * Run maintenance tasks.
	 *
	 * @param array<int, mixed> $args Optional arguments.
	 * @return void
	 */
	protected static function cmd_update( array $args = array() ) {
		$options = self::parse_options( $args );

		Console::line();
		Console::comment( 'Running WPMoo maintenance tasks…' );

		$pot_path = self::refresh_translations( $options );

		if ( $pot_path ) {
			Console::info( 'Translations refreshed at ' . self::relative_path( $pot_path ) );
		}

		Console::line();
	}

	/**
	 * Generate/merge translation templates.
	 *
	 * @return string|null Generated POT file path or null on failure.
	 */
	protected static function refresh_translations( array $options = array() ) {
		Console::comment( '→ Refreshing translation template(s)' );
		$pot_path = self::generate_pot( $options );

		if ( ! $pot_path ) {
			Console::warning( 'Skipped translation template generation (see messages above).' );

			return null;
		}

		self::update_po_files( dirname( $pot_path ), $pot_path );

		return $pot_path;
	}

	/**
	 * Generate the .pot template via WP-CLI, if available.
	 *
	 * @return string|null Absolute path to the generated POT file or null on failure.
	 */
	protected static function generate_pot( array $options = array() ) {
		$base_path     = self::base_path();
		$source_dir    = realpath( $base_path . 'src' );
		$languages_dir = $base_path . 'languages' . DIRECTORY_SEPARATOR;
		$domain        = App::instance()->textdomain();

		if ( false === $source_dir ) {
			Console::warning( 'Source directory not found for translation scan.' );

			return null;
		}

		if ( ! self::ensure_directory( $languages_dir ) ) {
			return null;
		}

		$pot_path = $languages_dir . $domain . '.pot';

		$generator = new PotGenerator( $domain, $base_path );

		if ( $generator->generate( $source_dir, $pot_path ) ) {
			Console::comment( '   • Generated POT via built-in scanner' );

			return $pot_path;
		}

		Console::warning( 'Built-in scanner could not generate translations; attempting WP-CLI fallback.' );

		if ( ! function_exists( 'exec' ) ) {
			Console::warning( 'PHP exec() is disabled; WP-CLI fallback cannot run.' );

			return null;
		}

		$wp_binary = self::locate_binary(
			self::wp_binary_candidates(),
			array( 'wp', 'wp.bat' )
		);

		if ( ! $wp_binary ) {
			Console::warning( 'WP-CLI binary not found. Install wp-cli or expose it via PATH to enable POT generation.' );

			return null;
		}

		Console::comment( '   • Generating POT via ' . basename( $wp_binary ) );

		$wp_path = self::resolve_wp_path( $options );

		if ( $wp_path ) {
			Console::comment( '   • WordPress path: ' . self::relative_path( $wp_path ) );
		}

		$arguments = array(
			'i18n',
			'make-pot',
			$source_dir,
			$pot_path,
			'--domain=' . $domain,
			'--exclude=vendor,node_modules,tests,temp,bin,languages,public_html',
			'--package-name=WPMoo',
			'--allow-root',
		);

		if ( $wp_path ) {
			$arguments[] = '--path=' . $wp_path;
		}

		list( $status, $output ) = self::execute_command( $wp_binary, $arguments );

		self::output_command_lines( $output );

		if ( 0 !== $status ) {
			Console::error( 'WP-CLI make-pot exited with a non-zero status.' );
			self::maybe_hint_missing_i18n_package( $output );

			return null;
		}

		return $pot_path;
	}

	/**
	 * Update existing .po files with the generated POT file.
	 *
	 * @param string $languages_dir Directory containing translation files.
	 * @param string $pot_path      Absolute path to the POT template.
	 * @return void
	 */
	protected static function update_po_files( $languages_dir, $pot_path ) {
		$glob_pattern = rtrim( $languages_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '*.po';
		$po_files     = glob( $glob_pattern );

		if ( empty( $po_files ) ) {
			Console::comment( '   • No .po files detected; skipping msgmerge step.' );

			return;
		}

		$msgmerge = self::locate_binary( array(), array( 'msgmerge' ) );

		if ( ! $msgmerge ) {
			Console::warning( 'msgmerge binary not found. Install gettext utilities to auto-update .po files.' );

			return;
		}

		foreach ( $po_files as $po_file ) {
			Console::comment( '   • Updating ' . self::relative_path( $po_file ) );

			list( $status, $output ) = self::execute_command(
				$msgmerge,
				array(
					'--update',
					'--backup=off',
					$po_file,
					$pot_path,
				)
			);

			self::output_command_lines( $output );

			if ( 0 !== $status ) {
				Console::warning( 'msgmerge failed for ' . basename( $po_file ) );
			}
		}
	}

	/**
	 * Locate an executable by scanning relative paths and the system PATH.
	 *
	 * @param array<int, string> $relative_candidates Relative paths from the framework base.
	 * @param array<int, string> $names               Binary names to probe via PATH.
	 * @return string|null Absolute path to the executable or null.
	 */
	protected static function locate_binary( array $relative_candidates, array $names ) {
		$base = self::base_path();

		foreach ( $relative_candidates as $candidate ) {
			$path = $base . ltrim( $candidate, '/\\' );

			if ( file_exists( $path ) && is_file( $path ) ) {
				$real = realpath( $path );

				return $real ? $real : $path;
			}
		}

		if ( ! function_exists( 'exec' ) ) {
			return null;
		}

		foreach ( $names as $name ) {
			$located = self::search_system_path( $name );

			if ( $located ) {
				return $located;
			}
		}

		return null;
	}

	/**
	 * Search for a binary on the current PATH.
	 *
	 * @param string $binary Binary name.
	 * @return string|null Absolute path or null.
	 */
	protected static function search_system_path( $binary ) {
		if ( ! function_exists( 'exec' ) ) {
			return null;
		}

		$output   = array();
		$status   = 1;
		$platform = PHP_OS_FAMILY;

		if ( 'Windows' === $platform ) {
			@exec( 'where ' . $binary . ' 2>&1', $output, $status ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		} else {
			@exec( 'command -v ' . escapeshellarg( $binary ) . ' 2>&1', $output, $status ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		}

		if ( 0 === $status && ! empty( $output[0] ) ) {
			return trim( $output[0] );
		}

		return null;
	}

	/**
	 * Execute a shell command and capture output.
	 *
	 * @param string               $binary    Executable path (or phar).
	 * @param array<int, string>   $arguments Command arguments.
	 * @return array{0:int,1:array<int,string>} Tuple of exit status and output lines.
	 */
	protected static function execute_command( $binary, array $arguments ) {
		$prefix = self::command_prefix( $binary );
		$cmd    = $prefix;

		foreach ( $arguments as $argument ) {
			$cmd .= ' ' . self::escape_argument( $argument );
		}

		$cmd   .= ' 2>&1';
		$output = array();
		$status = 0;

		exec( $cmd, $output, $status ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

		return array( $status, $output );
	}

	/**
	 * Build the command prefix for the executable.
	 *
	 * @param string $binary Binary path.
	 * @return string
	 */
	protected static function command_prefix( $binary ) {
		$resolved = realpath( $binary );
		$path     = $resolved ? $resolved : $binary;

		if ( preg_match( '/\\.phar$/i', $path ) ) {
			return escapeshellcmd( PHP_BINARY ) . ' ' . escapeshellarg( $path );
		}

		return escapeshellcmd( $path );
	}

	/**
	 * Escape a single argument for the shell.
	 *
	 * @param string $argument Argument string.
	 * @return string
	 */
	protected static function escape_argument( $argument ) {
		if ( '' === $argument ) {
			return "''";
		}
		if ( '' === trim( $argument ) ) {
			return "''";
		}

		return escapeshellarg( $argument );
	}

	/**
	 * Output command lines with indentation.
	 *
	 * @param array<int, string> $lines Lines to emit.
	 * @return void
	 */
	protected static function output_command_lines( array $lines ) {
		foreach ( $lines as $line ) {
			Console::line( '      ' . $line );
		}
	}

	/**
	 * Ensure a directory exists (creating it if needed).
	 *
	 * @param string $directory Directory path.
	 * @return bool True on success.
	 */
	protected static function ensure_directory( $directory ) {
		if ( is_dir( $directory ) ) {
			return true;
		}

		if ( @mkdir( $directory, 0755, true ) ) {
			return true;
		}

		Console::error( 'Unable to create directory: ' . self::relative_path( $directory ) );

		return false;
	}

	/**
	 * Determine the framework base path.
	 *
	 * @return string Base path with trailing directory separator.
	 */
	protected static function base_path() {
		$app      = App::instance();
		$candidates = array(
			$app->path( '' ),
			$app->path( '../' ),
			$app->path( '../../' ),
			$app->path( '../../../' ),
		);

		foreach ( $candidates as $candidate ) {
			$resolved = realpath( $candidate );

			if ( false === $resolved ) {
				continue;
			}

			if ( is_dir( $resolved . DIRECTORY_SEPARATOR . 'src' ) ) {
				return rtrim( $resolved, '/\\' ) . DIRECTORY_SEPARATOR;
			}
		}

		$raw  = $app->path( '../' );
		$real = realpath( $raw );
		$base = $real ? $real : $raw;

		return rtrim( $base, '/\\' ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Generate candidate paths for wp-cli binaries.
	 *
	 * @return array<int, string>
	 */
	protected static function wp_binary_candidates() {
		$candidates = array(
			'vendor/bin/wp',
			'vendor/bin/wp.bat',
			'bin/wp',
			'bin/wp.bat',
			'wp-cli.phar',
			'vendor/wp-cli.phar',
		);

		for ( $depth = 1; $depth <= 6; $depth++ ) {
			$prefix       = str_repeat( '../', $depth );
			$candidates[] = $prefix . 'vendor/bin/wp';
			$candidates[] = $prefix . 'vendor/bin/wp.bat';
			$candidates[] = $prefix . 'bin/wp';
			$candidates[] = $prefix . 'bin/wp.bat';
			$candidates[] = $prefix . 'wp-cli.phar';
			$candidates[] = $prefix . 'vendor/wp-cli.phar';
			$candidates[] = $prefix . 'bin/wp-cli.phar';
		}

		return array_unique( $candidates );
	}

	/**
	 * Convert CLI arguments into an associative options array.
	 *
	 * @param array<int, string> $args Raw argv tokens.
	 * @return array<string, string>
	 */
	protected static function parse_options( array $args ) {
		$options = array();

		foreach ( $args as $arg ) {
			if ( 0 === strpos( $arg, '--wp-path=' ) ) {
				$options['wp-path'] = substr( $arg, 10 );
			}
		}

		return $options;
	}

	/**
	 * Resolve the WordPress installation path if available.
	 *
	 * @param array<string, string> $options CLI options (expects optional wp-path).
	 * @return string|null Absolute WordPress path or null.
	 */
	protected static function resolve_wp_path( array $options ) {
		if ( isset( $options['wp-path'] ) ) {
			$explicit = rtrim( $options['wp-path'] );

			$resolved = realpath( $explicit );

			if ( false === $resolved ) {
				Console::warning( 'Provided --wp-path could not be resolved: ' . $explicit );
			} elseif ( self::looks_like_wp_root( $resolved . DIRECTORY_SEPARATOR ) ) {
				return rtrim( $resolved, '/\\' );
			} else {
				Console::warning( 'Provided --wp-path does not look like a WordPress root: ' . $explicit );
			}
		}

		$path = self::base_path();

		for ( $depth = 0; $depth <= 8; $depth++ ) {
			if ( self::looks_like_wp_root( $path ) ) {
				return rtrim( realpath( $path ) ?: $path, '/\\' );
			}

			foreach ( array( 'public_html', 'wordpress', 'wp' ) as $subdir ) {
				$candidate = $path . $subdir . DIRECTORY_SEPARATOR;

				if ( self::looks_like_wp_root( $candidate ) ) {
					return rtrim( realpath( $candidate ) ?: $candidate, '/\\' );
				}
			}

			$parent = realpath( $path . '../' );

			if ( ! $parent || $parent === $path ) {
				break;
			}

			$path = rtrim( $parent, '/\\' ) . DIRECTORY_SEPARATOR;
		}

		return null;
	}

	/**
	 * Determine whether a directory looks like a WordPress root.
	 *
	 * @param string $path Directory path (with trailing separator).
	 * @return bool
	 */
	protected static function looks_like_wp_root( $path ) {
		return is_dir( $path )
			&& file_exists( $path . 'wp-load.php' )
			&& file_exists( $path . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php' );
	}

	/**
	 * Provide guidance when the WP-CLI i18n package is missing.
	 *
	 * @param array<int, string> $output Command output.
	 * @return void
	 */
	protected static function maybe_hint_missing_i18n_package( array $output ) {
		foreach ( $output as $line ) {
			if ( false !== stripos( $line, 'not a registered wp command' ) ) {
				Console::warning( 'Tip: Install the i18n commands via "wp package install wp-cli/i18n-command".' );
				return;
			}
		}
	}

	/**
	 * Convert an absolute path to a path relative to the base.
	 *
	 * @param string $path Absolute path.
	 * @return string Relative path if possible, otherwise original.
	 */
	protected static function relative_path( $path ) {
		$base = self::base_path();

		$normalized_base = str_replace( '\\', '/', $base );
		$normalized_path = str_replace( '\\', '/', $path );

		if ( 0 === strpos( $normalized_path, $normalized_base ) ) {
			return ltrim( substr( $normalized_path, strlen( $normalized_base ) ), '/' );
		}

		return $path;
	}
}
