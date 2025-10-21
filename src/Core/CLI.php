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
			case 'version':
				self::cmd_version( array_slice( $argv, 2 ) );
				break;
			case 'dist':
				self::cmd_dist( array_slice( $argv, 2 ) );
				break;
			case 'build':
				self::cmd_build( array_slice( $argv, 2 ) );
				break;
			case 'deploy':
				self::cmd_deploy( array_slice( $argv, 2 ) );
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
		Console::line( '  php bin/moo version [--patch|--minor|--major|<version>] [--dry-run] [--yes]' );
		Console::line( '                       Bump framework version across manifests' );
		Console::line( '  php bin/moo build [--pm=<manager>] [--install|--no-install] [--script=<name>]' );
		Console::line( '                       Build front-end assets using the detected package manager' );
		Console::line( '  php bin/moo deploy [<path>] [--pm=<manager>] [--no-build] [--zip] [--script=<name>]' );
	Console::line( '                       Create a deployable copy (optionally zipped) with cleaned assets' );
	Console::line( '  php bin/moo dist [--label=<slug>] [--output=<dir>] [--source=<path>] [--version=<x.y.z>] [--keep]' );
	Console::line( '                       Produce a reusable framework or project distribution archive' );
		Console::line( '  php bin/moo help        Show this help' );
		Console::line();
	}

	/**
	 * Produce a reusable distribution archive of the framework.
	 *
	 * @param array<int, mixed> $args CLI arguments.
	 * @return void
	 * @since 0.4.2
	 */
	protected static function cmd_dist( array $args = array() ) {
		$options = self::parse_dist_options( $args );

		$source_root = $options['source']
			? self::normalize_absolute_path( $options['source'] )
			: self::default_dist_source();

		if ( ! $source_root || ! is_dir( $source_root ) ) {
			Console::error( 'Unable to resolve source directory for distribution.' );
			return;
		}

	$base_path    = self::framework_base_path();
	$is_framework = self::paths_equal( $source_root, rtrim( $base_path, '/\\' ) );

	$metadata = $is_framework ? array() : self::detect_project_metadata( $source_root );

		$version = $options['version']
			? self::sanitize_version_input( $options['version'] )
			: ( $is_framework
				? self::detect_current_version( $base_path )
				: ( $metadata['version'] ?? self::detect_current_version( $source_root ) ) );

		if ( ! $version ) {
			$version = '0.0.0';
		}

		if ( $options['label'] ) {
			$slug = self::sanitize_slug( $options['label'] );
		} elseif ( $is_framework ) {
			$slug = self::plugin_slug();
		} elseif ( ! empty( $metadata['slug'] ) ) {
			$slug = $metadata['slug'];
		} else {
			$slug = self::sanitize_slug( basename( $source_root ) );
		}

		if ( '' === $slug ) {
			$slug = 'package';
		}

		$label = $slug . '-' . $version;

		$dist_root = $options['output']
			? self::normalize_absolute_path( $options['output'] )
			: dirname( $source_root ) . DIRECTORY_SEPARATOR . 'dist';

		if ( ! $dist_root ) {
			Console::error( 'Failed to resolve distribution output directory.' );
			return;
		}

		if ( ! self::ensure_directory( rtrim( $dist_root, '/\\' ) . DIRECTORY_SEPARATOR ) ) {
			Console::error( 'Unable to create distribution output directory.' );
			return;
		}

		$temp_dir = self::create_temp_directory( $slug . '-dist-' );

		if ( ! $temp_dir ) {
			Console::error( 'Unable to create temporary directory for distribution build.' );
			return;
		}

		$target_root = $temp_dir . DIRECTORY_SEPARATOR . $label;

		if ( ! @mkdir( $target_root, 0755, true ) ) {
			Console::error( 'Unable to prepare working directory for distribution.' );
			self::delete_directory( $temp_dir );
			return;
		}

		Console::line();
		Console::comment( 'Preparing distribution: ' . $label );

	if ( $is_framework ) {
		foreach ( self::default_dist_includes( $source_root ) as $entry ) {
			$source = $source_root . DIRECTORY_SEPARATOR . $entry;
			$target = $target_root . DIRECTORY_SEPARATOR . $entry;

			if ( self::copy_within_dist( $source, $target ) && 'vendor' === $entry ) {
				self::prune_vendor_tree( $target );
			}

			if ( ! file_exists( $target ) ) {
				Console::warning( 'Failed to include ' . $entry . ' in distribution.' );
			}
		}

		self::ensure_minified_assets( $target_root . DIRECTORY_SEPARATOR . 'assets' );
		self::prune_assets_tree( $target_root . DIRECTORY_SEPARATOR . 'assets' );

		$composer_binary = self::locate_composer_binary( $target_root );
			$composer_status = false;

			if ( $composer_binary ) {
				Console::comment( '→ Installing production dependencies (--no-dev)' );
				self::delete_directory( $target_root . DIRECTORY_SEPARATOR . 'vendor' );
				list( $status, $output ) = self::execute_command(
					$composer_binary,
					array(
						'install',
						'--no-dev',
						'--prefer-dist',
						'--no-interaction',
						'--no-progress',
						'--optimize-autoloader',
					),
					$target_root
				);
				self::output_command_lines( $output );

				if ( 0 === $status ) {
					$composer_status = true;
				} else {
					Console::warning( 'Composer install failed (exit code ' . $status . '). Reinstating bundled vendor directory.' );
					self::copy_within_dist(
						$source_root . DIRECTORY_SEPARATOR . 'vendor',
						$target_root . DIRECTORY_SEPARATOR . 'vendor'
					);
				}
			} else {
				Console::comment( '→ Composer binary not found; reusing existing vendor directory.' );
			}

			self::remove_if_exists( $target_root . DIRECTORY_SEPARATOR . 'composer.json' );
			self::remove_if_exists( $target_root . DIRECTORY_SEPARATOR . 'composer.lock' );
			self::remove_if_exists( $target_root . DIRECTORY_SEPARATOR . 'package.json' );
			self::remove_if_exists( $target_root . DIRECTORY_SEPARATOR . 'package-lock.json' );
			self::remove_if_exists( $target_root . DIRECTORY_SEPARATOR . 'pnpm-lock.yaml' );
			self::remove_if_exists( $target_root . DIRECTORY_SEPARATOR . 'yarn.lock' );
			self::delete_directory( $target_root . DIRECTORY_SEPARATOR . 'bin' );
			self::delete_directory( $target_root . DIRECTORY_SEPARATOR . 'node_modules' );
			self::prune_vendor_tree( $target_root . DIRECTORY_SEPARATOR . 'vendor' );
		} else {
			$exclusions = self::default_deploy_exclusions();

			if ( ! self::copy_tree( $source_root, $target_root, $exclusions ) ) {
				Console::error( 'Failed to copy project files into working directory.' );
				self::delete_directory( $temp_dir );
				return;
			}

			self::post_process_deploy( $target_root, array() );
			self::prune_vendor_tree( $target_root . DIRECTORY_SEPARATOR . 'vendor' );
		}

		$archive_path = rtrim( $dist_root, '/\\' ) . DIRECTORY_SEPARATOR . $label . '.zip';

		if ( ! self::create_zip_archive( $target_root, $archive_path ) ) {
			Console::error( 'Failed to create distribution archive.' );
			self::delete_directory( $temp_dir );
			return;
		}

		Console::info( 'Distribution archive created: ' . self::relative_path( $archive_path ) );

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

		if ( ! $options['keep'] ) {
			self::delete_directory( $temp_dir );
		} else {
			Console::comment( 'Working directory preserved at ' . self::relative_path( $temp_dir ) );
		}

		Console::line();
	}

	/**
	 * Execute the asset build pipeline.
	 *
	 * @param array<string, mixed> $options Build options.
	 * @return bool True on success.
	 * @since 0.4.0
	 */
	protected static function perform_build( array $options = array() ) {
		$defaults = array(
			'pm'             => null,
			'script'         => 'build',
			'force-install'  => false,
			'skip-install'   => false,
			'allow-missing'  => false,
		);

		$options = array_merge( $defaults, $options );
		$base    = self::base_path();
		$pkg     = $base . 'package.json';

		if ( ! file_exists( $pkg ) ) {
			if ( $options['allow-missing'] ) {
				Console::comment( '→ No package.json detected; skipping asset build.' );

				return true;
			}

			Console::error( 'No package.json detected; cannot run build.' );

			return false;
		}

		$manager = self::detect_package_manager( $base, $options['pm'] );

		if ( ! $manager ) {
			Console::error( 'Could not determine an available package manager. Install npm, yarn, pnpm, or bun (or pass --pm=<manager>).' );

			return false;
		}

		$name   = $manager['name'];
		$binary = $manager['binary'];

		Console::comment( '→ Using ' . $name . ' (' . $binary . ')' );

		$should_install = (bool) $options['force-install'];

		if ( ! $should_install && ! $options['skip-install'] && ! is_dir( $base . 'node_modules' ) ) {
			$should_install = true;
		}

		if ( $should_install ) {
			Console::comment( '   • Installing dependencies' );
			list( $install_status, $install_output ) = self::execute_command(
				$binary,
				self::install_arguments( $name ),
				$base
			);
			self::output_command_lines( $install_output );

			if ( 0 !== $install_status ) {
				Console::error( 'Dependency installation failed with status ' . $install_status . '.' );

				return false;
			}
		} elseif ( ! is_dir( $base . 'node_modules' ) ) {
			Console::warning( '   • node_modules missing; continuing without installation (build may fail).' );
		}

		Console::comment( '   • Running ' . $name . ' ' . self::format_run_command( $name, $options['script'] ) );
		list( $build_status, $build_output ) = self::execute_command(
			$binary,
			self::build_arguments( $name, $options['script'] ),
			$base
		);
		self::output_command_lines( $build_output );

		if ( 0 !== $build_status ) {
			Console::error( 'Build script exited with status ' . $build_status . '.' );

			return false;
		}

		Console::info( '→ Asset build completed.' );

		self::do_action_safe(
			'wpmoo_cli_build_completed',
			$name,
			$base,
			$options
		);

		return true;
	}

	/**
	 * Parse build command options.
	 *
	 * @param array<int, mixed> $args CLI arguments.
	 * @return array<string, mixed>
	 * @since 0.4.0
	 */
	protected static function parse_build_options( array $args ) {
		$options = array(
			'pm'            => null,
			'script'        => 'build',
			'force-install' => false,
			'skip-install'  => false,
		);

		foreach ( $args as $arg ) {
			if ( ! is_string( $arg ) || '' === $arg ) {
				continue;
			}

			if ( 0 === strpos( $arg, '--pm=' ) ) {
				$options['pm'] = substr( $arg, 5 );
			} elseif ( 0 === strpos( $arg, '--pkgm=' ) ) {
				$options['pm'] = substr( $arg, 7 );
			} elseif ( '--install' === $arg || '--force-install' === $arg ) {
				$options['force-install'] = true;
			} elseif ( '--no-install' === $arg ) {
				$options['skip-install'] = true;
			} elseif ( 0 === strpos( $arg, '--script=' ) ) {
				$script = substr( $arg, 9 );

				if ( '' !== $script ) {
					$options['script'] = $script;
				}
			}
		}

		return $options;
	}

	/**
	 * Parse deploy command options.
	 *
	 * @param array<int, mixed> $args CLI arguments.
	 * @return array<string, mixed>
	 * @since 0.4.0
	 */
	protected static function parse_deploy_options( array $args ) {
		$options = self::parse_build_options( $args );

		$options['target']      = null;
		$options['zip']         = false;
		$options['zip-path']    = null;
		$options['no-build']    = false;
		$options['work-path']   = null;

		foreach ( $args as $arg ) {
			if ( ! is_string( $arg ) || '' === $arg ) {
				continue;
			}

			if ( '--no-build' === $arg ) {
				$options['no-build'] = true;
			} elseif ( '--zip' === $arg || '--create-zip' === $arg ) {
				$options['zip'] = true;
			} elseif ( 0 === strpos( $arg, '--zip=' ) ) {
				$options['zip']      = true;
				$zip_value           = substr( $arg, 6 );
				$options['zip-path'] = '' !== $zip_value ? $zip_value : null;
			} elseif ( 0 === strpos( $arg, '--pm=' ) || 0 === strpos( $arg, '--pkgm=' ) || 0 === strpos( $arg, '--script=' ) ) {
				continue;
			} elseif ( '--install' === $arg || '--force-install' === $arg || '--no-install' === $arg ) {
				continue;
			} elseif ( '-' !== substr( $arg, 0, 1 ) && null === $options['target'] ) {
				$options['target'] = $arg;
			}
		}

		if ( $options['target'] && self::ends_with_zip( $options['target'] ) ) {
			$options['zip'] = true;

			if ( ! $options['zip-path'] ) {
				$options['zip-path'] = $options['target'];
			}
		}

		return $options;
	}

	/**
	 * Parse CLI arguments for the version command.
	 *
	 * @param array<int, mixed> $args Raw CLI args.
	 * @return array<string, mixed>
	 * @since 0.4.1
	 */
	protected static function parse_version_arguments( array $args ) {
		$options = array(
			'bump'         => null,
			'explicit'     => null,
			'dry-run'      => false,
			'assume-yes'   => false,
			'pre-release'  => null,
		);

		$map = array(
			'--major' => 'major',
			'--minor' => 'minor',
			'--patch' => 'patch',
		);

		$count = count( $args );

		for ( $index = 0; $index < $count; $index++ ) {
			$arg = $args[ $index ];

			if ( ! is_string( $arg ) ) {
				continue;
			}

			$arg = trim( $arg );

			if ( '' === $arg ) {
				continue;
			}

			if ( isset( $map[ $arg ] ) ) {
				$options['bump'] = $map[ $arg ];
				continue;
			}

			if ( '--dry-run' === $arg ) {
				$options['dry-run'] = true;
				continue;
			}

			if ( '--yes' === $arg || '--force' === $arg ) {
				$options['assume-yes'] = true;
				continue;
			}

			if ( 0 === strpos( $arg, '--pre=' ) ) {
				$options['pre-release'] = substr( $arg, 6 );
				continue;
			}

			if ( '--pre' === $arg ) {
				if ( isset( $args[ $index + 1 ] ) ) {
					$options['pre-release'] = (string) $args[ $index + 1 ];
					++$index;
				}
				continue;
			}

			if ( 0 === strpos( $arg, '--' ) ) {
				continue;
			}

			$options['explicit'] = $arg;
		}

		return $options;
	}

	/**
	 * Parse CLI arguments for the dist command.
	 *
	 * @param array<int, mixed> $args Raw CLI args.
	 * @return array<string, mixed>
	 * @since 0.4.2
	 */
	protected static function parse_dist_options( array $args ) {
		$options = array(
			'label'   => null,
			'output'  => null,
			'source'  => null,
			'version' => null,
			'keep'    => false,
		);

		$count = count( $args );

		for ( $index = 0; $index < $count; $index++ ) {
			$raw = $args[ $index ];

			if ( ! is_string( $raw ) ) {
				continue;
			}

			$arg = trim( $raw );

			if ( '' === $arg ) {
				continue;
			}

			if ( 0 === strpos( $arg, '--label=' ) ) {
				$options['label'] = substr( $arg, 8 );
				continue;
			}

			if ( '--label' === $arg && isset( $args[ $index + 1 ] ) ) {
				$options['label'] = trim( (string) $args[ ++$index ] );
				continue;
			}

			if ( 0 === strpos( $arg, '--output=' ) ) {
				$options['output'] = substr( $arg, 9 );
				continue;
			}

			if ( '--output' === $arg && isset( $args[ $index + 1 ] ) ) {
				$options['output'] = trim( (string) $args[ ++$index ] );
				continue;
			}

		if ( '--keep' === $arg ) {
			$options['keep'] = true;
			continue;
		}

		if ( 0 === strpos( $arg, '--source=' ) ) {
			$options['source'] = substr( $arg, 9 );
			continue;
		}

		if ( '--source' === $arg && isset( $args[ $index + 1 ] ) ) {
			$options['source'] = trim( (string) $args[ ++$index ] );
			continue;
		}

		if ( 0 === strpos( $arg, '--version=' ) ) {
			$options['version'] = substr( $arg, 10 );
			continue;
		}

		if ( '--version' === $arg && isset( $args[ $index + 1 ] ) ) {
			$options['version'] = trim( (string) $args[ ++$index ] );
			continue;
		}
	}

	return $options;
}

	/**
	 * Normalize a version string input.
	 *
	 * @param string $value Raw version value.
	 * @return string
	 * @since 0.4.1
	 */
	protected static function sanitize_version_input( $value ) {
		$value = trim( (string) $value );
		$value = preg_replace( '/^v/i', '', $value );

		return $value;
	}

	/**
	 * Validate semantic version format.
	 *
	 * @param string $value Version string.
	 * @return bool
	 * @since 0.4.1
	 */
	protected static function is_valid_semver( $value ) {
		if ( '' === $value ) {
			return false;
		}

		return (bool) preg_match(
			'/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/',
			$value
		);
	}

	/**
	 * Increment the current version according to semantic versioning rules.
	 *
	 * @param string      $current     Current semantic version.
	 * @param string      $type        Increment type (major|minor|patch).
	 * @param string|null $pre_release Optional pre-release label.
	 * @return string|null
	 * @since 0.4.1
	 */
	protected static function bump_semver( $current, $type, $pre_release = null ) {
		if ( ! preg_match( '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-.+)?$/', $current, $matches ) ) {
			return null;
		}

		$major = (int) $matches[1];
		$minor = (int) $matches[2];
		$patch = (int) $matches[3];

		switch ( $type ) {
			case 'major':
				++$major;
				$minor = 0;
				$patch = 0;
				break;
			case 'minor':
				++$minor;
				$patch = 0;
				break;
			case 'patch':
			default:
				++$patch;
				break;
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

	/**
	 * Update version references across framework files.
	 *
	 * @param string $base_path       Framework base path.
	 * @param string $current_version Current version to replace.
	 * @param string $new_version     New version to set.
	 * @param bool   $dry_run         If true, do not write changes.
	 * @return array<int, string> List of files touched.
	 * @since 0.4.1
	 */
	protected static function update_version_files( $base_path, $current_version, $new_version, $dry_run = false ) {
		$updated = array();

		$files = array(
			$base_path . 'composer.json'            => 'json',
			$base_path . 'package.json'             => 'json',
			$base_path . 'wpmoo.php'                => 'bootstrap',
			$base_path . 'src/Options/Page.php'     => 'php',
			$base_path . 'src/Metabox/Metabox.php'  => 'php',
		);

		foreach ( $files as $path => $type ) {
			if ( ! file_exists( $path ) ) {
				continue;
			}

			$rel = $path;

			if ( 'json' === $type ) {
				if ( $dry_run ) {
					$updated[] = $path;
					continue;
				}

				$contents = file_get_contents( $path );

				if ( false === $contents ) {
					Console::warning( 'Failed to read ' . self::relative_path( $path ) );
					continue;
				}

				$data = json_decode( $contents, true );

				if ( ! is_array( $data ) ) {
					Console::warning( 'Invalid JSON in ' . self::relative_path( $path ) );
					continue;
				}

				$data['version'] = $new_version;

				if ( ! self::write_json_file( $path, $data ) ) {
					Console::warning( 'Could not write updated JSON to ' . self::relative_path( $path ) );
					continue;
				}

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

		return $updated;
	}

	/**
	 * Perform additional cleanup/optimisation on the deployed package.
	 *
	 * @param string               $working_dir Deployment directory.
	 * @param array<string, mixed> $options     Deploy options.
	 * @return void
	 * @since 0.4.1
	 */
	protected static function post_process_deploy( $working_dir, array $options ) {
		$moo_path = $working_dir . DIRECTORY_SEPARATOR . 'moo';

		if ( file_exists( $moo_path ) ) {
			if ( @unlink( $moo_path ) ) {
				Console::comment( '→ Removed CLI alias (moo)' );
			}
		}

		$composer_success = self::optimise_composer_dependencies( $working_dir );

		if ( ! $composer_success ) {
			Console::comment( '→ Retaining vendor directory (composer optimisation skipped).' );
		}

		$keep_composer_json = self::apply_filters_safe(
			'wpmoo_cli_deploy_keep_composer_json',
			false,
			$working_dir,
			$options,
			$composer_success
		);

		if ( ! $keep_composer_json ) {
			self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'composer.json' );
		}

	$keep_composer_lock = self::apply_filters_safe(
		'wpmoo_cli_deploy_keep_composer_lock',
		false,
		$working_dir,
		$options,
		$composer_success
	);

	if ( ! $keep_composer_lock ) {
		self::remove_if_exists( $working_dir . DIRECTORY_SEPARATOR . 'composer.lock' );
	}

	$keep_package_manifest = self::apply_filters_safe(
		'wpmoo_cli_deploy_keep_package_json',
		false,
		$working_dir,
		$options,
		$composer_success
	);

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

	/**
	 * Replace embedded framework copy with a runtime-optimised version.
	 *
	 * @param string $working_dir Deployment directory.
	 * @return void
	 * @since 0.4.2
	 */
	protected static function refresh_embedded_framework( $working_dir ) {
		$framework_path = $working_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'wpmoo-org';

		if ( ! is_dir( $framework_path ) ) {
			return;
		}

		$framework_path .= DIRECTORY_SEPARATOR . 'wpmoo';

		if ( is_link( $framework_path ) || is_file( $framework_path ) ) {
			@unlink( $framework_path );
		} else {
			self::delete_directory( $framework_path );
		}

		if ( ! is_dir( $framework_path ) && ! @mkdir( $framework_path, 0755, true ) ) {
			Console::warning( 'Unable to rebuild embedded WPMoo runtime directory.' );
			return;
		}

	$source_root = rtrim( self::framework_base_path(), '/\\' );

	self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'wpmoo.php', $framework_path . DIRECTORY_SEPARATOR . 'wpmoo.php' );
	self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'src', $framework_path . DIRECTORY_SEPARATOR . 'src' );
	self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'languages', $framework_path . DIRECTORY_SEPARATOR . 'languages' );
	self::copy_within_dist( $source_root . DIRECTORY_SEPARATOR . 'assets', $framework_path . DIRECTORY_SEPARATOR . 'assets' );
	self::ensure_minified_assets( $framework_path . DIRECTORY_SEPARATOR . 'assets' );
	self::prune_assets_tree( $framework_path . DIRECTORY_SEPARATOR . 'assets' );

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

	/**
	 * Write JSON data with formatting.
	 *
	 * @param string               $path File path.
	 * @param array<string, mixed> $data Data to write.
	 * @return bool
	 * @since 0.4.1
	 */
	protected static function write_json_file( $path, array $data ) {
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			return false;
		}

		$json .= PHP_EOL;

		return false !== file_put_contents( $path, $json );
	}

	/**
	 * Replace a quoted version literal in a PHP file.
	 *
	 * @param string $path             File path.
	 * @param string $current_version  Version to replace.
	 * @param string $new_version      New version string.
	 * @param bool   $dry_run          If true, do not write changes.
	 * @return bool True if replacement occurred.
	 * @since 0.4.1
	 */
	protected static function replace_version_literal( $path, $current_version, $new_version, $dry_run = false ) {
		$contents = file_get_contents( $path );

		if ( false === $contents ) {
			Console::warning( 'Failed to read ' . self::relative_path( $path ) );
			return false;
		}

		$count   = 0;
		$updated = str_replace( "'" . $current_version . "'", "'" . $new_version . "'", $contents, $count );

		if ( 0 === $count ) {
			$updated = str_replace( '"' . $current_version . '"', '"' . $new_version . '"', $contents, $count );
		}

		if ( 0 === $count ) {
			$updated = preg_replace(
				"/(['\"])(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)(?:-[^'\"]+)?\\1/",
				'$1' . $new_version . '$1',
				$contents,
				1,
				$count
			);
		}

		if ( 0 === $count || null === $updated ) {
			Console::warning( 'No version literal updated in ' . self::relative_path( $path ) );
			return false;
		}

		if ( $dry_run ) {
			return true;
		}

		return false !== file_put_contents( $path, $updated );
	}

	/**
	 * Update version information within the bootstrap file.
	 *
	 * @param string $path             Bootstrap file path.
	 * @param string $current_version  Current version string.
	 * @param string $new_version      Desired version string.
	 * @param bool   $dry_run          Whether to skip writing changes.
	 * @return bool True when a change was performed.
	 * @since 0.4.1
	 */
	protected static function update_bootstrap_version( $path, $current_version, $new_version, $dry_run = false ) {
		$contents = file_get_contents( $path );

		if ( false === $contents ) {
			Console::warning( 'Failed to read ' . self::relative_path( $path ) );
			return false;
		}

		$updated = $contents;
		$count   = 0;

		$updated = preg_replace(
			'/(Version:\s*)' . preg_quote( $current_version, '/' ) . '/',
			'$1' . $new_version,
			$updated,
			1,
			$count
		);

		$constant_count = 0;

		$updated = preg_replace(
			"/define\\(\\s*'WPMOO_VERSION'\\s*,\\s*'[^']+'\\s*\\)/",
			"define( 'WPMOO_VERSION', '" . $new_version . "' )",
			$updated,
			1,
			$constant_count
		);

		if ( 0 === $count && 0 === $constant_count ) {
			Console::warning( 'No bootstrap version markers updated in ' . self::relative_path( $path ) );
			return false;
		}

		if ( $dry_run ) {
			return true;
		}

		return false !== file_put_contents( $path, $updated );
	}

	/**
	 * Detect the current framework version from composer.json.
	 *
	 * @param string $base_path Base directory.
	 * @return string|null
	 * @since 0.4.1
	 */
	protected static function detect_current_version( $base_path ) {
		$composer = $base_path . 'composer.json';

		if ( ! file_exists( $composer ) ) {
			return null;
		}

		$contents = file_get_contents( $composer );

		if ( false === $contents ) {
			return null;
		}

		$data = json_decode( $contents, true );

		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			return null;
		}

		return (string) $data['version'];
	}

	/**
	 * Detect available package manager and resolve its binary.
	 *
	 * @param string      $base_path Base directory.
	 * @param string|null $hint      Preferred manager.
	 * @return array<string, string>|null
	 * @since 0.4.0
	 */
	protected static function detect_package_manager( $base_path, $hint = null ) {
		$candidates = self::package_manager_candidates( $base_path, $hint );

		foreach ( $candidates as $candidate ) {
			$binary = self::locate_binary(
				array(),
				self::package_manager_binary_names( $candidate['name'] )
			);

			if ( $binary ) {
				return array(
					'name'   => $candidate['name'],
					'binary' => $binary,
				);
			}

			if ( $hint && strtolower( $hint ) === $candidate['name'] ) {
				Console::warning( 'Specified package manager "' . $hint . '" was not found on PATH.' );
			}
		}

		return null;
	}

	/**
	 * Build a prioritized list of package manager candidates.
	 *
	 * @param string      $base_path Base directory.
	 * @param string|null $hint      Preferred manager.
	 * @return array<int, array<string, string>>
	 * @since 0.4.0
	 */
	protected static function package_manager_candidates( $base_path, $hint = null ) {
		$base_path = rtrim( $base_path, '/\\' ) . DIRECTORY_SEPARATOR;

		$order = array(
			'yarn' => array( 'locks' => array( 'yarn.lock' ) ),
			'pnpm' => array( 'locks' => array( 'pnpm-lock.yaml' ) ),
			'bun'  => array( 'locks' => array( 'bun.lockb' ) ),
			'npm'  => array( 'locks' => array( 'package-lock.json', 'npm-shrinkwrap.json' ) ),
		);

		$candidates = array();

		if ( $hint ) {
			$key = strtolower( $hint );

			if ( isset( $order[ $key ] ) ) {
				$candidates[] = array( 'name' => $key );
			}
		}

		foreach ( $order as $name => $metadata ) {
			foreach ( $metadata['locks'] as $lock ) {
				if ( file_exists( $base_path . $lock ) ) {
					$candidates[] = array( 'name' => $name );
					break;
				}
			}
		}

		foreach ( $order as $name => $metadata ) {
			$already = false;

			foreach ( $candidates as $candidate ) {
				if ( $candidate['name'] === $name ) {
					$already = true;
					break;
				}
			}

			if ( ! $already ) {
				$candidates[] = array( 'name' => $name );
			}
		}

		return $candidates;
	}

	/**
	 * Binary name permutations for a package manager.
	 *
	 * @param string $manager Manager name.
	 * @return array<int, string>
	 * @since 0.4.0
	 */
	protected static function package_manager_binary_names( $manager ) {
		$manager = strtolower( $manager );

		return array(
			$manager,
			$manager . '.cmd',
			$manager . '.exe',
		);
	}

	/**
	 * Build the install command arguments for a package manager.
	 *
	 * @param string $manager Manager name.
	 * @return array<int, string>
	 * @since 0.4.0
	 */
	protected static function install_arguments( $manager ) {
		$manager = strtolower( $manager );

		if ( 'yarn' === $manager ) {
			return array( 'install' );
		}

		return array( 'install' );
	}

	/**
	 * Build the script run arguments for a package manager.
	 *
	 * @param string $manager Manager name.
	 * @param string $script  Script name.
	 * @return array<int, string>
	 * @since 0.4.0
	 */
	protected static function build_arguments( $manager, $script ) {
		$manager = strtolower( $manager );

		if ( 'yarn' === $manager ) {
			return array( 'run', $script );
		}

		return array( 'run', $script );
	}

	/**
	 * Format the run command for display.
	 *
	 * @param string $manager Manager name.
	 * @param string $script  Script name.
	 * @return string
	 * @since 0.4.0
	 */
	protected static function format_run_command( $manager, $script ) {
		$manager = strtolower( $manager );

		if ( 'yarn' === $manager ) {
			return 'run ' . $script;
		}

		return 'run ' . $script;
	}

	/**
	 * Default deployment directory.
	 *
	 * @return string
	 * @since 0.4.0
	 */
	protected static function default_deploy_directory() {
		$base   = rtrim( self::base_path(), '/\\' );
		$parent = dirname( $base );
		$dist   = $parent . DIRECTORY_SEPARATOR . 'dist';

		self::ensure_directory( $dist . DIRECTORY_SEPARATOR );

		return $dist . DIRECTORY_SEPARATOR . self::plugin_slug();
	}

	/**
	 * Default deployment zip path.
	 *
	 * @param string $target Target directory.
	 * @param string $slug   Plugin slug.
	 * @return string
	 * @since 0.4.0
	 */
	protected static function default_deploy_zip_path( $target, $slug ) {
		$target = rtrim( $target, '/\\' );

		if ( '' === $target ) {
			$target = self::base_path();
		}

		if ( self::ends_with_zip( $target ) ) {
			return $target;
		}

		if ( basename( $target ) === $slug ) {
			$dir = dirname( $target );
			self::ensure_directory( $dir . DIRECTORY_SEPARATOR );

			return $dir . DIRECTORY_SEPARATOR . $slug . '.zip';
		}

		if ( is_dir( $target ) || ! file_exists( $target ) ) {
			self::ensure_directory( $target . DIRECTORY_SEPARATOR );

			return $target . DIRECTORY_SEPARATOR . $slug . '.zip';
		}

		return $target . '.zip';
	}

	/**
	 * Determine the plugin slug based on the base path.
	 *
	 * @return string
	 * @since 0.4.0
	 */
	protected static function plugin_slug() {
		$base = rtrim( self::base_path(), '/\\' );

		return basename( $base );
	}

	/**
	 * Default list of files/directories to exclude from deployment.
	 *
	 * @return array<int, string>
	 * @since 0.4.0
	 */
	protected static function default_deploy_exclusions() {
		return array(
			'.git',
			'.github',
			'.gitignore',
			'.gitattributes',
			'.idea',
			'.vscode',
			'.cache',
			'.DS_Store',
			'node_modules',
			'tests',
			'test',
			'docs',
			'temp',
			'webpack.config.js',
			'webpack.mix.js',
			'vite.config.js',
			'vite.config.ts',
			'vite.config.mjs',
			'gulpfile.js',
			'pnpm-lock.yaml',
			'yarn.lock',
			'pnpm-workspace.yaml',
			'tsconfig.json',
			'.eslintrc',
			'.eslintrc.js',
			'.eslintrc.cjs',
			'.stylelintrc',
			'.stylelintrc.json',
			'.stylelintrc.js',
			'.prettierrc',
			'.prettierrc.js',
			'.prettierrc.cjs',
			'.prettierrc.json',
			'.phpunit.result.cache',
			'phpunit.xml',
			'phpunit.xml.dist',
			'phpstan.neon',
			'phpstan.neon.dist',
			'.env',
			'.env.example',
			'.env.local',
			'.env.development',
			'.env.production',
			'.nvmrc',
			'.editorconfig',
			'assets/scss',
			'assets/js/src',
			'dist',
			'bin/cache',
		);
	}

	/**
	 * List default paths to include in distribution builds.
	 *
	 * @param string $source_root Base source directory.
	 * @return array<int, string>
	 * @since 0.4.2
	 */
protected static function default_dist_includes( $source_root ) {
	$candidates = array(
		'wpmoo.php',
		'src',
		'assets',
		'languages',
		'vendor',
		'composer.json',
		'composer.lock',
	);

		$includes = array();

		foreach ( $candidates as $candidate ) {
			if ( file_exists( $source_root . DIRECTORY_SEPARATOR . $candidate ) ) {
				$includes[] = $candidate;
			}
		}

	return $includes;
}

/**
 * Determine the default source directory for dist builds.
 *
 * @return string
 * @since 0.4.2
 */
protected static function default_dist_source() {
	$cwd = getcwd();

	if ( $cwd ) {
		$normalized = rtrim( $cwd, '/\\' );

		if ( self::looks_like_plugin_project( $normalized ) ) {
			return $normalized;
		}
	}

	return rtrim( self::framework_base_path(), '/\\' );
}

/**
 * Determine whether two filesystem paths refer to the same location.
 *
 * @param string $a First path.
 * @param string $b Second path.
 * @return bool
 * @since 0.4.2
 */
protected static function paths_equal( $a, $b ) {
	$ra = realpath( $a );
	$rb = realpath( $b );

	if ( false !== $ra && false !== $rb ) {
		return $ra === $rb;
	}

	$na = rtrim( str_replace( '\\', '/', $a ), '/' );
	$nb = rtrim( str_replace( '\\', '/', $b ), '/' );

	return $na === $nb;
}

/**
 * Determine if a string ends with a given suffix.
 *
 * @param string $haystack The string to inspect.
 * @param string $needle   The suffix to check.
 * @return bool
 * @since 0.4.3
 */
protected static function ends_with( $haystack, $needle ) {
	if ( '' === $needle ) {
		return true;
	}

	$length = strlen( $needle );

	if ( $length > strlen( $haystack ) ) {
		return false;
	}

	return substr( $haystack, -$length ) === $needle;
}

/**
 * Detect plugin or project metadata used for distribution labelling.
 *
 * @param string $path Project root path.
 * @return array<string, mixed>
 * @since 0.4.2
 */
protected static function detect_project_metadata( $path ) {
	$metadata = array(
		'name'    => null,
		'version' => null,
		'slug'    => null,
		'type'    => null,
		'main'    => null,
	);

	if ( ! is_dir( $path ) ) {
		return $metadata;
	}

	$composer_path = $path . DIRECTORY_SEPARATOR . 'composer.json';

	if ( file_exists( $composer_path ) ) {
		$contents = file_get_contents( $composer_path );

		if ( false !== $contents ) {
			$data = json_decode( $contents, true );

			if ( is_array( $data ) ) {
				if ( ! empty( $data['version'] ) ) {
					$metadata['version'] = $data['version'];
				}

				if ( ! empty( $data['type'] ) ) {
					$metadata['type'] = $data['type'];
				}

				if ( empty( $metadata['slug'] ) && ! empty( $data['name'] ) ) {
					$metadata['slug'] = self::sanitize_slug( basename( $data['name'] ) );
				}
			}
		}
	}

	foreach ( new \DirectoryIterator( $path ) as $file ) {
		if ( $file->isDot() || ! $file->isFile() ) {
			continue;
		}

		if ( strtolower( $file->getExtension() ) !== 'php' ) {
			continue;
		}

		$header = self::extract_plugin_header( $file->getPathname() );

		if ( empty( $header ) ) {
			continue;
		}

		$metadata['main'] = $file->getPathname();

		if ( ! empty( $header['name'] ) ) {
			$metadata['name'] = $header['name'];

			if ( empty( $metadata['slug'] ) ) {
				$metadata['slug'] = self::sanitize_slug( $header['name'] );
			}
		}

		if ( ! empty( $header['version'] ) ) {
			$metadata['version'] = $header['version'];
		}

		break;
	}

	if ( empty( $metadata['slug'] ) ) {
		$metadata['slug'] = self::sanitize_slug( basename( $path ) );
	}

	return $metadata;
}

/**
 * Determine whether a path appears to be a plugin project root.
 *
 * @param string $path Directory path.
 * @return bool
 * @since 0.4.2
 */
protected static function looks_like_plugin_project( $path ) {
	$meta = self::detect_project_metadata( $path );

	if ( ! empty( $meta['name'] ) && ! empty( $meta['main'] ) ) {
		return true;
	}

	if ( ! empty( $meta['type'] ) && false !== stripos( (string) $meta['type'], 'plugin' ) ) {
		return true;
	}

	return false;
}

/**
 * Extract plugin header information from a PHP file.
 *
 * @param string $file_path Absolute path to PHP file.
 * @return array<string, string>
 * @since 0.4.2
 */
protected static function extract_plugin_header( $file_path ) {
	$header = array();
	$handle = @fopen( $file_path, 'r' );

	if ( ! $handle ) {
		return $header;
	}

	$first_chunk = fread( $handle, 8192 );
	fclose( $handle );

	if ( false === $first_chunk ) {
		return $header;
	}

	if ( preg_match( '/^[ \t\/*#@]*Plugin Name:\s*(.*)$/mi', $first_chunk, $matches ) ) {
		$header['name'] = trim( $matches[1] );
	}

	if ( preg_match( '/^[ \t\/*#@]*Version:\s*(.*)$/mi', $first_chunk, $matches ) ) {
		$header['version'] = trim( $matches[1] );
	}

	return $header;
}

/**
 * Sanitize a string into a filesystem-safe slug.
 *
 * @param string $value Raw string.
 * @return string
 * @since 0.4.2
 */
protected static function sanitize_slug( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
	$value = trim( (string) $value, '-' );

	return $value ?: 'package';
}

/**
 * Remove non-runtime directories from the vendor tree.
 *
 * @param string $vendor_path Vendor directory.
 * @return void
 * @since 0.4.2
 */
protected static function prune_vendor_tree( $vendor_path ) {
	if ( ! is_dir( $vendor_path ) ) {
		return;
	}

	self::delete_directory( $vendor_path . DIRECTORY_SEPARATOR . 'bin' );

	$runtime_root = $vendor_path . DIRECTORY_SEPARATOR . 'wpmoo-org';

	if ( ! is_dir( $runtime_root ) ) {
		return;
	}

	$runtime_framework = $runtime_root . DIRECTORY_SEPARATOR . 'wpmoo';

	if ( is_link( $runtime_framework ) ) {
		@unlink( $runtime_framework );
		return;
	}

	if ( ! is_dir( $runtime_framework ) ) {
		return;
	}

	$nested_vendor = $runtime_framework . DIRECTORY_SEPARATOR . 'vendor';

	if ( is_dir( $nested_vendor ) ) {
		$nested_self = $nested_vendor . DIRECTORY_SEPARATOR . 'wpmoo-org' . DIRECTORY_SEPARATOR . 'wpmoo';

		if ( is_link( $nested_self ) ) {
			@unlink( $nested_self );
		} elseif ( is_dir( $nested_self ) ) {
			self::delete_directory( $nested_self );
		}

		self::delete_directory( $nested_vendor . DIRECTORY_SEPARATOR . 'bin' );
	}

	self::ensure_minified_assets( $runtime_framework . DIRECTORY_SEPARATOR . 'assets' );
	self::prune_assets_tree( $runtime_framework . DIRECTORY_SEPARATOR . 'assets' );
}

/**
 * Ensure minified variants exist for core assets.
 *
 * @param string $assets_dir Assets directory path.
 * @return void
 * @since 0.4.3
 */
protected static function ensure_minified_assets( $assets_dir ) {
	if ( ! is_dir( $assets_dir ) ) {
		return;
	}

	$map = array(
		'css' => '.css',
		'js'  => '.js',
	);

	foreach ( $map as $subdir => $suffix ) {
		$dir = $assets_dir . DIRECTORY_SEPARATOR . $subdir;

		if ( ! is_dir( $dir ) ) {
			continue;
		}

		$iterator = new \DirectoryIterator( $dir );

		foreach ( $iterator as $file ) {
			if ( $file->isDot() || ! $file->isFile() ) {
				continue;
			}

			$basename = $file->getBasename();
			$extension = strtolower( $file->getExtension() );

			if ( 'map' === $extension ) {
				continue;
			}

			if ( self::ends_with( $basename, '.min' . $suffix ) ) {
				continue;
			}

			if ( ! self::ends_with( $basename, $suffix ) ) {
				continue;
			}

			$min_path = substr( $file->getPathname(), 0, -strlen( $suffix ) ) . '.min' . $suffix;

			if ( ! file_exists( $min_path ) ) {
				@copy( $file->getPathname(), $min_path );
			}
		}
	}
}

/**
 * Remove non-minified and development assets from a directory tree.
 *
 * @param string $assets_dir Assets directory path.
 * @return void
 * @since 0.4.3
 */
protected static function prune_assets_tree( $assets_dir ) {
	if ( ! is_dir( $assets_dir ) ) {
		return;
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator(
			$assets_dir,
			\FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
		),
		\RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		$path = $item->getPathname();

		if ( $item->isDir() ) {
			if ( 'scss' === strtolower( $item->getBasename() ) ) {
				self::delete_directory( $path );
				continue;
			}

			@rmdir( $path );
			continue;
		}

		$basename = $item->getBasename();

		if ( self::ends_with( $basename, '.min.css' ) || self::ends_with( $basename, '.min.js' ) ) {
			continue;
		}

		@unlink( $path );
	}
}


	/**
	 * Normalize an absolute path (resolving relative input).
	 *
	 * @param string $path Input path.
	 * @return string|null
	 * @since 0.4.0
	 */
	protected static function normalize_absolute_path( $path ) {
		if ( '' === $path ) {
			return null;
		}

		$is_absolute = preg_match( '#^([a-zA-Z]:\\\\|//|/|\\\\)#', $path );

		if ( ! $is_absolute ) {
			$path = getcwd() . DIRECTORY_SEPARATOR . $path;
		}

		$real = realpath( $path );

		if ( false !== $real ) {
			return $real;
		}

		return rtrim( $path, '/\\' );
	}

	/**
	 * Determine whether a path is within another path.
	 *
	 * @param string $path        Path to check.
	 * @param string $container   Container path.
	 * @return bool
	 * @since 0.4.0
	 */
	protected static function path_is_within( $path, $container ) {
		$normalized_path      = self::normalize_absolute_path( $path );
		$normalized_container = self::normalize_absolute_path( $container );

		if ( null === $normalized_path || null === $normalized_container ) {
			return false;
		}

		$path      = rtrim( $normalized_path, '/\\' ) . DIRECTORY_SEPARATOR;
		$container = rtrim( $normalized_container, '/\\' ) . DIRECTORY_SEPARATOR;

		return 0 === strpos( $path, $container );
	}

	/**
	 * Check if a string ends with .zip (case-insensitive).
	 *
	 * @param string $value Input value.
	 * @return bool
	 * @since 0.4.0
	 */
	protected static function ends_with_zip( $value ) {
		return (bool) preg_match( '/\\.zip$/i', $value );
	}

	/**
	 * Create a temporary directory.
	 *
	 * @param string $prefix Directory prefix.
	 * @return string|null
	 * @since 0.4.0
	 */
	protected static function create_temp_directory( $prefix = 'wpmoo-' ) {
		$parent = sys_get_temp_dir();

		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			$path = $parent . DIRECTORY_SEPARATOR . $prefix . uniqid( '', true );

			if ( @mkdir( $path, 0755, true ) ) {
				return $path;
			}
		}

		return null;
	}

	/**
	 * Recursively copy files into the destination while respecting exclusions.
	 *
	 * @param string              $source_root Source root path.
	 * @param string              $destination_root Destination root path.
	 * @param array<int, string>  $exclusions Paths to exclude (relative to source root).
	 * @return bool
	 * @since 0.4.0
	 */
	protected static function copy_tree( $source_root, $destination_root, array $exclusions ) {
		$base_root = rtrim( self::base_path(), '/\\' );
		$iterator = scandir( $source_root );

		if ( false === $iterator ) {
			return false;
		}

		foreach ( $iterator as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$source_path      = $source_root . DIRECTORY_SEPARATOR . $entry;
			$relative         = ltrim( str_replace( array( '\\', '/' ), '/', substr( $source_path, strlen( $base_root ) ) ), '/' );
			$destination_path = $destination_root . DIRECTORY_SEPARATOR . $entry;

			if ( self::should_skip_path( $relative, $exclusions ) ) {
				continue;
			}

			if ( is_link( $source_path ) ) {
				$target = readlink( $source_path );

				if ( false === $target ) {
					continue;
				}

				@symlink( $target, $destination_path );
				continue;
			}

			if ( is_dir( $source_path ) ) {
				if ( ! is_dir( $destination_path ) && ! @mkdir( $destination_path, 0755, true ) ) {
					Console::warning( 'Unable to create directory: ' . self::relative_path( $destination_path ) );
					return false;
				}

				if ( ! self::copy_tree( $source_path, $destination_path, $exclusions ) ) {
					return false;
				}

				continue;
			}

			$destination_dir = dirname( $destination_path );

			if ( ! is_dir( $destination_dir ) && ! @mkdir( $destination_dir, 0755, true ) ) {
				Console::warning( 'Unable to create directory: ' . self::relative_path( $destination_dir ) );
				return false;
			}

			if ( ! @copy( $source_path, $destination_path ) ) {
				Console::warning( 'Failed to copy file: ' . self::relative_path( $source_path ) );
				return false;
			}
		}

		return true;
	}

	/**
	 * Copy a file or directory into the distribution workspace.
	 *
	 * @param string $source      Absolute source path.
	 * @param string $destination Absolute destination path.
	 * @return bool
	 * @since 0.4.2
	 */
	protected static function copy_within_dist( $source, $destination ) {
		if ( ! file_exists( $source ) ) {
			return false;
		}

		if ( is_link( $source ) ) {
			$target = readlink( $source );

			return false !== $target ? @symlink( $target, $destination ) : false;
		}

		if ( is_dir( $source ) ) {
			return self::copy_directory_for_dist( $source, $destination );
		}

		$directory = dirname( $destination );

		if ( ! is_dir( $directory ) && ! @mkdir( $directory, 0755, true ) ) {
			return false;
		}

		return @copy( $source, $destination );
	}

	/**
	 * Recursively copy a directory for distribution purposes.
	 *
	 * @param string $source      Source directory.
	 * @param string $destination Destination directory.
	 * @return bool
	 * @since 0.4.2
	 */
	protected static function copy_directory_for_dist( $source, $destination ) {
		if ( ! is_dir( $destination ) && ! @mkdir( $destination, 0755, true ) ) {
			return false;
		}

		$items = scandir( $source );

		if ( false === $items ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$source_path      = $source . DIRECTORY_SEPARATOR . $item;
			$destination_path = $destination . DIRECTORY_SEPARATOR . $item;

			if ( is_link( $source_path ) ) {
				$target = readlink( $source_path );

				if ( false === $target ) {
					continue;
				}

				@symlink( $target, $destination_path );
				continue;
			}

			if ( is_dir( $source_path ) ) {
				if ( ! self::copy_directory_for_dist( $source_path, $destination_path ) ) {
					return false;
				}

				continue;
			}

			$directory = dirname( $destination_path );

			if ( ! is_dir( $directory ) && ! @mkdir( $directory, 0755, true ) ) {
				return false;
			}

			if ( ! @copy( $source_path, $destination_path ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $path Path to delete.
	 * @return bool
	 * @since 0.4.0
	 */
	protected static function delete_directory( $path ) {
		if ( ! file_exists( $path ) ) {
			return true;
		}

		if ( is_file( $path ) || is_link( $path ) ) {
			return @unlink( $path );
		}

		$items = scandir( $path );

		if ( false === $items ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$target = $path . DIRECTORY_SEPARATOR . $item;

			if ( is_dir( $target ) && ! is_link( $target ) ) {
				if ( ! self::delete_directory( $target ) ) {
					return false;
				}
			} else {
				if ( ! @unlink( $target ) ) {
					return false;
				}
			}
		}

		return @rmdir( $path );
	}

	/**
	 * Determine whether a relative path should be skipped.
	 *
	 * @param string             $relative   Relative path.
	 * @param array<int, string> $exclusions Exclusions.
	 * @return bool
	 * @since 0.4.0
	 */
	protected static function should_skip_path( $relative, array $exclusions ) {
		$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );

		foreach ( $exclusions as $pattern ) {
			$normalized = ltrim( str_replace( '\\', '/', $pattern ), '/' );

			if ( '' === $normalized ) {
				continue;
			}

			if ( $relative === $normalized ) {
				return true;
			}

			if ( 0 === strpos( $relative, $normalized . '/' ) ) {
				return true;
			}

			if ( fnmatch( $normalized, $relative, FNM_PATHNAME ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Create a zip archive from a directory.
	 *
	 * @param string $source_dir Directory to archive.
	 * @param string $zip_path   Destination zip path.
	 * @return bool
	 * @since 0.4.0
	 */
	protected static function create_zip_archive( $source_dir, $zip_path ) {
		if ( ! class_exists( '\ZipArchive' ) ) {
			Console::error( 'ZipArchive extension is not available.' );

			return false;
		}

		$zip = new \ZipArchive();

		if ( true !== $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
			return false;
		}

		$source_dir = rtrim( $source_dir, '/\\' );
		$iterator   = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$source_dir,
				\FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
			),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			$path = $item->getPathname();
			$rel  = ltrim( substr( $path, strlen( $source_dir ) ), DIRECTORY_SEPARATOR );

			if ( '' === $rel ) {
				continue;
			}

			if ( $item->isDir() ) {
				$zip->addEmptyDir( str_replace( '\\', '/', $rel ) );
			} elseif ( $item->isFile() ) {
				$zip->addFile( $path, str_replace( '\\', '/', $rel ) );
			}
		}

		$zip->close();

		return true;
	}

	/**
	 * Proxy to WordPress do_action when available.
	 *
	 * @param string $hook Hook name.
	 * @param mixed  ...$args Arguments.
	 * @return void
	 * @since 0.4.0
	 */
	protected static function do_action_safe( $hook, ...$args ) {
		if ( function_exists( 'do_action' ) ) {
			do_action( $hook, ...$args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHookname -- Framework hook.
		}
	}

	/**
	 * Proxy to WordPress apply_filters when available.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Initial value.
	 * @param mixed  ...$args Arguments.
	 * @return mixed
	 * @since 0.4.0
	 */
	protected static function apply_filters_safe( $hook, $value, ...$args ) {
		if ( function_exists( 'apply_filters' ) ) {
			return apply_filters( $hook, $value, ...$args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHookname -- Framework hook.
		}

		return $value;
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
	 * Build front-end assets using the configured Node package manager.
	 *
	 * @param array<int, mixed> $args Optional CLI arguments.
	 * @return void
	 * @since 0.4.0
	 */
	protected static function cmd_build( array $args = array() ) {
		$options = self::parse_build_options( $args );

		Console::line();
		Console::comment( 'Building assets…' );

		$success = self::perform_build(
			array_merge(
				$options,
				array(
					'allow-missing' => false,
				)
			)
		);

		if ( ! $success ) {
			Console::error( 'Asset build failed.' );
		}

		Console::line();
	}

	/**
	 * Create a deployable copy of the framework (optionally zipped).
	 *
	 * @param array<int, mixed> $args Optional CLI arguments.
	 * @return void
	 * @since 0.4.0
	 */
	protected static function cmd_deploy( array $args = array() ) {
		$options = self::parse_deploy_options( $args );
		$base    = self::base_path();
		$slug    = self::plugin_slug();

		Console::line();
		Console::comment( 'Preparing deployable package…' );

		$target_input = $options['target'];

		if ( null === $target_input || '' === $target_input ) {
			$target_input = self::default_deploy_directory();
			Console::comment( '→ No destination provided; will use ' . self::relative_path( $target_input ) );
		}

		$target_path = self::normalize_absolute_path( $target_input );

		if ( ! $target_path ) {
			Console::error( 'Unable to resolve deployment path.' );
			Console::line();
			return;
		}

		$options['target'] = $target_path;

		$is_zip      = (bool) $options['zip'];
		$zip_path    = null;
		$working_dir = $target_path;
		$cleanup_dir = false;

		if ( $is_zip || self::ends_with_zip( $target_path ) ) {
			$zip_path = $options['zip-path'];

			if ( ! $zip_path ) {
				if ( self::ends_with_zip( $target_path ) ) {
					$zip_path = $target_path;
				} else {
					$zip_path = self::default_deploy_zip_path( $target_path, $slug );
				}
			}

			$zip_path = self::normalize_absolute_path( $zip_path );

			if ( ! $zip_path ) {
				Console::error( 'Unable to resolve zip output path.' );
				Console::line();
				return;
			}

			$working_dir = self::create_temp_directory( $slug . '-deploy-' );

			if ( ! $working_dir ) {
				Console::error( 'Could not create temporary directory for archive generation.' );
				Console::line();
				return;
			}

			$cleanup_dir = true;
			$is_zip      = true;

			if ( ! self::ensure_directory( dirname( $zip_path ) . DIRECTORY_SEPARATOR ) ) {
				Console::error( 'Unable to create directories for zip output.' );
				self::delete_directory( $working_dir );
				Console::line();
				return;
			}
		} else {
			if ( self::path_is_within( $target_path, $base ) ) {
				Console::error( 'Deployment path cannot be inside the source directory.' );
				Console::line();
				return;
			}
		}

		$options['zip']       = $is_zip;
		$options['zip-path']  = $zip_path;
		$options['work-path'] = $working_dir;

		self::do_action_safe( 'wpmoo_cli_deploy_start', $base, $options );

		if ( ! $options['no-build'] ) {
			self::do_action_safe( 'wpmoo_cli_deploy_before_build', $base, $options );

			Console::comment( '→ Building assets before packaging' );

			$build_success = self::perform_build(
				array(
					'pm'             => $options['pm'],
					'script'         => $options['script'],
					'force-install'  => $options['force-install'],
					'skip-install'   => $options['skip-install'],
					'allow-missing'  => true,
				)
			);

			if ( ! $build_success ) {
				if ( $cleanup_dir ) {
					self::delete_directory( $working_dir );
				}
				Console::error( 'Deployment aborted due to build failure.' );
				Console::line();
				return;
			}

			self::do_action_safe( 'wpmoo_cli_deploy_after_build', $base, $options );
		} else {
			Console::comment( '→ Skipping asset build (--no-build specified)' );
		}

		$exclusions = self::apply_filters_safe(
			'wpmoo_cli_deploy_exclusions',
			self::default_deploy_exclusions(),
			$base,
			$options
		);

		if ( ! is_array( $exclusions ) ) {
			$exclusions = self::default_deploy_exclusions();
		}

		if ( ! $is_zip ) {
			if ( is_dir( $working_dir ) ) {
				Console::comment( '→ Clearing destination directory' );
				self::delete_directory( $working_dir );
			}

			if ( ! self::ensure_directory( rtrim( $working_dir, '/\\' ) . DIRECTORY_SEPARATOR ) ) {
				Console::error( 'Unable to prepare destination directory.' );
				Console::line();
				return;
			}
		}

		Console::comment( '→ Copying files to ' . self::relative_path( $working_dir ) );

		$copy_ok = self::copy_tree(
			rtrim( $base, '/\\' ),
			rtrim( $working_dir, '/\\' ),
			$exclusions
		);

		if ( ! $copy_ok ) {
			if ( $cleanup_dir ) {
				self::delete_directory( $working_dir );
			}

			Console::error( 'Failed to copy files for deployment.' );
			Console::line();
			return;
		}

		self::post_process_deploy( $working_dir, $options );

		if ( $is_zip && $zip_path ) {
			Console::comment( '→ Creating archive ' . self::relative_path( $zip_path ) );

			$zip_ok = self::create_zip_archive( $working_dir, $zip_path );

			if ( ! $zip_ok ) {
				Console::error( 'Failed to create deployment archive.' );
				if ( $cleanup_dir ) {
					self::delete_directory( $working_dir );
				}
				Console::line();
				return;
			}

			Console::info( 'Deployment archive ready at ' . self::relative_path( $zip_path ) );
		} else {
			Console::info( 'Deployment directory ready at ' . self::relative_path( $working_dir ) );
		}

		self::do_action_safe(
			'wpmoo_cli_deploy_completed',
			$base,
			array(
				'destination' => $is_zip && $zip_path ? $zip_path : $working_dir,
				'options'     => $options,
			)
		);

		if ( $cleanup_dir ) {
			self::delete_directory( $working_dir );
		}

		Console::line();
	}

	/**
	 * Manage framework version increments and propagation.
	 *
	 * @param array<int, mixed> $args Optional CLI arguments.
	 * @return void
	 * @since 0.4.1
	 */
	protected static function cmd_version( array $args = array() ) {
		$base = self::base_path();

		$current_version = self::detect_current_version( $base );

		if ( ! $current_version ) {
			Console::error( 'Could not determine current version from composer.json.' );
			return;
		}

		$options = self::parse_version_arguments( $args );

		$requested_version = null;

		if ( $options['explicit'] ) {
			$requested_version = self::sanitize_version_input( $options['explicit'] );

			if ( ! self::is_valid_semver( $requested_version ) ) {
				Console::error( 'Explicit version "' . $options['explicit'] . '" is not a valid semantic version (expected format x.y.z).' );
				return;
			}
		} else {
			$bump_type        = $options['bump'] ? $options['bump'] : 'patch';
			$requested_version = self::bump_semver( $current_version, $bump_type, $options['pre-release'] );

			if ( ! $requested_version ) {
				Console::error( 'Unable to compute new version from current value ' . $current_version );
				return;
			}
		}

		if ( $requested_version === $current_version ) {
			Console::comment( 'Version remains unchanged (' . $current_version . '). Nothing to do.' );
			return;
		}

		Console::line();
		Console::comment( 'Updating WPMoo version: ' . $current_version . ' → ' . $requested_version );

		$updated_files = self::update_version_files(
			$base,
			$current_version,
			$requested_version,
			$options['dry-run']
		);

		if ( empty( $updated_files ) ) {
			Console::warning( 'No files required updating. Verify project structure.' );
		} else {
			foreach ( $updated_files as $file ) {
				Console::line(
					( $options['dry-run'] ? '[dry-run] ' : '' ) .
					'   • ' . self::relative_path( $file )
				);
			}
		}

		if ( $options['dry-run'] ) {
			Console::info( 'Dry run completed. No files were modified.' );
			Console::line();
			return;
		}

		Console::info( 'Version updated successfully to ' . $requested_version );

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
	 * @param string|null          $cwd       Optional working directory.
	 * @return array{0:int,1:array<int,string>} Tuple of exit status and output lines.
	 */
	protected static function execute_command( $binary, array $arguments, $cwd = null ) {
		$prefix = self::command_prefix( $binary );
		$cmd    = $prefix;

		foreach ( $arguments as $argument ) {
			$cmd .= ' ' . self::escape_argument( $argument );
		}

		$cmd   .= ' 2>&1';
		$output = array();
		$status = 0;

		$previous_cwd = null;

		if ( null !== $cwd && '' !== $cwd && is_dir( $cwd ) ) {
			$previous_cwd = getcwd();

			if ( false === @chdir( $cwd ) ) {
				$previous_cwd = null;
			}
		}

		exec( $cmd, $output, $status ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

		if ( null !== $previous_cwd ) {
			@chdir( $previous_cwd );
		}

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
 * Resolve the framework base path regardless of plugin context.
 *
 * @return string Base path with trailing separator.
 * @since 0.4.3
 */
protected static function framework_base_path() {
	if ( defined( 'WPMOO_PATH' ) ) {
		return rtrim( WPMOO_PATH, '/\\' ) . DIRECTORY_SEPARATOR;
	}

	return self::base_path();
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

	/**
	 * Install composer dependencies in deployment directory using --no-dev.
	 *
	 * @param string $working_dir Deployment directory.
	 * @return bool True if optimisation completed successfully.
	 * @since 0.4.1
	 */
	protected static function optimise_composer_dependencies( $working_dir ) {
		$composer_json = $working_dir . DIRECTORY_SEPARATOR . 'composer.json';

		if ( ! file_exists( $composer_json ) ) {
			return false;
		}

		if ( self::composer_uses_path_repositories( $composer_json ) ) {
			Console::comment( '→ Composer path repositories detected; skipping dependency optimisation.' );

			return false;
		}

		$composer_binary = self::locate_composer_binary( $working_dir );

		if ( ! $composer_binary ) {
			Console::comment( '→ Composer binary not found; skipping dependency optimisation.' );

			return false;
		}

		Console::comment( '→ Optimising composer dependencies (--no-dev)' );

		$args = array(
			'install',
			'--no-dev',
			'--prefer-dist',
			'--no-interaction',
			'--no-progress',
			'--optimize-autoloader',
		);

		list( $status, $output ) = self::execute_command( $composer_binary, $args, $working_dir );
		self::output_command_lines( $output );

		if ( 0 !== $status ) {
			Console::warning( 'Composer install failed (exit code ' . $status . '). Existing vendor directory retained.' );

			return false;
		}

		return true;
	}

	/**
	 * Attempt to locate a composer executable.
	 *
	 * @param string $working_dir Deployment directory.
	 * @return string|null Absolute path to composer binary.
	 * @since 0.4.1
	 */
	protected static function locate_composer_binary( $working_dir ) {
		$candidates = array(
			$working_dir . DIRECTORY_SEPARATOR . 'composer.phar',
			$working_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer',
			$working_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer.phar',
		);

		foreach ( $candidates as $candidate ) {
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		$composer = self::search_system_path( 'composer' );

		if ( $composer ) {
			return $composer;
		}

		$composer_phar = self::search_system_path( 'composer.phar' );

		if ( $composer_phar ) {
			return $composer_phar;
		}

		return null;
	}

	/**
	 * Remove a file if it exists.
	 *
	 * @param string $path File path.
	 * @return void
	 * @since 0.4.1
	 */
	protected static function remove_if_exists( $path ) {
		if ( file_exists( $path ) && ! is_dir( $path ) ) {
			@unlink( $path );
		}
	}

	/**
	 * Determine whether composer.json uses path repositories.
	 *
	 * @param string $composer_json Path to composer.json.
	 * @return bool
	 * @since 0.4.2
	 */
	protected static function composer_uses_path_repositories( $composer_json ) {
		if ( ! $composer_json || ! file_exists( $composer_json ) ) {
			return false;
		}

		$contents = file_get_contents( $composer_json );

		if ( false === $contents ) {
			return false;
		}

		$data = json_decode( $contents, true );

		if ( ! is_array( $data ) || empty( $data['repositories'] ) || ! is_array( $data['repositories'] ) ) {
			return false;
		}

		foreach ( $data['repositories'] as $repository ) {
			if ( ! is_array( $repository ) ) {
				continue;
			}

			if ( isset( $repository['type'] ) && 'path' === $repository['type'] ) {
				return true;
			}
		}

		return false;
	}

}
